@extends('layouts.app', ['title' => 'Lacak Paket — FleetPulse Official Parcel Tracking'])

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-12 sm:space-y-16">
    
    <!-- Flash Notification (Error / Info) -->
    @if(session('error'))
        <div class="max-w-2xl mx-auto p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-start gap-3 shadow-xs">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
            <div>
                <p class="font-semibold">Pelacakan Gagal</p>
                <p class="mt-0.5 text-rose-700 leading-relaxed">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Hero Section: Pure Tracking Input -->
    <div class="text-center max-w-2xl mx-auto space-y-6">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-slate-800 text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Portal Resmi Pelacakan Paket &bull; Real-Time GPS Tracking</span>
        </div>
        
        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
            Lacak Posisi &amp; Perjalanan Paket Anda
        </h1>
        
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl mx-auto">
            Pantau pergerakan kurir secara transparan dan akurat langsung di peta interaktif. Cukup masukkan nomor resi atau token pengiriman Anda.
        </p>

        <!-- Tracking Lookup Form -->
        <div class="pt-2">
            <form action="{{ route('tracking.lookup') }}" method="GET" class="flex flex-col sm:flex-row gap-2 max-w-xl mx-auto">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-3.5 text-slate-400"></i>
                    <input type="text" 
                           name="query" 
                           id="trackingInput" 
                           placeholder="Masukkan Nomor Resi (misal: {{ $sampleOrder?->order_number ?? 'FP-20261024-001' }})..." 
                           required
                           value="{{ request('query') }}"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 text-xs sm:text-sm bg-white shadow-xs font-mono">
                </div>
                <button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs sm:text-sm transition shadow-xs flex items-center justify-center gap-2 shrink-0">
                    <span>Lacak Paket</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>

            @if($sampleOrder)
                <div class="mt-3 flex items-center justify-center gap-2 text-[11px] text-slate-500 flex-wrap">
                    <span>Contoh nomor resi aktif:</span>
                    <button type="button" 
                            onclick="document.getElementById('trackingInput').value = '{{ $sampleOrder->order_number }}'"
                            class="font-mono font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 px-2 py-0.5 rounded border border-slate-200 transition">
                        #{{ $sampleOrder->order_number }}
                    </button>
                    @if($sampleOrder->tracking_token)
                        <button type="button" 
                                onclick="document.getElementById('trackingInput').value = '{{ $sampleOrder->tracking_token }}'"
                                class="font-mono text-slate-500 hover:text-slate-800 underline transition truncate max-w-[140px]">
                            {{ substr($sampleOrder->tracking_token, 0, 13) }}...
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- 3-Step Visual Tracking Guide -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-6">
        <div class="text-center max-w-lg mx-auto">
            <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Cara Mudah Melacak Pengiriman</h2>
            <p class="text-xs text-slate-500 mt-1">Hanya 3 langkah sederhana untuk memastikan paket Anda sampai ke tujuan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    1
                </div>
                <div class="space-y-1">
                    <h3 class="font-semibold text-slate-900 text-sm">Masukkan Nomor Resi</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Ketik nomor resi pengiriman yang diberikan oleh penjual atau toko pengirim pada kolom pencarian di atas.
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    2
                </div>
                <div class="space-y-1">
                    <h3 class="font-semibold text-slate-900 text-sm">Pantau Posisi Kurir</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Sistem menampilkan peta interaktif dengan koordinat kurir real-time dan tahapan riwayat checkpoint paket.
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                    3
                </div>
                <div class="space-y-1">
                    <h3 class="font-semibold text-slate-900 text-sm">Terima Paket Aman</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Anda dapat langsung menghubungi kurir via WhatsApp atau panggilan suara saat kurir tiba di alamat Anda.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking Key Highlights -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="radio" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Telemetri GPS Real-Time</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Pemantauan posisi armada logistik dengan siaran GPS berlatensi rendah untuk akurasi lokasi terbaik.
            </p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Privasi &amp; Keamanan Data</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Informasi rute dan paket dilindungi dengan token terenkripsi sehingga hanya pihak penerima yang dapat melacak.
            </p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i data-lucide="phone-call" class="w-4 h-4"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-xs sm:text-sm">Kontak Langsung 1-Tap</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Terhubung langsung ke kurir melalui WhatsApp atau panggilan telepon tanpa perantara saat paket diantar.
            </p>
        </div>
    </div>

    <!-- Frequently Asked Questions (FAQ) -->
    <div class="bg-slate-50 rounded-2xl border border-slate-200/90 p-6 sm:p-8 space-y-5">
        <h3 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i data-lucide="help-circle" class="w-4 h-4 text-slate-400"></i>
            <span>Pertanyaan Umum Seputar Pelacakan (FAQ)</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-1">
                <p class="font-semibold text-slate-800">Di mana saya bisa mendapatkan nomor resi pengiriman?</p>
                <p class="text-slate-500 leading-relaxed">
                    Nomor resi pengiriman (contoh: FP-...) diberikan oleh penjual atau toko setelah pesanan Anda diproses dan ditugaskan ke kurir.
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-1">
                <p class="font-semibold text-slate-800">Kapan posisi kurir mulai terlihat di peta?</p>
                <p class="text-slate-500 leading-relaxed">
                    Posisi kurir akan tampil aktif bergerak di peta segera setelah kurir mengonfirmasi pengambilan paket fisik dari pihak pengirim.
                </p>
            </div>
        </div>
    </div>

    <!-- Footer Notice -->
    <div class="text-center pt-4 text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} FleetPulse. Layanan Pelacakan Paket Resmi Multi-Armada.</p>
    </div>
</div>
@endsection
