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
            
            <!-- Left: Brand Identity -->
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white shadow-xs group-hover:bg-slate-800 transition">
                        <i data-lucide="truck" class="w-4 h-4"></i>
                    </span>
                    <span class="font-bold text-slate-900 tracking-tight text-sm">FleetPulse</span>
                </a>
            </div>

            <!-- Center: Role-Scoped Navigation Links -->
            <nav class="hidden md:flex items-center gap-1">
                @guest
                    <!-- TAMU / PELANGGAN: Pelacakan Resi -->
                    <a href="{{ route('home') }}" 
                       class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors bg-slate-100 text-slate-900 font-semibold">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-blue-600"></i>
                        <span>Lacak Paket</span>
                    </a>
                @endguest

                @auth
                    @if(auth()->user()->isDriver())
                        <!-- KURIR: Driver Cockpit & Cek Resi -->
                        <a href="{{ route('driver.index') }}" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('driver.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Cockpit</span>
                        </a>
                        <a href="{{ route('home') }}" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('home') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            <span>Lacak</span>
                        </a>
                    @elseif(auth()->user()->isDispatcher())
                        <!-- DISPATCHER: Dispatch Center & Cek Resi -->
                        <a href="{{ route('dispatcher.index') }}" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('dispatcher.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            <i data-lucide="map" class="w-3.5 h-3.5 text-blue-600"></i>
                            <span>Dispatch</span>
                        </a>
                        <a href="{{ route('home') }}" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('home') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            <span>Lacak</span>
                        </a>
                    @elseif(auth()->user()->isSuperAdmin() || auth()->user()->isTenantAdmin())
                        <!-- ADMIN / OWNER: Dispatch, Admin & Cek Resi -->
                        <a href="{{ route('dispatcher.index') }}" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('dispatcher.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            <i data-lucide="map" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>Dispatch</span>
                        </a>
                        <a href="/{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}/dashboard" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->is(config('app.admin_prefix', 'fleetpulse-ops-mgmt').'*') ? 'bg-amber-100/80 text-amber-900 font-semibold border border-amber-300' : 'text-amber-800 bg-amber-50/60 hover:bg-amber-100 hover:text-amber-900 border border-amber-200' }}">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Admin</span>
                        </a>
                        <a href="{{ route('home') }}" 
                           class="h-8 px-3 rounded-md text-xs font-medium inline-flex items-center gap-1.5 transition-colors {{ request()->routeIs('home') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            <span>Lacak</span>
                        </a>
                    @endif
                @endauth
            </nav>

            <!-- Right: User / Auth Controls -->
            <div class="flex items-center gap-2 shrink-0">
                @auth
                    <!-- Unified User Profile & Logout Dropdown -->
                    <div class="relative" id="userDropdownWrapper">
                        <button type="button" 
                                id="userDropdownBtn"
                                onclick="toggleUserDropdown(event)"
                                class="h-8 pl-1 pr-2 rounded-full border border-slate-200 hover:border-slate-300 hover:bg-slate-100 flex items-center gap-1.5 transition-colors text-slate-700 bg-white shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <span class="w-6 h-6 rounded-full bg-slate-900 text-white text-[11px] font-bold flex items-center justify-center shrink-0">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </span>
                            <span class="text-xs font-medium text-slate-800 max-w-[90px] truncate hidden sm:inline">
                                {{ explode(' ', auth()->user()->name)[0] }}
                            </span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" id="userDropdownChevron"></i>
                        </button>

                        <!-- Floating Dropdown Menu (Shadcn Style) -->
                        <div id="userDropdownMenu" 
                             class="{{ request()->has('open_menu') ? '' : 'hidden' }} absolute right-0 mt-2 w-60 rounded-xl bg-white border border-slate-200 shadow-lg py-1.5 z-50 animate-in fade-in slide-in-from-top-1">
                            
                            <!-- Profile Header -->
                            <div class="px-3.5 py-2.5 border-b border-slate-100 space-y-1">
                                <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</div>
                                
                                <div class="pt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ auth()->user()->role }}
                                    </span>

                                    @if(auth()->user()->isDriver() && auth()->user()->driver)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i data-lucide="truck" class="w-2.5 h-2.5"></i>
                                            <span>{{ auth()->user()->driver->fleet?->license_plate ?? 'Kurir' }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Navigation Shortcuts -->
                            <div class="px-1.5 py-1 space-y-0.5">
                                @if(auth()->user()->isDriver())
                                    <a href="{{ route('driver.index') }}" class="w-full px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 flex items-center gap-2 transition">
                                        <i data-lucide="smartphone" class="w-3.5 h-3.5 text-blue-600"></i>
                                        <span>Driver Cockpit (PWA)</span>
                                    </a>
                                @endif

                                <a href="{{ route('dispatcher.index') }}" class="w-full px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 flex items-center gap-2 transition">
                                    <i data-lucide="map" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Dispatch Center</span>
                                </a>

                                @if(auth()->user()->isSuperAdmin() || auth()->user()->isTenantAdmin())
                                    <a href="/{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}/dashboard" class="w-full px-2.5 py-1.5 rounded-lg text-xs font-medium text-amber-800 hover:bg-amber-50 flex items-center gap-2 transition">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-amber-600"></i>
                                        <span>Operations Admin</span>
                                    </a>
                                @endif
                            </div>

                            <!-- Logout Action -->
                            <div class="pt-1 border-t border-slate-100 px-1.5">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 hover:bg-rose-50 flex items-center gap-2 transition text-left">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-rose-600"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
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

        <!-- Mobile Collapsible Menu (Role Scoped) -->
        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1">
            @guest
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-xs font-medium bg-slate-100 text-slate-900 font-semibold">
                    Lacak Paket
                </a>
            @endguest

            @auth
                @if(auth()->user()->isDriver())
                    <a href="{{ route('driver.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200">
                        Cockpit
                    </a>
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Lacak
                    </a>
                @elseif(auth()->user()->isDispatcher())
                    <a href="{{ route('dispatcher.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium bg-blue-50 text-blue-800 font-semibold border border-blue-200">
                        Dispatch
                    </a>
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Lacak
                    </a>
                @elseif(auth()->user()->isSuperAdmin() || auth()->user()->isTenantAdmin())
                    <a href="{{ route('dispatcher.index') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Dispatch
                    </a>
                    <a href="/{{ config('app.admin_prefix', 'fleetpulse-ops-mgmt') }}/dashboard" class="block px-3 py-2 rounded-md text-xs font-medium text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200">
                        Admin
                    </a>
                    <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Lacak
                    </a>
                @endif

                <div class="pt-2 mt-2 border-t border-slate-100 space-y-2">
                    <div class="px-3 py-1.5 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-slate-400">{{ auth()->user()->role }}</div>
                        </div>
                        @if(auth()->user()->isDriver() && auth()->user()->driver)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold border border-blue-200">
                                {{ auth()->user()->driver->fleet?->license_plate ?? 'Kurir' }}
                            </span>
                        @endif
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-xs font-medium text-rose-600 hover:bg-rose-50 flex items-center gap-2">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
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

        function toggleUserDropdown(event) {
            if (event) event.stopPropagation();
            const menu = document.getElementById('userDropdownMenu');
            const chevron = document.getElementById('userDropdownChevron');
            if (menu) {
                const isHidden = menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                if (chevron) {
                    chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const wrapper = document.getElementById('userDropdownWrapper');
            const menu = document.getElementById('userDropdownMenu');
            const chevron = document.getElementById('userDropdownChevron');
            if (wrapper && menu && !wrapper.contains(event.target)) {
                menu.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const menu = document.getElementById('userDropdownMenu');
                const chevron = document.getElementById('userDropdownChevron');
                if (menu && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                }
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
