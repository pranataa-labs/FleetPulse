@extends('layouts.app', ['title' => "Live Tracking #{$order->order_number} — FleetPulse"])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header Details -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                    {{ $order->tenant->name }}
                </span>
                <span class="text-xs font-mono text-slate-400">UUID: {{ $order->tracking_token }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 mt-1">
                Tracking Order #{{ $order->order_number }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Estimasi rute: {{ $order->distance_km }} km &bullet; Tarif Total: Rp {{ number_format($order->total_fee, 0, ',', '.') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-right">
                <div class="text-xs text-slate-500">Status Pengiriman</div>
                <div id="statusBadge" class="text-sm font-bold text-blue-600 animate-pulse">
                    {{ $order->delivery_status }}
                </div>
            </div>
            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
        </div>
    </div>

    <!-- Main Grid: Map (Left 65%) + Order Details & Timeline (Right 35%) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Live Leaflet Map -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-2 shadow-sm flex flex-col">
            <div class="p-3 flex items-center justify-between border-b border-slate-100">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="navigation" class="w-4 h-4 text-blue-600"></i>
                    <span>Real-Time GPS Tracking Map</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span id="socketStatus" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Live Stream Active
                    </span>
                </div>
            </div>

            <!-- Leaflet Map Canvas -->
            <div id="liveMap" class="w-full h-[520px] rounded-xl z-10 mt-2"></div>
        </div>

        <!-- Sidebar: Timeline & Courier Info -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Courier Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Informasi Kurir</div>
                @if($order->driver)
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            {{ substr($order->driver->user?->name ?? 'D', 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 leading-tight">{{ $order->driver->user?->name ?? 'Courier' }}</h4>
                            <p class="text-xs text-slate-500">{{ $order->driver->fleet?->vehicle_type ?? 'MOTORCYCLE' }} &bullet; {{ $order->driver->fleet?->license_plate ?? 'B 3012 SFE' }}</p>
                            <p class="text-xs text-emerald-600 font-medium mt-0.5">{{ $order->driver->user?->phone ?? '-' }}</p>
                        </div>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                        Pesanan sedang dalam proses penugasan ke kurir terdekat.
                    </div>
                @endif

                <!-- Addresses -->
                <div class="space-y-3 pt-2 border-t border-slate-100 text-xs">
                    <div>
                        <div class="font-semibold text-slate-500">Titik Jemput (Origin):</div>
                        <div class="text-slate-800 font-medium mt-0.5">{{ $order->pickup_address }}</div>
                        <div class="text-slate-400 text-[11px]">{{ $order->sender_name }} ({{ $order->sender_phone }})</div>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-500">Titik Antar (Destination):</div>
                        <div class="text-slate-800 font-medium mt-0.5">{{ $order->dropoff_address }}</div>
                        <div class="text-slate-400 text-[11px]">{{ $order->recipient_name }} ({{ $order->recipient_phone }})</div>
                    </div>
                </div>
            </div>

            <!-- Tracking Checkpoints Timeline -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Riwayat Perjalanan (Checkpoints)</div>
                <div class="space-y-4 relative pl-4 border-l-2 border-slate-200" id="checkpointTimeline">
                    @forelse($order->checkpoints as $cp)
                        <div class="relative">
                            <span class="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-slate-900 border-2 border-white"></span>
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-xs text-slate-900">{{ $cp->status }}</span>
                                <span class="text-[10px] text-slate-400">{{ $cp->recorded_at->format('H:i') }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5">{{ $cp->note }}</p>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400">Belum ada checkpoint tercatat.</div>
                    @endforelse
                </div>
            </div>

            <!-- Obrolan Transparan Pengiriman (4-Pihak) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-100 text-blue-700">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Obrolan Pengiriman Transparan</h3>
                            <p class="text-[10px] text-slate-500">Pengirim &bull; Kurir &bull; Dispatcher &bull; Penerima</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-semibold border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live
                    </span>
                </div>

                <!-- Messages Stream -->
                <div id="trackingChatStream" class="h-60 overflow-y-auto space-y-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div class="text-center py-6 text-slate-400 text-xs">Memuat obrolan transparan...</div>
                </div>

                <!-- Chat Sender Role Selection & Input -->
                <form id="trackingChatForm" onsubmit="sendTrackingMessage(event)" class="space-y-2">
                    <div class="flex items-center gap-3 text-xs bg-slate-50 p-2 rounded-lg border border-slate-200">
                        <span class="text-slate-500 text-[11px] font-medium shrink-0">Kirim Sebagai:</span>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer text-[11px] text-slate-700 font-medium">
                            <input type="radio" name="chat_sender_role" value="SENDER" class="text-slate-900 focus:ring-0">
                            <span>Pengirim ({{ $order->sender_name }})</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer text-[11px] text-slate-700 font-medium">
                            <input type="radio" name="chat_sender_role" value="RECIPIENT" checked class="text-slate-900 focus:ring-0">
                            <span>Penerima ({{ $order->recipient_name }})</span>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="trackingChatMessage" placeholder="Tulis pesan ke kurir &amp; dispatcher..." required
                               class="flex-1 px-3 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white">
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition shadow-xs flex items-center gap-1.5 shrink-0">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            <span>Kirim</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const pickupLat = {{ (float) $order->pickup_lat }};
    const pickupLng = {{ (float) $order->pickup_lng }};
    const dropoffLat = {{ (float) $order->dropoff_lat }};
    const dropoffLng = {{ (float) $order->dropoff_lng }};
    let driverLat = {{ (float) ($order->driver?->last_lat ?? $order->pickup_lat) }};
    let driverLng = {{ (float) ($order->driver?->last_lng ?? $order->pickup_lng) }};

    // 1. Initialize Map
    const map = L.map('liveMap').setView([pickupLat, pickupLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors | FleetPulse Spatial Engine'
    }).addTo(map);

    // 2. Custom Icons
    const pickupIcon = L.divIcon({
        className: 'custom-pin-pickup',
        html: `<div style="background-color: #10b981; color: white; border-radius: 9999px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); border: 2px solid white;">📦</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    const dropoffIcon = L.divIcon({
        className: 'custom-pin-dropoff',
        html: `<div style="background-color: #ef4444; color: white; border-radius: 9999px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); border: 2px solid white;">🏁</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    const driverIcon = L.divIcon({
        className: 'custom-pin-driver',
        html: `<div id="driverMarkerElement" style="background-color: #2563eb; color: white; border-radius: 9999px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 10px 15px -3px rgba(37,99,235,0.4); border: 3px solid white; transition: all 0.6s ease;">🛵</div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

    // 3. Place Markers
    L.marker([pickupLat, pickupLng], { icon: pickupIcon }).addTo(map).bindPopup("<b>Titik Jemput (Origin)</b><br>{{ $order->pickup_address }}");
    L.marker([dropoffLat, dropoffLng], { icon: dropoffIcon }).addTo(map).bindPopup("<b>Titik Antar (Destination)</b><br>{{ $order->dropoff_address }}");

    const driverMarker = L.marker([driverLat, driverLng], { icon: driverIcon }).addTo(map)
        .bindPopup("<b>Kurir FleetPulse</b><br>{{ $order->driver?->user?->name ?? 'Kurir' }}");

    // 4. Draw Route Polyline
    const routeLine = L.polyline([
        [pickupLat, pickupLng],
        [driverLat, driverLng],
        [dropoffLat, dropoffLng]
    ], {
        color: '#3b82f6',
        weight: 4,
        opacity: 0.7,
        dashArray: '8, 8'
    }).addTo(map);

    // Fit bounds to show both points
    const bounds = L.latLngBounds([
        [pickupLat, pickupLng],
        [dropoffLat, dropoffLng],
        [driverLat, driverLng]
    ]);
    map.fitBounds(bounds, { padding: [50, 50] });

    // Function to smoothly animate driver marker
    function updateDriverLocation(lat, lng, heading) {
        driverMarker.setLatLng([lat, lng]);
        routeLine.setLatLngs([
            [pickupLat, pickupLng],
            [lat, lng],
            [dropoffLat, dropoffLng]
        ]);
        if (heading) {
            const el = document.getElementById('driverMarkerElement');
            if (el) el.style.transform = `rotate(${heading}deg)`;
        }
    }

    // 5. Connect to Reverb / Pusher WebSockets
    const trackingToken = "{{ $order->tracking_token }}";
    try {
        const pusherKey = "{{ env('REVERB_APP_KEY', 'qd4vy0399kdjzcecok6u') }}";
        const pusherHost = "{{ env('REVERB_HOST', 'localhost') }}";
        const pusherPort = {{ (int) env('REVERB_PORT', 8080) }};

        const pusher = new Pusher(pusherKey, {
            wsHost: pusherHost,
            wsPort: pusherPort,
            wssPort: pusherPort,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            cluster: 'mt1'
        });

        const channel = pusher.subscribe(`order.${trackingToken}`);
        channel.bind('driver.location.updated', function(data) {
            console.log('Live GPS packet received via WebSocket:', data);
            updateDriverLocation(data.lat, data.lng, data.heading);
            const badge = document.getElementById('socketStatus');
            if (badge) {
                badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Live GPS Streamed`;
            }
        });
    } catch (e) {
        console.warn('Reverb WebSocket fallback:', e);
    }

    // 6. Polling Fallback (ensures smooth real-time update in all environments)
    setInterval(async () => {
        try {
            const res = await fetch(`/api/track/${trackingToken}`);
            if (res.ok) {
                const data = await res.json();
                if (data.courier && data.courier.lat && data.courier.lng) {
                    updateDriverLocation(data.courier.lat, data.courier.lng, data.courier.heading);
                }
                const statusEl = document.getElementById('statusBadge');
                if (statusEl) statusEl.textContent = data.delivery_status;
            }
        } catch (err) {
            console.error('Polling error:', err);
        }
    }, 4000);
    // 7. Transparent 4-Party Chat Engine (Public Tracking View)
    const trackingOrderId = {{ $order->id }};
    const senderCustomerName = "{{ $order->sender_name }}";
    const recipientCustomerName = "{{ $order->recipient_name }}";

    async function fetchTrackingChat() {
        try {
            const res = await fetch(`/api/orders/${trackingOrderId}/messages`);
            if (!res.ok) return;
            const data = await res.json();
            const stream = document.getElementById('trackingChatStream');
            if (!stream) return;

            if (!data.messages || data.messages.length === 0) {
                stream.innerHTML = '<div class="text-center py-6 text-slate-400 text-xs">Belum ada percakapan. Kirim pesan untuk menyapa kurir & dispatcher.</div>';
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
                            <span class="inline-block px-3 py-1 rounded-full bg-slate-200/90 border border-slate-300 text-[10px] text-slate-700 font-medium max-w-xs">
                                ℹ️ ${msg.message} <span class="text-slate-400 text-[9px] ml-1">${msg.created_at}</span>
                            </span>
                        </div>`;
                } else {
                    html += `
                        <div class="flex justify-start">
                            <div class="max-w-[85%] rounded-2xl bg-white border border-slate-200 p-2.5 shadow-xs space-y-1">
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
            console.error('Tracking chat poll error:', e);
        }
    }

    window.sendTrackingMessage = async function(e) {
        e.preventDefault();
        const input = document.getElementById('trackingChatMessage');
        const msg = input.value.trim();
        if (!msg) return;

        const roleRadio = document.querySelector('input[name="chat_sender_role"]:checked');
        const role = roleRadio ? roleRadio.value : 'RECIPIENT';
        const senderName = role === 'SENDER' 
            ? `${senderCustomerName} (Pengirim)`
            : `${recipientCustomerName} (Penerima)`;

        try {
            const res = await fetch(`/api/orders/${trackingOrderId}/messages`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    sender_role: role,
                    sender_name: senderName,
                    message: msg
                })
            });
            if (res.ok) {
                input.value = '';
                fetchTrackingChat();
            }
        } catch (err) {
            console.error('Tracking send message error:', err);
        }
    };

    fetchTrackingChat();
    setInterval(fetchTrackingChat, 3000);
});
</script>
@endpush
