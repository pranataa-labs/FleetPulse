@extends('layouts.app', ['title' => 'Sign In — FleetPulse'])

@section('content')
<div class="max-w-md mx-auto px-4 py-16 space-y-6">
    <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900 text-white shadow-xs mx-auto mb-1">
            <i data-lucide="truck" class="w-5 h-5"></i>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sign in to FleetPulse</h1>
        <p class="text-xs text-slate-500">Access multi-tenant logistics portal &amp; fleet control room.</p>
    </div>

    <!-- Login Form -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="nama@perusahaan.com"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                </div>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" class="rounded text-slate-900 focus:ring-slate-900">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs sm:text-sm transition shadow-xs">
                Sign In
            </button>
        </form>
    </div>
</div>
@endsection
