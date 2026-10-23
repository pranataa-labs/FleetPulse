@extends('layouts.app', ['title' => 'FleetPulse — Multi-Tenant SaaS Delivery & Fleet Tracking'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">
    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
            Real-Time WebSockets &bullet; Multi-Tenant Scoped Architecture
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
            Multi-Tenant SaaS Logistics & Real-Time Fleet Tracking
        </h1>
        <p class="text-base sm:text-lg text-slate-600">
            Enterprise delivery management featuring sub-second GPS live streaming, automated spatial routing, pessimistic order locking, and scoped multi-tenancy.
        </p>

        <!-- Tracking Search Form -->
        <div class="pt-4 max-w-xl mx-auto">
            <form action="{{ url('/track') }}" method="GET" onsubmit="event.preventDefault(); window.location.href='/track/' + document.getElementById('trackingInput').value.trim();" class="flex gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-5 h-5 absolute left-3.5 top-3.5 text-slate-400"></i>
                    <input type="text" id="trackingInput" placeholder="Masukkan Tracking Token / Resi (UUID)..." required
                           value="{{ $activeOrders->first()?->tracking_token ?? '' }}"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-sm bg-white shadow-sm font-mono">
                </div>
                <button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm transition shadow-sm flex items-center gap-2">
                    <span>Lacak</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>
            <p class="text-xs text-slate-500 mt-2">
                Coba token demo di atas atau pilih pesanan aktif di bawah untuk melihat live map tracker.
            </p>
        </div>
    </div>

    <!-- Quick Role Demo Switcher Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-4">
            <div>
                <h3 class="font-semibold text-slate-900">1-Click Role Switcher (Simulasi Workflow)</h3>
                <p class="text-xs text-slate-500">Pilih salah satu peran untuk menguji otorisasi, proteksi route, dan workflow operasional.</p>
            </div>
            <a href="{{ route('login') }}" class="text-xs font-medium text-blue-600 hover:underline">Halaman Login &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('demo.switch', 1) }}" class="p-3 rounded-xl border border-slate-200 hover:border-slate-900 hover:bg-slate-50 transition text-left group">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Superadmin</div>
                <div class="font-semibold text-sm text-slate-900 group-hover:text-blue-600">Platform Ops</div>
                <div class="text-xs text-slate-500 mt-1">superadmin@fleetpulse.io</div>
            </a>
            <a href="{{ route('demo.switch', 2) }}" class="p-3 rounded-xl border border-slate-200 hover:border-slate-900 hover:bg-slate-50 transition text-left group">
                <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Tenant Admin</div>
                <div class="font-semibold text-sm text-slate-900 group-hover:text-blue-600">SwiftExpress Admin</div>
                <div class="text-xs text-slate-500 mt-1">admin@swiftexpress.com</div>
            </a>
            <a href="{{ route('demo.switch', 3) }}" class="p-3 rounded-xl border border-slate-200 hover:border-slate-900 hover:bg-slate-50 transition text-left group">
                <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Dispatcher</div>
                <div class="font-semibold text-sm text-slate-900 group-hover:text-blue-600">Live Control Center</div>
                <div class="text-xs text-slate-500 mt-1">dispatcher@swiftexpress.com</div>
            </a>
            <a href="{{ route('demo.switch', 5) }}" class="p-3 rounded-xl border border-slate-200 hover:border-slate-900 hover:bg-slate-50 transition text-left group">
                <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Courier / Driver</div>
                <div class="font-semibold text-sm text-slate-900 group-hover:text-blue-600">Budi Santoso (PWA)</div>
                <div class="text-xs text-slate-500 mt-1">budi.driver@swiftexpress.com</div>
            </a>
        </div>
    </div>

    <!-- Active Shipments Table -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Active Deliveries in System</h2>
                <p class="text-xs text-slate-500">Live parcel movements with cryptographic UUID tracking tokens.</p>
            </div>
            <a href="{{ route('dispatcher.index') }}" class="text-sm font-medium text-slate-900 hover:underline">
                View Dispatch Board &rarr;
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase font-semibold">
                        <tr>
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">Tenant</th>
                            <th class="py-3 px-4">Route (Origin &rarr; Dest)</th>
                            <th class="py-3 px-4">Vehicle</th>
                            <th class="py-3 px-4">Distance / Fee</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($activeOrders as $order)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-4 font-mono font-medium text-slate-900">
                                    {{ $order->order_number }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
                                        {{ $order->tenant->name }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-xs truncate">
                                    <span class="font-medium text-slate-800">{{ $order->pickup_address }}</span>
                                    <span class="text-slate-400 mx-1">&rarr;</span>
                                    <span>{{ $order->dropoff_address }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                        {{ $order->vehicle_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-xs font-medium text-slate-800">
                                    {{ $order->distance_km }} km &bullet; Rp {{ number_format($order->total_fee, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $statusColors = [
                                            'DRAFT' => 'bg-slate-100 text-slate-700',
                                            'DISPATCHED' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'ACCEPTED' => 'bg-sky-50 text-sky-800 border-sky-200',
                                            'PICKED_UP' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                            'IN_TRANSIT' => 'bg-blue-50 text-blue-800 border-blue-200 animate-pulse',
                                            'DELIVERED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                            'CANCELLED' => 'bg-rose-50 text-rose-800 border-rose-200',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusColors[$order->delivery_status] ?? 'bg-slate-100' }}">
                                        {{ $order->delivery_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('tracking.show', $order->tracking_token) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition shadow-sm">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span>Live Map</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Belum ada pesanan dalam sistem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
