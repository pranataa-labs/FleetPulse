<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\TrackingCheckpoint;
use App\Services\GeoDistanceService;
use App\Services\PricingService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispatcherController extends Controller
{
    /**
     * Dispatcher Live Operations Control Room.
     */
    public function index(): View
    {
        $tenantId = TenantContext::getTenantId() ?? 1;

        $orders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['driver.user', 'driver.fleet'])
            ->latest()
            ->paginate(15);

        $drivers = Driver::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['user', 'fleet'])
            ->get();

        $activeFleetsCount = Fleet::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->count();

        $dispatchedOrdersCount = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('delivery_status', 'DISPATCHED')
            ->count();

        $inTransitCount = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('delivery_status', 'IN_TRANSIT')
            ->count();

        $deliveredTodayCount = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('delivery_status', 'DELIVERED')
            ->whereDate('updated_at', today())
            ->count();

        return view('dispatcher.index', compact(
            'orders',
            'drivers',
            'activeFleetsCount',
            'dispatchedOrdersCount',
            'inTransitCount',
            'deliveredTodayCount'
        ));
    }

    /**
     * API returning all online drivers for the live Dispatch map.
     */
    public function apiFleetGeo(): JsonResponse
    {
        $tenantId = TenantContext::getTenantId() ?? 1;

        $drivers = Driver::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['user', 'fleet'])
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user?->name ?? 'Courier #' . $driver->id,
                    'phone' => $driver->user?->phone,
                    'is_online' => $driver->is_online,
                    'is_on_duty' => $driver->is_on_duty,
                    'vehicle_type' => $driver->fleet?->vehicle_type ?? 'MOTORCYCLE',
                    'license_plate' => $driver->fleet?->license_plate ?? 'N/A',
                    'lat' => $driver->last_lat ? (float) $driver->last_lat : null,
                    'lng' => $driver->last_lng ? (float) $driver->last_lng : null,
                    'heading' => $driver->last_heading ? (float) $driver->last_heading : null,
                    'last_ping' => $driver->last_ping_at?->diffForHumans(),
                ];
            });

        return response()->json([
            'tenant_id' => $tenantId,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Store new order with automated Haversine distance and dynamic pricing.
     */
    public function storeOrder(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::getTenantId() ?? 1;

        $validated = $request->validate([
            'sender_name' => 'required|string|max:100',
            'sender_phone' => 'required|string|max:20',
            'pickup_address' => 'required|string|max:255',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'recipient_name' => 'required|string|max:100',
            'recipient_phone' => 'required|string|max:20',
            'dropoff_address' => 'required|string|max:255',
            'dropoff_lat' => 'required|numeric|between:-90,90',
            'dropoff_lng' => 'required|numeric|between:-180,180',
            'vehicle_type' => 'required|string|in:MOTORCYCLE,VAN,BOX_TRUCK,SEMI_TRAILER',
            'weight_kg' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string|max:500',
        ]);

        // 1. Calculate Distance using Haversine
        $distanceKm = GeoDistanceService::haversine(
            (float) $validated['pickup_lat'],
            (float) $validated['pickup_lng'],
            (float) $validated['dropoff_lat'],
            (float) $validated['dropoff_lng']
        );

        // 2. Calculate Fee using Pricing Matrix
        $pricing = PricingService::calculateFee(
            $tenantId,
            $validated['vehicle_type'],
            $distanceKm,
            (float) $validated['weight_kg']
        );

        // 3. Create Order
        $order = Order::create([
            'tenant_id' => $tenantId,
            'sender_name' => $validated['sender_name'],
            'sender_phone' => $validated['sender_phone'],
            'pickup_address' => $validated['pickup_address'],
            'pickup_lat' => $validated['pickup_lat'],
            'pickup_lng' => $validated['pickup_lng'],
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'dropoff_address' => $validated['dropoff_address'],
            'dropoff_lat' => $validated['dropoff_lat'],
            'dropoff_lng' => $validated['dropoff_lng'],
            'vehicle_type' => $validated['vehicle_type'],
            'weight_kg' => $validated['weight_kg'],
            'distance_km' => $distanceKm,
            'total_fee' => $pricing['total_fee'],
            'payment_status' => 'PAID',
            'delivery_status' => 'DISPATCHED',
            'notes' => $validated['notes'] ?? null,
        ]);

        TrackingCheckpoint::create([
            'order_id' => $order->id,
            'status' => 'DISPATCHED',
            'lat' => $order->pickup_lat,
            'lng' => $order->pickup_lng,
            'note' => 'Order created and dispatched for driver pickup.',
            'recorded_at' => now(),
        ]);

        return redirect()->route('dispatcher.index')->with('success', "Pesanan #{$order->order_number} berhasil dibuat dan didispatch.");
    }

    /**
     * Dispatch order to driver pool.
     */
    public function dispatchOrder(int $orderId): RedirectResponse
    {
        $order = Order::withoutGlobalScope('tenant')->findOrFail($orderId);
        $order->update(['delivery_status' => 'DISPATCHED']);

        TrackingCheckpoint::create([
            'order_id' => $order->id,
            'status' => 'DISPATCHED',
            'lat' => $order->pickup_lat,
            'lng' => $order->pickup_lng,
            'note' => 'Order dispatched to available courier pool.',
            'recorded_at' => now(),
        ]);

        return redirect()->back()->with('success', "Pesanan #{$order->order_number} telah disiarkan ke driver.");
    }
}
