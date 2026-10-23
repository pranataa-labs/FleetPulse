@extends('layouts.app', ['title' => 'Dispatcher Control Room — FleetPulse'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dispatcher Operations Center</h1>
            <p class="text-xs text-slate-500">Real-time fleet control, automated spatial dispatch, and delivery assignment queue.</p>
        </div>
        <button onclick="document.getElementById('newOrderModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm transition shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Create New Delivery Order</span>
        </button>
    </div>

    <!-- KPI Cards (Shadcn Style) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Active Fleets</span>
                <i data-lucide="truck" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-slate-900">{{ $activeFleetsCount }}</div>
            <div class="text-[11px] text-emerald-600 font-medium">&uarr; Ready for assignment</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Dispatched Orders</span>
                <i data-lucide="send" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-amber-600">{{ $dispatchedOrdersCount }}</div>
            <div class="text-[11px] text-slate-500">Awaiting driver claims</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>In-Transit Active</span>
                <i data-lucide="navigation" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-blue-600">{{ $inTransitCount }}</div>
            <div class="text-[11px] text-blue-600 font-medium">Live GPS streaming</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-1">
            <div class="text-xs font-medium text-slate-500 flex items-center justify-between">
                <span>Delivered Today</span>
                <i data-lucide="check-circle" class="w-4 h-4 text-slate-400"></i>
            </div>
            <div class="text-2xl font-bold text-emerald-600">{{ $deliveredTodayCount }}</div>
            <div class="text-[11px] text-slate-500">Fulfilled successfully</div>
        </div>
    </div>

    <!-- Live Fleet Map Canvas -->
    <div class="bg-white rounded-2xl border border-slate-200 p-3 shadow-sm space-y-2">
        <div class="px-3 pt-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="map" class="w-4 h-4 text-blue-600"></i>
                <span>Live Fleet Geolocation Radar</span>
            </div>
            <div class="text-xs text-slate-500">
                Auto-refreshes driver positions &bullet; Reverb WebSocket & Fallback Polling
            </div>
        </div>
        <div id="dispatchMap" class="w-full h-96 rounded-xl border border-slate-100 z-10"></div>
    </div>

    <!-- Orders Operations Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-900 text-sm">Dispatched & Active Deliveries</h2>
            <span class="text-xs text-slate-500">{{ $orders->total() }} total records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Sender / Pickup</th>
                        <th class="py-3 px-4">Recipient / Dropoff</th>
                        <th class="py-3 px-4">Driver</th>
                        <th class="py-3 px-4">Distance & Fee</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $o)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-mono font-medium text-slate-900">
                                {{ $o->order_number }}
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <div class="font-medium text-slate-800">{{ $o->sender_name }}</div>
                                <div class="text-slate-400 max-w-xs truncate">{{ $o->pickup_address }}</div>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <div class="font-medium text-slate-800">{{ $o->recipient_name }}</div>
                                <div class="text-slate-400 max-w-xs truncate">{{ $o->dropoff_address }}</div>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                @if($o->driver)
                                    <span class="font-medium text-slate-800">{{ $o->driver->user?->name }}</span>
                                    <span class="text-slate-400 block">{{ $o->driver->fleet?->license_plate }}</span>
                                @else
                                    <span class="text-slate-400 italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <div class="text-slate-500 font-medium whitespace-nowrap">{{ $o->distance_km }} km</div>
                                <div class="font-semibold text-slate-900 whitespace-nowrap">Rp {{ number_format($o->total_fee, 0, ',', '.') }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                    {{ $o->delivery_status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                @if($o->delivery_status === 'DRAFT')
                                    <form action="{{ route('dispatcher.orders.dispatch', $o->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition">
                                            Dispatch
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('tracking.show', $o->tracking_token) }}" target="_blank" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-medium transition inline-block">
                                    Track
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada data pengiriman.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $orders->links() }}
        </div>
    </div>
</div>

<!-- Modal Create Order -->
<div id="newOrderModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-base">New Delivery Order</h3>
            <button onclick="document.getElementById('newOrderModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="{{ route('dispatcher.orders.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="space-y-2">
                <div class="font-semibold text-slate-800">Sender Information</div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="sender_name" placeholder="Sender Name" value="PT Mitra Sejahtera" required class="p-2.5 rounded-lg border border-slate-300">
                    <input type="text" name="sender_phone" placeholder="Sender Phone" value="081299887766" required class="p-2.5 rounded-lg border border-slate-300">
                </div>
                <input type="text" name="pickup_address" placeholder="Pickup Address" value="Jl. Jenderal Sudirman Kav. 52, SCBD Jakarta" required class="w-full p-2.5 rounded-lg border border-slate-300">
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.0000001" name="pickup_lat" value="-6.2250000" placeholder="Pickup Lat" required class="p-2 rounded-lg border border-slate-300 font-mono">
                    <input type="number" step="0.0000001" name="pickup_lng" value="106.8090000" placeholder="Pickup Lng" required class="p-2 rounded-lg border border-slate-300 font-mono">
                </div>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-100">
                <div class="font-semibold text-slate-800">Recipient Information</div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="recipient_name" placeholder="Recipient Name" value="Dewi Lestari" required class="p-2.5 rounded-lg border border-slate-300">
                    <input type="text" name="recipient_phone" placeholder="Recipient Phone" value="081311223344" required class="p-2.5 rounded-lg border border-slate-300">
                </div>
                <input type="text" name="dropoff_address" placeholder="Dropoff Address" value="Jl. Kemang Raya No. 10, Jakarta Selatan" required class="w-full p-2.5 rounded-lg border border-slate-300">
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.0000001" name="dropoff_lat" value="-6.2600000" placeholder="Dropoff Lat" required class="p-2 rounded-lg border border-slate-300 font-mono">
                    <input type="number" step="0.0000001" name="dropoff_lng" value="106.8150000" placeholder="Dropoff Lng" required class="p-2 rounded-lg border border-slate-300 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Vehicle Type</label>
                    <select name="vehicle_type" class="w-full p-2.5 rounded-lg border border-slate-300 bg-white">
                        <option value="MOTORCYCLE">Motorcycle (Courier)</option>
                        <option value="VAN">Van</option>
                        <option value="BOX_TRUCK">Box Truck</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Weight (KG)</label>
                    <input type="number" step="0.1" name="weight_kg" value="2.0" class="w-full p-2.5 rounded-lg border border-slate-300">
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm transition">
                Create & Auto-Dispatch Order
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const dispatchMap = L.map('dispatchMap').setView([-6.2000000, 106.8166667], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | FleetPulse Dispatch'
    }).addTo(dispatchMap);

    const driverMarkers = {};

    function renderDriverMarkers(drivers) {
        drivers.forEach(d => {
            if (!d.lat || !d.lng) return;
            const key = d.id;
            const popupContent = `<b>${d.name}</b><br>Plate: ${d.license_plate}<br>Status: ${d.is_on_duty ? 'On-Duty' : 'Idle'}`;

            if (driverMarkers[key]) {
                driverMarkers[key].setLatLng([d.lat, d.lng]);
            } else {
                const markerIcon = L.divIcon({
                    className: 'custom-dispatcher-driver',
                    html: `<div style="background-color: ${d.is_online ? '#10b981' : '#64748b'}; color: white; border-radius: 9999px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 13px; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.3);">🛵</div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                driverMarkers[key] = L.marker([d.lat, d.lng], { icon: markerIcon }).addTo(dispatchMap).bindPopup(popupContent);
            }
        });
    }

    // Initial load & Polling
    async function fetchDrivers() {
        try {
            const res = await fetch('/dispatch/api/drivers');
            if (res.ok) {
                const data = await res.json();
                renderDriverMarkers(data.drivers || []);
            }
        } catch (e) {
            console.error('Error fetching drivers:', e);
        }
    }

    fetchDrivers();
    setInterval(fetchDrivers, 5000);
});
</script>
@endpush
