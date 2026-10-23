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

            @php
                $senderWa = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $activeOrder->sender_phone ?? ''));
                $recipientWa = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $activeOrder->recipient_phone ?? ''));
            @endphp

            <!-- Kontak Langsung Manual: Toko (Pengirim) & Pembeli (Penerima) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                <!-- Kontak Pengirim / Penjual -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 flex items-center gap-1.5">
                            <i data-lucide="store" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>Pengirim (Penjual)</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $activeOrder->sender_phone }}</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-900">{{ $activeOrder->sender_name }}</p>
                        <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ $activeOrder->pickup_address }}</p>
                    </div>
                    <div class="flex gap-2 pt-1">
                        @if($senderWa)
                            <a href="https://wa.me/{{ $senderWa }}?text={{ urlencode('Halo ' . $activeOrder->sender_name . ', saya kurir FleetPulse akan menjemput paket #' . $activeOrder->order_number) }}" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-xs transition">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                <span>WhatsApp</span>
                            </a>
                        @endif
                        @if($activeOrder->sender_phone)
                            <a href="tel:{{ $activeOrder->sender_phone }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition">
                                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                <span>Telp</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Kontak Penerima / Pembeli -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 flex items-center gap-1.5">
                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>Penerima (Pembeli)</span>
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $activeOrder->recipient_phone }}</span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-900">{{ $activeOrder->recipient_name }}</p>
                        <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ $activeOrder->dropoff_address }}</p>
                    </div>
                    <div class="flex gap-2 pt-1">
                        @if($recipientWa)
                            <a href="https://wa.me/{{ $recipientWa }}?text={{ urlencode('Halo ' . $activeOrder->recipient_name . ', saya kurir FleetPulse sedang mengantarkan paket Anda #' . $activeOrder->order_number) }}" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-xs transition">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                <span>WhatsApp</span>
                            </a>
                        @endif
                        @if($activeOrder->recipient_phone)
                            <a href="tel:{{ $activeOrder->recipient_phone }}" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition">
                                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                <span>Telp</span>
                            </a>
                        @endif
                    </div>
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
</script>
@endpush
