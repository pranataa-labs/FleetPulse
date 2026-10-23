@extends('layouts.app', ['title' => 'Operations Admin Dashboard — FleetPulse'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-600"></i>
                <span>Obfuscated Admin Operations Panel: /{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-1">Platform Operations & SaaS Control</h1>
            <p class="text-xs text-slate-500">Global tenant provisioning, fleet capacity management, rate cards, and live audit telemetry.</p>
        </div>
        <div class="text-xs text-slate-500 bg-white border border-slate-200 p-2.5 rounded-xl shadow-sm">
            <span>Logged in as: <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})</span>
        </div>
    </div>

    <!-- Platform KPI Overview (Shadcn KPI Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Active Tenants</span>
                <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-slate-900">{{ $tenants->count() }}</div>
            <div class="text-[11px] text-emerald-600 font-medium">100% Isolated Data Scopes</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Total Fleet Units</span>
                <i data-lucide="truck" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-slate-900">{{ $totalFleets }}</div>
            <div class="text-[11px] text-slate-500">{{ $activeDrivers }} drivers online</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Total Orders Processed</span>
                <i data-lucide="package-check" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-blue-600">{{ $totalOrders }}</div>
            <div class="text-[11px] text-blue-600 font-medium">UUID Token Guarded</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Platform Revenue</span>
                <i data-lucide="banknote" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-emerald-600">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500">Collected transactions</div>
        </div>
    </div>

    <!-- Tenants & Multi-Tenancy Architecture Overview -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-sm">Tenant Organizations & Subscription Tiers</h2>
                <p class="text-xs text-slate-500">Each tenant has isolated orders, drivers, fleets, and rate card configurations.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-4">Organization</th>
                        <th class="py-3 px-4">Subdomain / Domain</th>
                        <th class="py-3 px-4">Tier</th>
                        <th class="py-3 px-4">Fleets</th>
                        <th class="py-3 px-4">Drivers</th>
                        <th class="py-3 px-4">Orders</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($tenants as $t)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900 text-xs">{{ $t->name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ $t->uuid }}</div>
                            </td>
                            <td class="py-3 px-4 text-xs font-mono text-slate-700">
                                {{ $t->slug }}.fleetpulse.test
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 uppercase">
                                    {{ $t->plan_tier }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-800">{{ $t->fleets_count }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-800">{{ $t->drivers_count }}</td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-800">{{ $t->orders_count }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $t->subscription_status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rate Card & Dynamic Pricing Matrix -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-100">
            <h2 class="font-bold text-slate-900 text-sm">Dynamic Logistics Pricing Matrix (Rate Cards)</h2>
            <p class="text-xs text-slate-500">Formula: Base Fare + (Distance &times; Rate/km) + (Weight &times; Rate/kg) &times; Surge</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-4">Tenant</th>
                        <th class="py-3 px-4">Vehicle Type</th>
                        <th class="py-3 px-4">Base Fare</th>
                        <th class="py-3 px-4">Rate / KM</th>
                        <th class="py-3 px-4">Rate / KG</th>
                        <th class="py-3 px-4">Surge Multiplier</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($pricingRules as $rule)
                        <tr class="hover:bg-slate-50/80 transition">
                            <form action="{{ route('admin.pricing.update', $rule->id) }}" method="POST">
                                @csrf
                                <td class="py-3 px-4 text-xs font-medium text-slate-900">
                                    {{ $rule->tenant->name }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-800">
                                        {{ $rule->vehicle_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" step="100" name="base_fare" value="{{ (int)$rule->base_fare }}" class="w-24 p-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" step="100" name="rate_per_km" value="{{ (int)$rule->rate_per_km }}" class="w-24 p-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" step="100" name="rate_per_kg" value="{{ (int)$rule->rate_per_kg }}" class="w-24 p-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" step="0.1" name="surge_multiplier" value="{{ (float)$rule->surge_multiplier }}" class="w-16 p-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition">
                                        Update
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- PZN Daily Rotating Logs Telemetry Viewer -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i data-lucide="terminal" class="w-4 h-4 text-slate-600"></i>
                    <span>Daily Rotating Telemetry Logs (Pak Eko PZN Standard)</span>
                </h3>
                <p class="text-xs text-slate-500">File: storage/logs/{{ $todayLogFilename }} (Retention: 14 Days)</p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-mono font-medium border border-emerald-200">
                LOG_CHANNEL=daily
            </span>
        </div>

        <div class="bg-slate-950 text-slate-100 p-4 rounded-xl font-mono text-xs overflow-x-auto max-h-64 space-y-1">
            @forelse($logLines as $line)
                <div class="leading-relaxed hover:bg-slate-900 p-0.5 rounded">
                    {{ $line }}
                </div>
            @empty
                <div class="text-slate-500">Log file is ready and will stream GPS pings, order dispatches, and audit events.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
