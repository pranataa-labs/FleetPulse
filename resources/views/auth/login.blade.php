@extends('layouts.app', ['title' => 'Sign In — FleetPulse'])

@section('content')
<div class="max-w-md mx-auto px-4 py-12 space-y-6">
    <div class="text-center space-y-1.5">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900 text-white shadow-xs mx-auto mb-1">
            <i data-lucide="truck" class="w-5 h-5"></i>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke Sistem FleetPulse</h1>
        <p class="text-xs text-slate-500">Pilih peran akun Anda untuk membuka ruang kerja yang sesuai.</p>
    </div>

    <!-- Role Selection Tabs (Pilihan Peran Kerja) -->
    <div class="bg-slate-100 p-1 rounded-xl grid grid-cols-3 gap-1 text-xs font-semibold">
        <button type="button" onclick="selectRole('driver')" id="tab-driver" class="py-2 px-2 rounded-lg bg-white text-slate-900 shadow-xs transition flex flex-col items-center gap-1 border border-slate-200">
            <i data-lucide="smartphone" class="w-4 h-4 text-emerald-600"></i>
            <span>Kurir (Driver)</span>
        </button>

        <button type="button" onclick="selectRole('dispatcher')" id="tab-dispatcher" class="py-2 px-2 rounded-lg text-slate-600 hover:text-slate-900 transition flex flex-col items-center gap-1">
            <i data-lucide="map" class="w-4 h-4 text-blue-600"></i>
            <span>Dispatcher</span>
        </button>

        <button type="button" onclick="selectRole('admin')" id="tab-admin" class="py-2 px-2 rounded-lg text-slate-600 hover:text-slate-900 transition flex flex-col items-center gap-1">
            <i data-lucide="shield-check" class="w-4 h-4 text-amber-600"></i>
            <span>Admin</span>
        </button>
    </div>

    <!-- Role Explanation Banner -->
    <div id="roleExplanation" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-start gap-2.5">
        <i data-lucide="info" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
        <span id="roleText">
            <strong>Portal Kurir (Driver):</strong> Masuk ke aplikasi kurir untuk mengambil tugas pengantaran paket, kirim lokasi GPS, dan update status barang.
        </span>
    </div>

    <!-- Login Form -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Akun</label>
                <input type="email" id="email" name="email" value="{{ old('email', 'budi.driver@swiftexpress.com') }}" required autofocus
                       placeholder="nama@perusahaan.com"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 font-mono">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" id="password" name="password" value="password123" required
                       placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 font-mono">
            </div>

            <button type="submit" id="btnSubmit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs sm:text-sm transition shadow-xs flex items-center justify-center gap-2">
                <span>Masuk sebagai Kurir</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>
    </div>

    <div class="text-center">
        <a href="{{ route('home') }}" class="text-xs text-slate-500 hover:text-slate-800 font-medium inline-flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Kembali ke Halaman Lacak Resi Pelanggan</span>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function selectRole(role) {
    const tabDriver = document.getElementById('tab-driver');
    const tabDispatcher = document.getElementById('tab-dispatcher');
    const tabAdmin = document.getElementById('tab-admin');
    const emailInput = document.getElementById('email');
    const expBox = document.getElementById('roleExplanation');
    const roleText = document.getElementById('roleText');
    const btnSubmit = document.getElementById('btnSubmit');

    // Reset styles
    [tabDriver, tabDispatcher, tabAdmin].forEach(t => {
        t.className = "py-2 px-2 rounded-lg text-slate-600 hover:text-slate-900 transition flex flex-col items-center gap-1";
    });

    if (role === 'driver') {
        tabDriver.className = "py-2 px-2 rounded-lg bg-white text-slate-900 shadow-xs transition flex flex-col items-center gap-1 border border-slate-200 font-bold";
        emailInput.value = 'budi.driver@swiftexpress.com';
        expBox.className = "p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-start gap-2.5";
        roleText.innerHTML = "<strong>Portal Kurir (Driver):</strong> Masuk ke aplikasi kurir untuk mengambil tugas pengantaran paket, kirim lokasi GPS, dan update status barang.";
        btnSubmit.innerHTML = `<span>Masuk sebagai Kurir</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
    } else if (role === 'dispatcher') {
        tabDispatcher.className = "py-2 px-2 rounded-lg bg-white text-slate-900 shadow-xs transition flex flex-col items-center gap-1 border border-slate-200 font-bold";
        emailInput.value = 'dispatcher@swiftexpress.com';
        expBox.className = "p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-start gap-2.5";
        roleText.innerHTML = "<strong>Portal Tim Kantor (Dispatcher):</strong> Masuk ke ruang kontrol operasional untuk menginput kiriman baru dan memantau posisi seluruh armada di radar.";
        btnSubmit.innerHTML = `<span>Masuk sebagai Dispatcher</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
    } else if (role === 'admin') {
        tabAdmin.className = "py-2 px-2 rounded-lg bg-white text-slate-900 shadow-xs transition flex flex-col items-center gap-1 border border-slate-200 font-bold";
        emailInput.value = 'admin@swiftexpress.com';
        expBox.className = "p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5";
        roleText.innerHTML = "<strong>Portal Pimpinan (Admin):</strong> Masuk ke panel operasional perusahaan untuk mengatur tarif per KM/KG, armada, dan audit log harian.";
        btnSubmit.innerHTML = `<span>Masuk sebagai Admin</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
    }
    lucide.createIcons();
}
</script>
@endpush
