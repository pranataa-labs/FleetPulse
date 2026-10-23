<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'FleetPulse' }} — Multi-Tenant Logistics & Live Fleet Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN Fallback & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Leaflet.js GIS Mapping Library -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Pusher / Reverb WebSockets Client -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .leaflet-container { font-family: 'Inter', sans-serif; border-radius: 0.75rem; }
    </style>
    @stack('styles')
</head>
<body class="min-h-full flex flex-col bg-slate-50 text-slate-900">
    <!-- Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-slate-900 tracking-tight text-lg">
                    <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-slate-900 text-white shadow-sm">
                        <i data-lucide="truck" class="w-5 h-5"></i>
                    </span>
                    <span>FleetPulse</span>
                    @if(isset($currentTenant))
                        <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                            {{ $currentTenant->name }}
                        </span>
                    @endif
                </a>

                <nav class="hidden md:flex items-center gap-1 text-sm font-medium text-slate-600">
                    <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-lg hover:text-slate-900 hover:bg-slate-100 transition-colors">
                        Home
                    </a>
                    <a href="{{ route('dispatcher.index') }}" class="px-3 py-1.5 rounded-lg hover:text-slate-900 hover:bg-slate-100 transition-colors">
                        Dispatch Center
                    </a>
                    <a href="{{ route('driver.index') }}" class="px-3 py-1.5 rounded-lg hover:text-slate-900 hover:bg-slate-100 transition-colors">
                        Driver Cockpit
                    </a>
                    @auth
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isTenantAdmin())
                            <a href="/{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}/dashboard" class="px-3 py-1.5 rounded-lg text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors font-medium border border-amber-200">
                                Operations Admin
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <div class="flex items-center gap-2 text-sm text-slate-700 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ auth()->user()->name }}</span>
                        <span class="text-xs text-slate-500 px-1.5 py-0.5 rounded bg-slate-100">
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 font-medium text-slate-600 transition">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white shadow-sm transition">
                        Sign In / Demo
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-6 mt-12 text-sm text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-800">FleetPulse</span>
                <span>— Multi-Tenant Logistics & Live Fleet Tracking SaaS</span>
            </div>
            <div class="text-xs text-slate-400">
                PZN Standard Daily Rotating Logging (14 Days) &bullet; Obfuscated Admin Protection
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
