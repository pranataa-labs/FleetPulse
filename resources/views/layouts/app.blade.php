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
    <!-- Navigation Bar (Shadcn Proportional Header) -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-[0_1px_3px_0_rgba(0,0,0,0.02)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between gap-4">
            
            <!-- Left: Brand & Tenant Identity -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white shadow-xs group-hover:bg-slate-800 transition">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                    </span>
                    <span class="font-bold text-slate-900 tracking-tight text-sm">FleetPulse</span>
                </a>

                <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

                @if(isset($currentTenant))
                    <div class="hidden sm:flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-100/80 border border-slate-200/80 text-xs font-medium text-slate-700 max-w-[200px]" title="{{ $currentTenant->name }}">
                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                        <span class="truncate">{{ $currentTenant->name }}</span>
                    </div>
                @endif
            </div>

            <!-- Center: Navigation Links (Desktop) -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('home') }}" 
                   class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('home') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                    <i data-lucide="home" class="w-3.5 h-3.5"></i>
                    <span>Home</span>
                </a>

                <a href="{{ route('dispatcher.index') }}" 
                   class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('dispatcher.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                    <i data-lucide="map" class="w-3.5 h-3.5"></i>
                    <span>Dispatch Center</span>
                </a>

                <a href="{{ route('driver.index') }}" 
                   class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('driver.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                    <span>Driver Cockpit</span>
                </a>

                @auth
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isTenantAdmin())
                        <a href="/{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}/dashboard" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->is(config('app.admin_prefix', 'fleetpulse-ops-mgmt').'*') ? 'bg-amber-100/80 text-amber-900 font-semibold border border-amber-300' : 'text-amber-800 bg-amber-50/60 hover:bg-amber-100 hover:text-amber-900 border border-amber-200' }}">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Operations Admin</span>
                        </a>
                    @endif
                @endauth
            </nav>

            <!-- Right: User / Auth Controls -->
            <div class="flex items-center gap-2 shrink-0">
                @auth
                    <div class="hidden sm:flex items-center gap-2.5 pl-2 border-l border-slate-200/80">
                        <div class="w-7 h-7 rounded-full bg-slate-900 text-white text-xs font-semibold flex items-center justify-center shrink-0 shadow-xs">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="text-left hidden lg:block">
                            <div class="text-xs font-semibold text-slate-800 leading-none truncate max-w-[120px]">{{ auth()->user()->name }}</div>
                            <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">{{ auth()->user()->role }}</span>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                title="Sign Out"
                                class="h-8 px-2.5 rounded-md border border-slate-200 hover:bg-slate-100 text-xs font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" 
                       class="h-8 px-3 rounded-md bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition shadow-xs inline-flex items-center gap-1.5">
                        <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                        <span>Sign In</span>
                    </a>
                @endauth

                <!-- Mobile Hamburger Button -->
                <button type="button" 
                        onclick="toggleMobileMenu()" 
                        class="md:hidden h-8 w-8 rounded-md border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 hover:text-slate-900 transition">
                    <i data-lucide="menu" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Collapsible Menu -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1">
            @if(isset($currentTenant))
                <div class="flex items-center gap-1.5 px-3 py-2 rounded-md bg-slate-50 text-xs font-medium text-slate-700 mb-2 border border-slate-200">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Tenant: {{ $currentTenant->name }}</span>
                </div>
            @endif

            <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-xs font-medium {{ request()->routeIs('home') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                Home
            </a>
            <a href="{{ route('dispatcher.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium {{ request()->routeIs('dispatcher.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                Dispatch Center
            </a>
            <a href="{{ route('driver.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium {{ request()->routeIs('driver.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                Driver Cockpit
            </a>

            @auth
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isTenantAdmin())
                    <a href="/{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}/dashboard" class="block px-3 py-2 rounded-md text-xs font-medium text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200">
                        Operations Admin
                    </a>
                @endif
            @endauth
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
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
