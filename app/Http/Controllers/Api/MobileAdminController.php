<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAdminController extends Controller
{
    /**
     * Dashboard overview for Android Admin / Field Supervisor application.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');

        $driverQuery = Driver::query();
        $orderQuery = Order::withoutGlobalScope('tenant');

        if ($tenantId) {
            $driverQuery->where('tenant_id', $tenantId);
            $orderQuery->where('tenant_id', $tenantId);
        }

        $totalDrivers = (clone $driverQuery)->count();
        $onlineDrivers = (clone $driverQuery)->where('is_online', true)->count();
        $onDutyDrivers = (clone $driverQuery)->where('is_on_duty', true)->count();

        $activeDeliveries = (clone $orderQuery)->whereIn('delivery_status', ['ACCEPTED', 'PICKED_UP', 'IN_TRANSIT'])->count();
        $dispatchedWaiting = (clone $orderQuery)->where('delivery_status', 'DISPATCHED')->count();
        $completedToday = (clone $orderQuery)->where('delivery_status', 'DELIVERED')->whereDate('updated_at', today())->count();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_drivers' => $totalDrivers,
                'online_drivers' => $onlineDrivers,
                'on_duty_drivers' => $onDutyDrivers,
                'active_deliveries' => $activeDeliveries,
                'waiting_dispatch' => $dispatchedWaiting,
                'delivered_today' => $completedToday,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Real-time fleet telemetry radar for Android Admin application.
     */
    public function drivers(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');

        $query = Driver::with(['user', 'fleet'])->latest('last_ping_at');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $drivers = $query->get()->map(function ($driver) {
            $activeOrder = $driver->activeOrder();

            return [
                'id' => $driver->id,
                'name' => $driver->user?->name ?? 'Kurir #' . $driver->id,
                'phone' => $driver->user?->phone,
                'is_online' => (bool) $driver->is_online,
                'is_on_duty' => (bool) $driver->is_on_duty,
                'vehicle' => $driver->fleet ? [
                    'type' => $driver->fleet->vehicle_type,
                    'plate' => $driver->fleet->license_plate,
                ] : null,
                'location' => [
                    'lat' => $driver->last_lat ? (float) $driver->last_lat : null,
                    'lng' => $driver->last_lng ? (float) $driver->last_lng : null,
                    'heading' => $driver->last_heading ? (float) $driver->last_heading : null,
                    'last_ping' => $driver->last_ping_at?->diffForHumans(),
                ],
                'active_order' => $activeOrder ? [
                    'id' => $activeOrder->id,
                    'order_number' => $activeOrder->order_number,
                    'status' => $activeOrder->delivery_status,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $drivers->count(),
            'drivers' => $drivers,
        ]);
    }

    /**
     * Orders monitoring for Android Admin application.
     */
    public function orders(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $status = $request->query('status');

        $query = Order::withoutGlobalScope('tenant')->with(['tenant', 'driver.user'])->latest();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($status) {
            $query->where('delivery_status', $status);
        }

        $orders = $query->take(30)->get()->map(function ($o) {
            return [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'tracking_token' => $o->tracking_token,
                'tenant_name' => $o->tenant?->name,
                'courier_name' => $o->driver?->user?->name ?? 'Belum Ditugaskan',
                'delivery_status' => $o->delivery_status,
                'pickup_address' => $o->pickup_address,
                'dropoff_address' => $o->dropoff_address,
                'total_fee' => (float) $o->total_fee,
                'distance_km' => (float) $o->distance_km,
                'created_at' => $o->created_at?->format('d M Y, H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }
}
