<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    /**
     * Search and redirect to live tracking interface by tracking token or order number.
     */
    public function lookup(Request $request)
    {
        $rawQuery = $request->input('tracking_number') ?? $request->input('query') ?? '';
        $query = trim((string) $rawQuery);

        if (empty($query)) {
            return redirect()->route('home')->with('error', 'Silakan masukkan nomor resi atau kode tracking.');
        }

        $order = Order::withoutGlobalScope('tenant')
            ->where('tracking_token', $query)
            ->orWhere('order_number', $query)
            ->orWhere('order_number', 'LIKE', "%{$query}%")
            ->first();

        if (! $order) {
            return redirect()->route('home')->with('error', "Nomor resi '{$query}' tidak ditemukan dalam sistem. Mohon periksa kembali kode resi Anda.");
        }

        return redirect()->route('tracking.show', $order->tracking_token);
    }

    /**
     * Show live parcel tracking interface for customers.
     */
    public function show(string $token): View
    {
        $order = Order::withoutGlobalScope('tenant')
            ->with(['tenant', 'driver.user', 'driver.fleet', 'checkpoints'])
            ->where('tracking_token', $token)
            ->firstOrFail();

        return view('tracking.show', compact('order'));
    }

    /**
     * API endpoint returning current live coordinates and timeline for polling/fallback.
     */
    public function status(string $token): JsonResponse
    {
        $order = Order::withoutGlobalScope('tenant')
            ->with(['tenant', 'driver.user', 'driver.fleet', 'checkpoints'])
            ->where('tracking_token', $token)
            ->firstOrFail();

        return response()->json([
            'order_number' => $order->order_number,
            'tracking_token' => $order->tracking_token,
            'delivery_status' => $order->delivery_status,
            'tenant' => [
                'name' => $order->tenant->name,
                'slug' => $order->tenant->slug,
            ],
            'pickup' => [
                'address' => $order->pickup_address,
                'lat' => (float) $order->pickup_lat,
                'lng' => (float) $order->pickup_lng,
                'sender' => $order->sender_name,
            ],
            'dropoff' => [
                'address' => $order->dropoff_address,
                'lat' => (float) $order->dropoff_lat,
                'lng' => (float) $order->dropoff_lng,
                'recipient' => $order->recipient_name,
            ],
            'courier' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->user?->name,
                'phone' => $order->driver->user?->phone,
                'vehicle_type' => $order->driver->fleet?->vehicle_type ?? 'MOTORCYCLE',
                'license_plate' => $order->driver->fleet?->license_plate ?? 'N/A',
                'lat' => $order->driver->last_lat ? (float) $order->driver->last_lat : null,
                'lng' => $order->driver->last_lng ? (float) $order->driver->last_lng : null,
                'heading' => $order->driver->last_heading ? (float) $order->driver->last_heading : null,
                'last_ping' => $order->driver->last_ping_at?->diffForHumans(),
            ] : null,
            'distance_km' => (float) $order->distance_km,
            'total_fee' => (float) $order->total_fee,
            'checkpoints' => $order->checkpoints->map(fn ($cp) => [
                'status' => $cp->status,
                'note' => $cp->note,
                'lat' => $cp->lat ? (float) $cp->lat : null,
                'lng' => $cp->lng ? (float) $cp->lng : null,
                'time' => $cp->recorded_at->format('d M Y, H:i'),
                'iso' => $cp->recorded_at->toIso8601String(),
            ]),
        ]);
    }
}
