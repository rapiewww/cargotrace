<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CargoTrace — Global Shipment Visibility & Tracking Platform' }}</title>

    <!-- Google / Bunny Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|inter:400,500,600,700" rel="stylesheet" />

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Leaflet JS & Chart.js -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @stack('styles')
</head>
<body class="bg-[#050B14] text-slate-100 font-sans antialiased min-h-full flex flex-col selection:bg-amber-400 selection:text-slate-950">
    <!-- Top Navigation Bar (Strict Enterprise Glassmorphism) -->
    <header class="sticky top-0 z-50 bg-[#0B1728]/85 backdrop-blur-xl border-b border-white/10 shadow-lg">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-6 lg:space-x-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group transition-transform duration-200 hover:scale-[1.02]">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 via-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25 group-hover:shadow-amber-500/40 transition">
                            <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-lg font-bold tracking-tight text-white">CargoTrace</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">Global</span>
                        </div>
                    </a>

                    <!-- Navigation Tabs (Enterprise Pill Style) -->
                    <nav class="hidden md:flex items-center space-x-1.5">
                        <a href="{{ route('dashboard') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('shipments.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('shipments.*') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            Shipments
                        </a>

                        @if(in_array(auth()->user()->role, ['admin', 'operator']))
                            <a href="{{ route('operations.exceptions') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.exceptions') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Exceptions
                            </a>
                            <a href="{{ route('operations.carriers') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.carriers') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Carriers
                            </a>
                            <a href="{{ route('operations.locations') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.locations') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Global Ports
                            </a>
                        @endif

                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('operations.audit-logs') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.audit-logs') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Audit Logs
                            </a>
                        @endif
                    </nav>
                </div>

                <!-- Right Utility Icons & User Menu -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Public Website Link -->
                    <a href="{{ route('welcome') }}" target="_blank" title="View Public Portal" class="hidden sm:inline-flex items-center text-xs text-slate-400 hover:text-amber-400 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Public Portal
                    </a>

                    <!-- Notifications Dropdown -->
                    @php
                        $unreadNotifications = auth()->user()->notifications()->whereNull('read_at')->latest()->take(5)->get();
                        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
                    @endphp
                    <div class="relative">
                        <button id="notif-btn" onclick="document.getElementById('notif-menu').classList.toggle('hidden')" class="relative p-2 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 border border-white/5 transition-all duration-200 hover:scale-[1.05]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                                </span>
                            @endif
                        </button>

                        <div id="notif-menu" class="hidden absolute right-0 mt-3 w-80 sm:w-96 rounded-2xl bg-[#0B1728]/95 backdrop-blur-2xl border border-white/10 shadow-2xl p-4 z-50">
                            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Live Alerts ({{ $unreadCount }})</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] text-teal-400 hover:underline font-medium">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="divide-y divide-white/5 max-h-72 overflow-y-auto mt-2">
                                @forelse($unreadNotifications as $notif)
                                    <div class="py-2.5 space-y-1">
                                        <div class="flex items-center justify-between text-xs font-semibold text-white">
                                            <span>{{ $notif->title }}</span>
                                            <span class="text-[10px] text-slate-500">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400 leading-relaxed">{{ $notif->message }}</p>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-xs text-slate-500">No unread alerts</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- User Pill & Logout -->
                    <div class="flex items-center space-x-3 pl-3 border-l border-white/10">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-teal-500 to-cyan-400 flex items-center justify-center text-xs font-bold text-slate-950 ring-2 ring-teal-400/30">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-xs font-semibold text-white leading-tight">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-amber-400 uppercase tracking-wider font-semibold">{{ auth()->user()->role }}</div>
                            </div>
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" title="Logout" class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-white/5 transition-all duration-200 hover:scale-[1.05]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- App Body with Floating Left Icon Bar -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Floating Left Sidebar Icon Bar -->
        <aside class="hidden lg:flex flex-col items-center justify-between w-14 py-6 bg-[#07101D]/90 backdrop-blur-md border-r border-white/10 z-40">
            <div class="flex flex-col items-center space-y-3.5">
                <a href="{{ route('dashboard') }}" title="Dashboard Overview" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-[1.08] {{ request()->routeIs('dashboard') ? 'bg-teal-400/20 text-teal-300 border border-teal-400/30 shadow-lg shadow-teal-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </a>
                <a href="{{ route('shipments.index') }}" title="Shipments Manifest" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-[1.08] {{ request()->routeIs('shipments.*') ? 'bg-teal-400/20 text-teal-300 border border-teal-400/30 shadow-lg shadow-teal-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </a>
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <a href="{{ route('operations.exceptions') }}" title="Operational Exceptions" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-[1.08] {{ request()->routeIs('operations.exceptions') ? 'bg-teal-400/20 text-teal-300 border border-teal-400/30 shadow-lg shadow-teal-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </a>
                    <a href="{{ route('operations.carriers') }}" title="Carriers Fleet" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-[1.08] {{ request()->routeIs('operations.carriers') ? 'bg-teal-400/20 text-teal-300 border border-teal-400/30 shadow-lg shadow-teal-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </a>
                    <a href="{{ route('operations.locations') }}" title="Global Ports Network" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-[1.08] {{ request()->routeIs('operations.locations') ? 'bg-teal-400/20 text-teal-300 border border-teal-400/30 shadow-lg shadow-teal-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </a>
                @endif
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('operations.audit-logs') }}" title="Audit Logs" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-[1.08] {{ request()->routeIs('operations.audit-logs') ? 'bg-teal-400/20 text-teal-300 border border-teal-400/30 shadow-lg shadow-teal-500/10' : 'text-slate-400 hover:text-white hover:bg-white/5 border border-transparent' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                @endif
            </div>

            <!-- Bottom Logout Icon -->
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" title="Logout" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-all duration-200 hover:scale-[1.08]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </aside>

        <!-- Main Workspace Area -->
        <main class="flex-1 overflow-y-auto">
            <!-- Flash Notifications -->
            @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between shadow-xl backdrop-blur-xl">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="p-4 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-center justify-between shadow-xl backdrop-blur-xl">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-white">&times;</button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
