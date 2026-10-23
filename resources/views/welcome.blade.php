@extends('layouts.app', ['title' => 'FleetPulse — Multi-Tenant SaaS Delivery & Real-Time Fleet Tracking'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-16">
    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto space-y-5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-800 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Enterprise Logistics Platform &bullet; Live Telemetry
        </div>
        
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
            Multi-Tenant Logistics &amp; Real-Time Fleet Tracking
        </h1>
        
        <p class="text-base sm:text-lg text-slate-600">
            High-precision parcel delivery management with sub-second WebSocket GPS telemetry, automated spatial routing, and robust multi-tenant data isolation.
        </p>

        <!-- Tracking Search Form -->
        <div class="pt-2 max-w-xl mx-auto">
            <form action="{{ url('/track') }}" method="GET" onsubmit="event.preventDefault(); const val = document.getElementById('trackingInput').value.trim(); if(val) window.location.href='/track/' + val;" class="flex gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-3.5 text-slate-400"></i>
                    <input type="text" id="trackingInput" placeholder="Masukkan Kode Resi / Tracking Token (UUID)..." required
                           value="{{ $activeOrders->first()?->tracking_token ?? '' }}"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-xs sm:text-sm bg-white shadow-xs font-mono">
                </div>
                <button type="submit" class="px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs sm:text-sm transition shadow-xs flex items-center gap-2 shrink-0">
                    <span>Lacak Paket</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>
            <p class="text-[11px] text-slate-500 mt-2">
                Lacak posisi kurir dan status perjalanan pengiriman Anda secara langsung di peta interaktif.
            </p>
        </div>
    </div>

    <!-- Core Platform Capabilities (SaaS Feature Highlights) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs space-y-2">
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="radio" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Real-Time WebSockets</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                Siaran koordinat GPS kurir dengan latensi sub-detik melalui Laravel Reverb tanpa lag dan polling berlebih.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs space-y-2">
            <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="building-2" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Multi-Tenant Scoped</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                Isolasi data 100% aman untuk setiap perusahaan logistik dengan Global Eloquent Scopes dan middleware subdomain.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs space-y-2">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="compass" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Haversine Spatial Engine</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                Kalkulasi jarak lengkung bumi otomatis dan estimasi ongkos kirim dinamis berdasarkan massa kargo dan jenis armada.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-xs space-y-2">
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="lock" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Pessimistic Concurrency</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                Proteksi klaim pesanan kurir dengan row-level lock database (`lockForUpdate`) untuk eliminasi double-assignment.
            </p>
        </div>
    </div>

    <!-- Active Shipments Monitoring Table -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Active Shipments &amp; Live Tracking</h2>
                <p class="text-xs text-slate-500">Daftar pergerakan paket aktif dalam jaringan logistik.</p>
            </div>
            <a href="{{ route('dispatcher.index') }}" class="text-xs font-semibold text-slate-900 hover:underline inline-flex items-center gap-1">
                <span>Dispatch Center</span>
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/80 border-b border-slate-200 text-xs text-slate-500 uppercase font-semibold">
                        <tr>
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">Tenant</th>
                            <th class="py-3 px-4">Rute (Origin &rarr; Dest)</th>
                            <th class="py-3 px-4">Armada</th>
                            <th class="py-3 px-4">Jarak &amp; Tarif</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($activeOrders as $order)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-900 text-xs">
                                    {{ $order->order_number }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-slate-100 text-slate-700">
                                        {{ $order->tenant->name }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 max-w-xs truncate">
                                    <span class="font-medium text-slate-800">{{ $order->pickup_address }}</span>
                                    <span class="text-slate-400 mx-1">&rarr;</span>
                                    <span>{{ $order->dropoff_address }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                        {{ $order->vehicle_type }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-medium text-slate-800">
                                    {{ $order->distance_km }} km &bullet; Rp {{ number_format($order->total_fee, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $statusColors = [
                                            'DRAFT' => 'bg-slate-100 text-slate-700',
                                            'DISPATCHED' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'ACCEPTED' => 'bg-sky-50 text-sky-800 border-sky-200',
                                            'PICKED_UP' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                            'IN_TRANSIT' => 'bg-blue-50 text-blue-800 border-blue-200',
                                            'DELIVERED' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                            'CANCELLED' => 'bg-rose-50 text-rose-800 border-rose-200',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ $statusColors[$order->delivery_status] ?? 'bg-slate-100' }}">
                                        {{ $order->delivery_status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('tracking.show', $order->tracking_token) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition shadow-xs">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <span>Live Map</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
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
