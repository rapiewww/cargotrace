<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CargoTrace — Global Shipment Visibility Platform' }}</title>

    <!-- Scripts & Styles via Vite (Plus Jakarta Sans & JetBrains Mono bundled locally) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-[#050B14] text-slate-100 font-sans antialiased min-h-full flex flex-col selection:bg-amber-400 selection:text-slate-950">
    <!-- Single Unified Top Navigation (Full Width 1600px, Strict Enterprise) -->
    <header class="sticky top-0 z-50 bg-[#0B1728]/90 backdrop-blur-xl border-b border-white/10 shadow-xl">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18">
                <!-- Brand & Nav Links -->
                <div class="flex items-center space-x-6 xl:space-x-10">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group transition-transform duration-200 hover:scale-[1.02]">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 via-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25 group-hover:shadow-amber-500/40 transition">
                            <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xl font-extrabold tracking-tight text-white font-sans">CargoTrace</span>
                                <span class="text-[9px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20 font-mono">Global</span>
                            </div>
                            <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold block leading-none mt-0.5">Visibility Platform</span>
                        </div>
                    </a>

                    <!-- Navigation Links (Enterprise Pill Style) -->
                    <nav class="hidden lg:flex items-center space-x-1.5">
                        <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('shipments.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('shipments.*') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                            Shipments
                        </a>

                        @if(in_array(auth()->user()->role, ['admin', 'operator']))
                            <a href="{{ route('operations.exceptions') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.exceptions') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Exceptions
                            </a>
                            <a href="{{ route('operations.carriers') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.carriers') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Carriers
                            </a>
                            <a href="{{ route('operations.locations') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.locations') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Global Ports
                            </a>
                        @endif

                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('operations.audit-logs') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('operations.audit-logs') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                Audit Logs
                            </a>
                            @if(Route::has('admin.users.index'))
                                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                    Users
                                </a>
                            @endif
                            @if(Route::has('admin.settings'))
                                <a href="{{ route('admin.settings') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold tracking-wide transition-all duration-200 {{ request()->routeIs('admin.settings') ? 'bg-teal-500/20 text-teal-300 border border-teal-400/30 shadow-inner' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                                    Settings
                                </a>
                            @endif
                        @endif
                    </nav>
                </div>

                <!-- Right Utilities & Profile -->
                <div class="flex items-center space-x-3 sm:space-x-5">
                    <!-- Public Portal Link -->
                    <a href="{{ route('welcome') }}" target="_blank" title="View Public Portal" class="hidden sm:inline-flex items-center text-xs text-slate-400 hover:text-amber-400 transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Public Portal
                    </a>

                    <!-- Notifications Dropdown -->
                    @php
                        $unreadNotifications = auth()->user()->notifications()->whereNull('read_at')->latest()->take(5)->get();
                        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
                    @endphp
                    <div class="relative">
                        <button
                            id="notif-btn"
                            type="button"
                            onclick="document.getElementById('notif-menu').classList.toggle('hidden')"
                            class="relative p-2.5 rounded-xl text-slate-300 hover:text-white hover:bg-white/5 border border-white/5 transition-all duration-200 hover:scale-[1.05]"
                            aria-label="View notifications"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
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
                                            <span class="text-[10px] text-slate-500 font-mono">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400 leading-relaxed">{{ $notif->message }}</p>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-xs text-slate-500">No unread alerts</div>
                                @endforelse
                            </div>
                            @if(Route::has('notifications.index'))
                                <div class="pt-3 border-t border-white/10 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs text-teal-300 hover:text-white font-semibold">View all notifications &rarr;</a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- User Pill & Logout -->
                    <div class="flex items-center space-x-3 pl-3 sm:pl-4 border-l border-white/10">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-teal-500 to-cyan-400 flex items-center justify-center text-xs font-bold text-slate-950 ring-2 ring-teal-400/30">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-xs font-semibold text-white leading-tight truncate max-w-[140px]">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-amber-400 uppercase tracking-wider font-semibold font-mono">{{ auth()->user()->role }}</div>
                            </div>
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" title="Sign Out" class="p-2.5 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-white/5 transition-all duration-200 hover:scale-[1.05]" aria-label="Sign Out">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <button
                        type="button"
                        onclick="toggleMobileNav()"
                        class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 border border-white/10"
                        aria-label="Toggle navigation"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Nav Drawer -->
            <div id="mobile-nav-drawer" class="hidden lg:hidden py-4 border-t border-white/10 space-y-1.5">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Dashboard</a>
                <a href="{{ route('shipments.index') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Shipments</a>
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <a href="{{ route('operations.exceptions') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Exceptions</a>
                    <a href="{{ route('operations.carriers') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Carriers</a>
                    <a href="{{ route('operations.locations') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Global Ports</a>
                @endif
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('operations.audit-logs') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Audit Logs</a>
                    @if(Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Users Management</a>
                    @endif
                    @if(Route::has('admin.settings'))
                        <a href="{{ route('admin.settings') }}" class="block px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-white/5">Platform Settings</a>
                    @endif
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content Canvas (Full-width, up to 1600px with generous breathing room) -->
    <main class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Toast Alerts -->
        @if(session('success'))
            <x-toast type="success" :message="session('success')" class="mb-6" />
        @endif

        @if(session('error'))
            <x-toast type="error" :message="session('error')" class="mb-6" />
        @endif

        @yield('content')
    </main>

    <!-- Footer with Honesty Note -->
    <footer class="bg-[#03070C] border-t border-white/10 py-6 text-xs text-slate-500 mt-auto">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-slate-400">CargoTrace &bull; Global Shipment Visibility & Tracking Platform</span>
                <span class="text-slate-600">|</span>
                <span class="text-[11px] text-amber-400/80 font-mono">Demo dataset for academic evaluation</span>
            </div>
            <div class="flex items-center space-x-4 text-slate-400">
                <span class="text-[11px]">Rule-based ETA & operator-recorded checkpoints</span>
                <a href="{{ route('welcome') }}" class="hover:text-white transition">Public Portal</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
