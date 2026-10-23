<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverMobileController extends Controller
{
    /**
     * Driver Mobile PWA Cockpit.
     */
    public function index(Request $request): View
    {
        // For convenience in testing/demo, resolve first driver or driver passed via ?driver_id=X
        $driverId = $request->query('driver_id');
        $driver = null;

        if ($driverId) {
            $driver = Driver::withoutGlobalScope('tenant')->with(['user', 'fleet', 'tenant'])->find($driverId);
        }

        if (! $driver) {
            $driver = Driver::withoutGlobalScope('tenant')->with(['user', 'fleet', 'tenant'])->first();
        }

        $activeOrder = $driver ? $driver->activeOrder() : null;

        // Dispatched orders available to claim
        $availableOrders = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $driver?->tenant_id ?? 1)
            ->where('delivery_status', 'DISPATCHED')
            ->latest()
            ->take(5)
            ->get();

        $recentDelivered = Order::withoutGlobalScope('tenant')
            ->where('driver_id', $driver?->id)
            ->where('delivery_status', 'DELIVERED')
            ->latest()
            ->take(5)
            ->get();

        return view('driver.index', compact('driver', 'activeOrder', 'availableOrders', 'recentDelivered'));
    }
}
