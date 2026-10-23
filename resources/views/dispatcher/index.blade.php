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
            <span>Buat Order Pengiriman Baru</span>
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
                                <button type="button" onclick="openDispatcherChat({{ $o->id }}, '{{ $o->order_number }}')" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-medium transition inline-flex items-center gap-1">
                                    <i data-lucide="message-square" class="w-3 h-3 text-blue-600"></i>
                                    <span>Chat</span>
                                </button>
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
<div id="newOrderModal" class="{{ request()->has('open_modal') ? '' : 'hidden' }} fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Buat Pesanan Pengiriman Baru</h3>
                <p class="text-xs text-slate-500">Lengkapi data pengirim, penerima, dan armada untuk penugasan kurir otomatis.</p>
            </div>
            <button onclick="document.getElementById('newOrderModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form action="{{ route('dispatcher.orders.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            
            <!-- Bagian 1: Pengirim -->
            <div class="space-y-3 p-3.5 rounded-xl bg-slate-50/80 border border-slate-200/80">
                <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-blue-600"></i>
                    <span>1. Informasi Pengirim (Lokasi Penjemputan)</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-medium text-slate-700 text-[11px] mb-1">Nama Pengirim / Toko</label>
                        <input type="text" name="sender_name" placeholder="Contoh: PT Mitra Sejahtera" value="PT Mitra Sejahtera" required class="w-full p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500 text-xs">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 text-[11px] mb-1">No. HP / WhatsApp Pengirim</label>
                        <input type="text" name="sender_phone" placeholder="Contoh: 081299887766" value="081299887766" required class="w-full p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 text-[11px] mb-1">Alamat Lengkap Penjemputan Paket</label>
                    <input type="text" name="pickup_address" placeholder="Nama jalan, gedung, nomor kavling..." value="Jl. Jenderal Sudirman Kav. 52, SCBD Jakarta" required class="w-full p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500 text-xs">
                    <input type="hidden" name="pickup_lat" value="-6.2250000">
                    <input type="hidden" name="pickup_lng" value="106.8090000">
                </div>
            </div>

            <!-- Bagian 2: Penerima -->
            <div class="space-y-3 p-3.5 rounded-xl bg-slate-50/80 border border-slate-200/80">
                <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs">
                    <i data-lucide="navigation" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>2. Informasi Penerima (Lokasi Pengantaran)</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-medium text-slate-700 text-[11px] mb-1">Nama Penerima Paket</label>
                        <input type="text" name="recipient_name" placeholder="Contoh: Dewi Lestari" value="Dewi Lestari" required class="w-full p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500 text-xs">
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 text-[11px] mb-1">No. HP / WhatsApp Penerima</label>
                        <input type="text" name="recipient_phone" placeholder="Contoh: 081311223344" value="081311223344" required class="w-full p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-slate-700 text-[11px] mb-1">Alamat Lengkap Pengantaran Paket</label>
                    <input type="text" name="dropoff_address" placeholder="Nama jalan, nomor rumah/kantor..." value="Jl. Kemang Raya No. 10, Jakarta Selatan" required class="w-full p-2.5 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500 text-xs">
                    <input type="hidden" name="dropoff_lat" value="-6.2600000">
                    <input type="hidden" name="dropoff_lng" value="106.8150000">
                </div>
            </div>

            <!-- Bagian 3: Armada & Kargo -->
            <div class="space-y-3 p-3.5 rounded-xl bg-slate-50/80 border border-slate-200/80">
                <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs">
                    <i data-lucide="package" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span>3. Jenis Armada &amp; Beban Paket</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-medium text-slate-700 text-[11px] mb-1">Pilihan Kendaraan / Armada</label>
                        <select name="vehicle_type" class="w-full p-2.5 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500">
                            <option value="MOTORCYCLE">Sepeda Motor (Kurir Standar)</option>
                            <option value="VAN">Mobil Van (Barang Sedang)</option>
                            <option value="BOX_TRUCK">Truk Box (Muatan Besar / Kargo)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-slate-700 text-[11px] mb-1">Perkiraan Berat Kargo (KG)</label>
                        <input type="number" step="0.1" name="weight_kg" value="2.0" class="w-full p-2.5 rounded-lg border border-slate-300 bg-white text-xs focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-500">
                    </div>
                </div>
                <div class="text-[11px] text-slate-500 flex items-center gap-1">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 shrink-0"></i>
                    <span>Jarak tempuh (Haversine km) dan ongkos kirim total akan dikalkulasi otomatis oleh sistem saat disimpan.</span>
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs transition shadow-sm flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-4 h-4"></i>
                <span>Simpan Pesanan &amp; Siarkan ke Kurir (Auto-Dispatch)</span>
            </button>
        </form>
    </div>
</div>

<!-- Modal Dispatcher 4-Party Transparent Chat -->
<div id="dispatcherChatModal" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-5 shadow-xl space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                </span>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Obrolan Pengiriman Transparan</h3>
                    <p class="text-[11px] text-slate-500" id="chatOrderSubtitle">Order #...</p>
                </div>
            </div>
            <button onclick="closeDispatcherChat()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="text-[11px] text-slate-500 bg-slate-50 p-2 rounded-lg border border-slate-200 flex items-center gap-1.5">
            <i data-lucide="info" class="w-3.5 h-3.5 text-blue-600 shrink-0"></i>
            <span>Pesan yang dikirimkan di sini dapat dilihat secara transparan oleh Pengirim, Kurir, Dispatcher, dan Penerima.</span>
        </div>

        <!-- Chat Stream -->
        <div id="dispatcherChatStream" class="h-64 overflow-y-auto space-y-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
            <div class="text-center py-6 text-slate-400">Memuat obrolan...</div>
        </div>

        <!-- Chat Form -->
        <form onsubmit="sendDispatcherMessage(event)" class="flex gap-2">
            <input type="text" id="dispatcherChatMessage" placeholder="Kirim instruksi / update ke kurir, pengirim &amp; penerima..." required
                   class="flex-1 px-3 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition shadow-xs flex items-center gap-1.5 shrink-0">
                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                <span>Kirim</span>
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentChatOrderId = null;
let chatPollInterval = null;
const dispatcherName = "{{ auth()->user()->name }} (Dispatcher)";

function openDispatcherChat(orderId, orderNumber) {
    currentChatOrderId = orderId;
    document.getElementById('chatOrderSubtitle').textContent = `Order #${orderNumber} • Koordinasi Lapangan`;
    document.getElementById('dispatcherChatModal').classList.remove('hidden');
    fetchDispatcherChat();
    if (chatPollInterval) clearInterval(chatPollInterval);
    chatPollInterval = setInterval(fetchDispatcherChat, 3000);
}

function closeDispatcherChat() {
    document.getElementById('dispatcherChatModal').classList.add('hidden');
    if (chatPollInterval) clearInterval(chatPollInterval);
    currentChatOrderId = null;
}

async function fetchDispatcherChat() {
    if (!currentChatOrderId) return;
    try {
        const res = await fetch(`/api/orders/${currentChatOrderId}/messages`);
        if (!res.ok) return;
        const data = await res.json();
        const stream = document.getElementById('dispatcherChatStream');
        if (!stream) return;

        if (!data.messages || data.messages.length === 0) {
            stream.innerHTML = '<div class="text-center py-6 text-slate-400 text-xs">Belum ada percakapan untuk order ini.</div>';
            return;
        }

        const roleBadges = {
            'SYSTEM': '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 text-slate-700">SISTEM</span>',
            'DRIVER': '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">KURIR</span>',
            'DISPATCHER': '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800">DISPATCHER</span>',
            'SENDER': '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">PENGIRIM</span>',
            'RECIPIENT': '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-800">PENERIMA</span>'
        };

        let html = '';
        data.messages.forEach(msg => {
            if (msg.is_system) {
                html += `
                    <div class="text-center my-2">
                        <span class="inline-block px-3 py-1 rounded-full bg-slate-200/90 border border-slate-300 text-[10px] text-slate-700 font-medium max-w-sm">
                            ℹ️ ${msg.message} <span class="text-slate-400 text-[9px] ml-1">${msg.created_at}</span>
                        </span>
                    </div>`;
            } else if (msg.sender_role === 'DISPATCHER') {
                html += `
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-tr-none bg-blue-600 text-white p-2.5 shadow-xs space-y-1">
                            <div class="flex items-center justify-between gap-3 text-[10px] text-blue-100">
                                <span class="font-semibold">${msg.sender_name}</span>
                                <span>${msg.created_at}</span>
                            </div>
                            <p class="text-xs leading-relaxed">${msg.message}</p>
                        </div>
                    </div>`;
            } else {
                html += `
                    <div class="flex justify-start">
                        <div class="max-w-[85%] rounded-2xl rounded-tl-none bg-white border border-slate-200 p-2.5 shadow-xs space-y-1">
                            <div class="flex items-center justify-between gap-3 text-[10px]">
                                <span class="flex items-center gap-1 font-semibold text-slate-800">
                                    ${roleBadges[msg.sender_role] || ''}
                                    ${msg.sender_name}
                                </span>
                                <span class="text-slate-400">${msg.created_at}</span>
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed">${msg.message}</p>
                        </div>
                    </div>`;
            }
        });

        const shouldScroll = stream.scrollHeight - stream.clientHeight <= stream.scrollTop + 60;
        stream.innerHTML = html;
        if (shouldScroll) {
            stream.scrollTop = stream.scrollHeight;
        }
    } catch (e) {
        console.error('Dispatcher chat error:', e);
    }
}

async function sendDispatcherMessage(e) {
    e.preventDefault();
    const input = document.getElementById('dispatcherChatMessage');
    const msg = input.value.trim();
    if (!msg || !currentChatOrderId) return;

    try {
        const res = await fetch(`/api/orders/${currentChatOrderId}/messages`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                sender_role: 'DISPATCHER',
                sender_name: dispatcherName,
                message: msg
            })
        });
        if (res.ok) {
            input.value = '';
            fetchDispatcherChat();
        }
    } catch (err) {
        console.error('Dispatcher send message error:', err);
    }
}

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
