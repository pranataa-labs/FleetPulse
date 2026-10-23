@extends('layouts.app', ['title' => 'Sign In — FleetPulse'])

@section('content')
<div class="max-w-md mx-auto px-4 py-16 space-y-6">
    <div class="text-center space-y-2">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to FleetPulse</h1>
        <p class="text-sm text-slate-500">Access multi-tenant logistics portal & fleet control room.</p>
    </div>

    <!-- 1-Click Demo Accounts -->
    <div class="bg-blue-50/60 rounded-xl border border-blue-200 p-4 space-y-3">
        <div class="text-xs font-semibold text-blue-900 flex items-center justify-between">
            <span>⚡ 1-Click Fast Login (Demo Accounts)</span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <a href="{{ route('demo.switch', 1) }}" class="p-2.5 rounded-lg bg-white border border-blue-200 hover:border-blue-400 font-medium text-slate-800 transition">
                <div class="font-bold text-slate-900">Superadmin</div>
                <div class="text-slate-500 text-[10px] truncate">superadmin@fleetpulse.io</div>
            </a>
            <a href="{{ route('demo.switch', 2) }}" class="p-2.5 rounded-lg bg-white border border-blue-200 hover:border-blue-400 font-medium text-slate-800 transition">
                <div class="font-bold text-blue-700">Swift Admin</div>
                <div class="text-slate-500 text-[10px] truncate">admin@swiftexpress.com</div>
            </a>
            <a href="{{ route('demo.switch', 3) }}" class="p-2.5 rounded-lg bg-white border border-blue-200 hover:border-blue-400 font-medium text-slate-800 transition">
                <div class="font-bold text-emerald-700">Dispatcher</div>
                <div class="text-slate-500 text-[10px] truncate">dispatcher@swiftexpress.com</div>
            </a>
            <a href="{{ route('demo.switch', 5) }}" class="p-2.5 rounded-lg bg-white border border-blue-200 hover:border-blue-400 font-medium text-slate-800 transition">
                <div class="font-bold text-amber-700">Courier Driver</div>
                <div class="text-slate-500 text-[10px] truncate">budi.driver@swiftexpress.com</div>
            </a>
        </div>
    </div>

    <!-- Login Form -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', 'admin@swiftexpress.com') }}" required
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" id="password" name="password" value="password123" required
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm transition shadow-sm">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection
