@extends('layouts.app', ['title' => 'Driver Mobile Cockpit — FleetPulse'])

@section('content')
<div class="max-w-xl mx-auto px-4 py-6 space-y-5">
    <!-- Driver Header & Status -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-lg">
                    {{ substr($driver->user?->name ?? 'D', 0, 1) }}
                </div>
                <div>
                    <h2 class="font-bold text-slate-900 text-base leading-tight">{{ $driver->user?->name ?? 'Courier Driver' }}</h2>
                    <p class="text-xs text-slate-500">{{ $driver->fleet?->vehicle_type ?? 'MOTORCYCLE' }} &bullet; {{ $driver->fleet?->license_plate ?? 'B 3012 SFE' }}</p>
                </div>
            </div>
            <div class="text-right">
                <span id="dutyIndicator" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $driver->is_online ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                    <span class="w-2 h-2 rounded-full {{ $driver->is_online ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                    <span id="dutyStatusText">{{ $driver->is_online ? 'ONLINE' : 'OFFLINE' }}</span>
                </span>
            </div>
        </div>

        <!-- Action Bar: Duty Toggle & GPS Ping -->
        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
            <button onclick="toggleDutyStatus()" class="py-2.5 px-3 rounded-xl border border-slate-200 hover:bg-slate-50 font-medium text-xs text-slate-700 transition flex items-center justify-center gap-1.5">
                <i data-lucide="power" class="w-4 h-4 text-slate-500"></i>
                <span>Toggle Duty Status</span>
            </button>
            <button onclick="sendGpsPing()" id="btnGpsPing" class="py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 font-medium text-xs text-white transition flex items-center justify-center gap-1.5 shadow-sm">
                <i data-lucide="radio" class="w-4 h-4"></i>
                <span>Ping GPS (Simulasi)</span>
            </button>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
            <div>
                <span>Last Lat/Lng: </span>
                <span id="lastCoords" class="font-mono text-slate-700">{{ $driver->last_lat ?? '-6.1753' }}, {{ $driver->last_lng ?? '106.8271' }}</span>
            </div>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="autoPingCheckbox" onchange="toggleAutoPing(this)" class="rounded text-blue-600">
                <span>Auto Ping (3s)</span>
            </label>
        </div>
    </div>

    <!-- Active Delivery Assignment -->
    @if($activeOrder)
        <div class="bg-white rounded-2xl border-2 border-blue-500 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                    PESANAN AKTIF DITUGASKAN
                </span>
                <span class="font-mono text-xs font-semibold text-slate-900">#{{ $activeOrder->order_number }}</span>
            </div>

            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-400 font-semibold">Jemput:</span>
                    <p class="font-medium text-slate-800">{{ $activeOrder->pickup_address }}</p>
                </div>
                <div>
                    <span class="text-slate-400 font-semibold">Tujuan Antar:</span>
                    <p class="font-medium text-slate-800">{{ $activeOrder->dropoff_address }}</p>
                </div>
                <div class="flex justify-between text-slate-600 pt-1">
                    <span>Ongkir: Rp {{ number_format($activeOrder->total_fee, 0, ',', '.') }}</span>
                    <span>Status: <strong class="text-blue-600">{{ $activeOrder->delivery_status }}</strong></span>
                </div>
            </div>

            <!-- Dynamic Progressive Workflow Button (Step-by-Step) -->
            <div class="pt-2 border-t border-slate-100">
                @if($activeOrder->delivery_status === 'ACCEPTED')
                    <button onclick="updateOrderStatus({{ $activeOrder->id }}, 'PICKED_UP')" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm flex items-center justify-center gap-2">
                        <i data-lucide="package-check" class="w-4 h-4"></i>
                        <span>1. Konfirmasi Ambil Fisik Paket dari Pengirim</span>
                    </button>
                    <p class="text-[11px] text-slate-400 text-center mt-1.5">Tekan saat barang fisik sudah Anda terima dari tangan pengirim.</p>
                @elseif($activeOrder->delivery_status === 'PICKED_UP')
                    <button onclick="updateOrderStatus({{ $activeOrder->id }}, 'IN_TRANSIT')" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-sm flex items-center justify-center gap-2">
                        <i data-lucide="navigation" class="w-4 h-4"></i>
                        <span>2. Mulai Jalan Antar ke Alamat Penerima</span>
                    </button>
                    <p class="text-[11px] text-slate-400 text-center mt-1.5">Tekan saat Anda mulai berkendara menuju alamat tujuan.</p>
                @elseif($activeOrder->delivery_status === 'IN_TRANSIT')
                    <button onclick="updateOrderStatus({{ $activeOrder->id }}, 'DELIVERED')" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-sm flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>3. Selesai Antar (Serahkan ke Penerima)</span>
                    </button>
                    <p class="text-[11px] text-slate-400 text-center mt-1.5">Tekan saat paket telah sukses diserahkan ke penerima.</p>
                @else
                    <div class="py-2.5 px-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium text-center">
                        Pesanan ini berstatus <strong>{{ $activeOrder->delivery_status }}</strong>.
                    </div>
                @endif
            </div>
        </div>

        <!-- Obrolan Pengiriman Transparan (4-Pihak: Pengirim, Kurir, Dispatcher, Penerima) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-slate-900 text-xs">Obrolan Pengiriman Transparan</h3>
                        <p class="text-[10px] text-slate-500">Pengirim &bull; Kurir (Anda) &bull; Dispatcher &bull; Penerima</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-semibold border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live
                </span>
            </div>

            <!-- Message Stream -->
            <div id="driverChatStream" class="h-56 overflow-y-auto space-y-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <div class="text-center py-6 text-slate-400 text-xs">Memuat percakapan...</div>
            </div>

            <!-- Chat Input -->
            <form id="driverChatForm" onsubmit="sendDriverMessage(event)" class="flex gap-2">
                <input type="text" id="driverChatMessage" placeholder="Kirim pesan transparan ke semua pihak..." required
                       class="flex-1 px-3 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 bg-white">
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition shadow-xs flex items-center gap-1.5 shrink-0">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Kirim</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Available Dispatched Orders (Pool) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <h3 class="font-bold text-slate-900 text-sm">Pesanan Tersedia (Dispatched Pool)</h3>
            <span class="text-xs text-slate-500">{{ $availableOrders->count() }} pesanan</span>
        </div>

        <div class="space-y-3">
            @forelse($availableOrders as $order)
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono font-bold text-slate-900">#{{ $order->order_number }}</span>
                        <span class="font-semibold text-emerald-600">Rp {{ number_format($order->total_fee, 0, ',', '.') }}</span>
                    </div>
                    <div class="text-xs text-slate-600 space-y-1">
                        <div><strong class="text-slate-800">Dari:</strong> {{ $order->pickup_address }}</div>
                        <div><strong class="text-slate-800">Ke:</strong> {{ $order->dropoff_address }}</div>
                    </div>
                    <button onclick="claimOrder({{ $order->id }})" class="w-full py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs transition shadow-sm flex items-center justify-center gap-1.5">
                        <i data-lucide="package-plus" class="w-3.5 h-3.5"></i>
                        <span>Ambil Pesanan Ini</span>
                    </button>
                </div>
            @empty
                <div class="py-4 text-center text-xs text-slate-400">
                    Belum ada pesanan baru yang siap diambil di pool.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const driverId = {{ $driver->id }};
let isOnline = {{ $driver->is_online ? 'true' : 'false' }};
let currentLat = {{ (float) ($driver->last_lat ?? -6.1753924) }};
let currentLng = {{ (float) ($driver->last_lng ?? 106.8271528) }};
let autoPingInterval = null;

async function toggleDutyStatus() {
    isOnline = !isOnline;
    try {
        const res = await fetch('/api/driver/toggle-duty', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ driver_id: driverId, is_online: isOnline, is_on_duty: isOnline })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('dutyStatusText').textContent = data.is_online ? 'ONLINE' : 'OFFLINE';
            const ind = document.getElementById('dutyIndicator');
            if (data.is_online) {
                ind.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200';
            } else {
                ind.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500';
            }
        }
    } catch (e) {
        console.error(e);
    }
}

async function sendGpsPing() {
    // Add small step simulation (movement toward Senopati)
    currentLat -= 0.0008;
    currentLng -= 0.0004;

    try {
        const res = await fetch('/api/driver/location', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                driver_id: driverId,
                lat: currentLat,
                lng: currentLng,
                heading: 180.0,
                speed: 30.0
            })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('lastCoords').textContent = `${currentLat.toFixed(6)}, ${currentLng.toFixed(6)}`;
            const btn = document.getElementById('btnGpsPing');
            btn.classList.add('bg-emerald-600');
            setTimeout(() => btn.classList.remove('bg-emerald-600'), 500);
        }
    } catch (e) {
        console.error(e);
    }
}

function toggleAutoPing(checkbox) {
    if (checkbox.checked) {
        autoPingInterval = setInterval(sendGpsPing, 3000);
    } else {
        clearInterval(autoPingInterval);
    }
}

async function claimOrder(orderId) {
    try {
        const res = await fetch(`/api/driver/orders/${orderId}/claim`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ driver_id: driverId })
        });
        const data = await res.json();
        if (res.status === 200) {
            alert('Berhasil! Anda telah mengambil pesanan ini.');
            window.location.reload();
        } else if (res.status === 409) {
            alert('Pesanan sudah diambil oleh driver lain (Pessimistic lock protected)!');
            window.location.reload();
        } else {
            alert(data.message || 'Gagal mengambil pesanan');
        }
    } catch (e) {
        console.error(e);
    }
}

async function updateOrderStatus(orderId, status) {
    try {
        const res = await fetch(`/api/driver/orders/${orderId}/status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                driver_id: driverId,
                status: status,
                lat: currentLat,
                lng: currentLng
            })
        });
        const data = await res.json();
        if (data.success) {
            alert(`Status berhasil diubah ke ${status}`);
            window.location.reload();
        }
    } catch (e) {
        console.error(e);
    }
}

// Transparent 4-Party Chat Engine (Driver View)
const activeOrderId = {{ $activeOrder ? $activeOrder->id : 'null' }};
const currentDriverName = "{{ auth()->user()->name }}";

async function fetchChatMessages() {
    if (!activeOrderId) return;
    try {
        const res = await fetch(`/api/orders/${activeOrderId}/messages`);
        if (!res.ok) return;
        const data = await res.json();
        const stream = document.getElementById('driverChatStream');
        if (!stream) return;

        if (!data.messages || data.messages.length === 0) {
            stream.innerHTML = '<div class="text-center py-6 text-slate-400 text-xs">Belum ada obrolan. Ketik pesan untuk berkoordinasi.</div>';
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
            } else if (msg.sender_role === 'DRIVER') {
                html += `
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-tr-none bg-slate-900 text-white p-2.5 shadow-xs space-y-1">
                            <div class="flex items-center justify-between gap-3 text-[10px] text-slate-300">
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
        console.error('Driver chat poll error:', e);
    }
}

async function sendDriverMessage(e) {
    e.preventDefault();
    const input = document.getElementById('driverChatMessage');
    const msg = input.value.trim();
    if (!msg || !activeOrderId) return;

    try {
        const res = await fetch(`/api/orders/${activeOrderId}/messages`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                sender_role: 'DRIVER',
                sender_name: `${currentDriverName} (Kurir)`,
                message: msg
            })
        });
        if (res.ok) {
            input.value = '';
            fetchChatMessages();
        }
    } catch (err) {
        console.error('Driver send error:', err);
    }
}

if (activeOrderId) {
    fetchChatMessages();
    setInterval(fetchChatMessages, 3000);
}
</script>
@endpush
