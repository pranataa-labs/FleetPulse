<?php

namespace App\Http\Controllers;

use App\Events\DriverLocationUpdated;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\TrackingCheckpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverApiController extends Controller
{
    /**
     * Update driver live GPS location and broadcast via WebSockets.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0',
        ]);

        $driver = Driver::with(['user', 'fleet'])->findOrFail($validated['driver_id']);

        $driver->update([
            'last_lat' => $validated['lat'],
            'last_lng' => $validated['lng'],
            'last_heading' => $validated['heading'] ?? null,
            'last_ping_at' => now(),
            'is_online' => true,
        ]);

        $activeOrder = $driver->activeOrder();
        $trackingToken = $activeOrder?->tracking_token;

        // Broadcast real-time GPS event over Reverb
        event(new DriverLocationUpdated(
            tenantId: $driver->tenant_id,
            driverId: $driver->id,
            driverName: $driver->user?->name ?? 'Driver #' . $driver->id,
            vehicleType: $driver->fleet?->vehicle_type ?? 'MOTORCYCLE',
            licensePlate: $driver->fleet?->license_plate ?? 'N/A',
            lat: (float) $validated['lat'],
            lng: (float) $validated['lng'],
            heading: isset($validated['heading']) ? (float) $validated['heading'] : null,
            speed: isset($validated['speed']) ? (float) $validated['speed'] : null,
            trackingToken: $trackingToken,
        ));

        Log::info('Driver GPS ping received', [
            'driver_id' => $driver->id,
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'tracking_token' => $trackingToken,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location updated and broadcasted successfully.',
            'driver' => [
                'id' => $driver->id,
                'last_lat' => $driver->last_lat,
                'last_lng' => $driver->last_lng,
                'is_online' => $driver->is_online,
                'active_order_token' => $trackingToken,
            ],
        ]);
    }

    /**
     * Toggle duty status (Online / On-Duty).
     */
    public function toggleDuty(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'is_online' => 'nullable|boolean',
            'is_on_duty' => 'nullable|boolean',
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);

        $updates = [];
        if (isset($validated['is_online'])) {
            $updates['is_online'] = $validated['is_online'];
        }
        if (isset($validated['is_on_duty'])) {
            $updates['is_on_duty'] = $validated['is_on_duty'];
        }

        $driver->update($updates);

        Log::info('Driver duty status toggled', [
            'driver_id' => $driver->id,
            'is_online' => $driver->is_online,
            'is_on_duty' => $driver->is_on_duty,
        ]);

        return response()->json([
            'success' => true,
            'is_online' => $driver->is_online,
            'is_on_duty' => $driver->is_on_duty,
        ]);
    }

    /**
     * Claim dispatched order with pessimistic locking (lockForUpdate).
     */
    public function claimOrder(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driverId = (int) $validated['driver_id'];

        try {
            $claimedOrder = DB::transaction(function () use ($orderId, $driverId) {
                // Pessimistic Lock on the specific row
                $order = Order::withoutGlobalScope('tenant')
                    ->where('id', $orderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->delivery_status !== 'DISPATCHED') {
                    return null; // Already claimed or no longer available
                }

                $order->update([
                    'driver_id' => $driverId,
                    'delivery_status' => 'ACCEPTED',
                ]);

                TrackingCheckpoint::create([
                    'order_id' => $order->id,
                    'status' => 'ACCEPTED',
                    'note' => 'Driver accepted delivery assignment.',
                    'recorded_at' => now(),
                ]);

                return $order;
            });

            if (! $claimedOrder) {
                return response()->json([
                    'success' => false,
                    'error' => 'ORDER_ALREADY_CLAIMED',
                    'message' => 'Pesanan sudah diambil oleh driver lain atau tidak lagi tersedia.',
                ], 409); // 409 Conflict
            }

            // Auto-create transparent chat room thread (Kurir, Pengirim, Dispatcher, Penerima)
            $driver = Driver::withoutGlobalScope('tenant')->with('user')->find($driverId);
            $driverName = $driver?->user?->name ?? 'Kurir';

            OrderMessage::firstOrCreate(
                [
                    'order_id' => $claimedOrder->id,
                    'sender_role' => 'SYSTEM',
                ],
                [
                    'sender_name' => 'Sistem FleetPulse',
                    'message' => "Ruang koordinasi aktif. Kurir {$driverName} telah mengambil tugas pengantaran ini ({$claimedOrder->order_number}). Pengirim, Kurir, Dispatcher, dan Penerima kini terhubung dalam obrolan transparan.",
                ]
            );

            Log::info('Order successfully claimed with pessimistic lock', [
                'order_id' => $claimedOrder->id,
                'driver_id' => $driverId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil diambil.',
                'order' => $claimedOrder,
            ]);
        } catch (\Exception $e) {
            Log::error('Order claim failed', [
                'order_id' => $orderId,
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil pesanan.',
            ], 500);
        }
    }

    /**
     * Update order delivery progress status.
     */
    public function updateOrderStatus(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'status' => 'required|in:PICKED_UP,IN_TRANSIT,DELIVERED,CANCELLED',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $order = Order::withoutGlobalScope('tenant')
            ->where('id', $orderId)
            ->where('driver_id', $validated['driver_id'])
            ->firstOrFail();

        $order->update([
            'delivery_status' => $validated['status'],
        ]);

        TrackingCheckpoint::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'note' => $validated['note'] ?? "Order status updated to {$validated['status']}.",
            'recorded_at' => now(),
        ]);

        if ($validated['status'] === 'PICKED_UP') {
            OrderMessage::create([
                'order_id' => $order->id,
                'sender_role' => 'SYSTEM',
                'sender_name' => 'Sistem FleetPulse',
                'message' => 'Paket fisik telah berhasil diambil (PICKED UP) oleh kurir dari tangan pengirim.',
            ]);
        } elseif ($validated['status'] === 'IN_TRANSIT') {
            OrderMessage::create([
                'order_id' => $order->id,
                'sender_role' => 'SYSTEM',
                'sender_name' => 'Sistem FleetPulse',
                'message' => 'Kurir telah berangkat dan sedang dalam perjalanan meluncur menuju alamat penerima (IN TRANSIT). Pantau live di peta.',
            ]);
        } elseif ($validated['status'] === 'DELIVERED') {
            OrderMessage::create([
                'order_id' => $order->id,
                'sender_role' => 'SYSTEM',
                'sender_name' => 'Sistem FleetPulse',
                'message' => 'Paket telah berhasil diserahkan kepada penerima (DELIVERED). Pengiriman selesai dengan sukses.',
            ]);
        }

        Log::info('Order status updated', [
            'order_id' => $order->id,
            'status' => $validated['status'],
            'driver_id' => $validated['driver_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status pesanan berhasil diperbarui ke {$validated['status']}.",
            'order' => $order->fresh(['checkpoints']),
        ]);
    }

    /**
     * Get available dispatched orders pool for Android Driver App.
     */
    public function availableOrders(Request $request): JsonResponse
    {
        $driverId = $request->query('driver_id');
        $driver = $driverId ? Driver::find($driverId) : null;

        $query = Order::withoutGlobalScope('tenant')
            ->where('delivery_status', 'DISPATCHED')
            ->latest();

        if ($driver) {
            $query->where('tenant_id', $driver->tenant_id);
        }

        $orders = $query->take(20)->get()->map(function ($o) {
            return [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'pickup_address' => $o->pickup_address,
                'dropoff_address' => $o->dropoff_address,
                'total_fee' => (float) $o->total_fee,
                'distance_km' => (float) $o->distance_km,
                'weight_kg' => (float) $o->weight_kg,
                'vehicle_type' => $o->vehicle_type,
                'created_at' => $o->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get current active assigned order for Android Driver App.
     */
    public function activeOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);
        $order = $driver->activeOrder();

        if (! $order) {
            return response()->json([
                'success' => true,
                'has_active_order' => false,
                'order' => null,
            ]);
        }

        $senderWa = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', (string) $order->sender_phone));
        $recipientWa = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', (string) $order->recipient_phone));

        return response()->json([
            'success' => true,
            'has_active_order' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'tracking_token' => $order->tracking_token,
                'delivery_status' => $order->delivery_status,
                'total_fee' => (float) $order->total_fee,
                'distance_km' => (float) $order->distance_km,
                'pickup' => [
                    'name' => $order->sender_name,
                    'phone' => $order->sender_phone,
                    'wa_link' => $senderWa ? "https://wa.me/{$senderWa}?text=" . urlencode("Halo {$order->sender_name}, saya kurir FleetPulse akan menjemput paket #{$order->order_number}") : null,
                    'address' => $order->pickup_address,
                    'lat' => (float) $order->pickup_lat,
                    'lng' => (float) $order->pickup_lng,
                ],
                'dropoff' => [
                    'name' => $order->recipient_name,
                    'phone' => $order->recipient_phone,
                    'wa_link' => $recipientWa ? "https://wa.me/{$recipientWa}?text=" . urlencode("Halo {$order->recipient_name}, saya kurir FleetPulse sedang mengantarkan paket #{$order->order_number}") : null,
                    'address' => $order->dropoff_address,
                    'lat' => (float) $order->dropoff_lat,
                    'lng' => (float) $order->dropoff_lng,
                ],
                'next_step' => match ($order->delivery_status) {
                    'ACCEPTED' => ['action' => 'PICKED_UP', 'label' => 'Konfirmasi Ambil Fisik Paket'],
                    'PICKED_UP' => ['action' => 'IN_TRANSIT', 'label' => 'Mulai Jalan Antar ke Penerima'],
                    'IN_TRANSIT' => ['action' => 'DELIVERED', 'label' => 'Selesai Antar (Serahkan ke Penerima)'],
                    default => null,
                },
            ],
        ]);
    }
}
