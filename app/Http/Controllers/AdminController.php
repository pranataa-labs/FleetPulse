<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Obfuscated Admin Dashboard.
     */
    public function dashboard(): View
    {
        $tenants = Tenant::withCount(['fleets', 'drivers', 'orders'])->get();
        $totalOrders = Order::withoutGlobalScope('tenant')->count();
        $totalRevenue = Order::withoutGlobalScope('tenant')->where('payment_status', 'PAID')->sum('total_fee');
        $activeDrivers = Driver::withoutGlobalScope('tenant')->where('is_online', true)->count();
        $totalFleets = Fleet::withoutGlobalScope('tenant')->count();

        $pricingRules = PricingRule::withoutGlobalScope('tenant')->with('tenant')->get();

        // Read today's daily log file (PZN standard)
        $todayLogFilename = 'laravel-' . date('Y-m-d') . '.log';
        $logPath = storage_path('logs/' . $todayLogFilename);
        $logLines = [];

        if (File::exists($logPath)) {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_slice($lines, -30); // Last 30 lines
        }

        return view('admin.dashboard', compact(
            'tenants',
            'totalOrders',
            'totalRevenue',
            'activeDrivers',
            'totalFleets',
            'pricingRules',
            'todayLogFilename',
            'logLines'
        ));
    }

    /**
     * Update pricing rate card rule.
     */
    public function updatePricingRule(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'base_fare' => 'required|numeric|min:0',
            'rate_per_km' => 'required|numeric|min:0',
            'rate_per_kg' => 'required|numeric|min:0',
            'surge_multiplier' => 'required|numeric|min:0.5|max:5',
        ]);

        $rule = PricingRule::withoutGlobalScope('tenant')->findOrFail($id);
        $rule->update($validated);

        Log::info('Pricing rule updated by admin', [
            'rule_id' => $rule->id,
            'tenant_id' => $rule->tenant_id,
            'vehicle_type' => $rule->vehicle_type,
            'updates' => $validated,
        ]);

        return redirect()->back()->with('success', "Tarif untuk {$rule->vehicle_type} berhasil diperbarui.");
    }
}
