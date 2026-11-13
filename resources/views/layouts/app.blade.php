<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth dark" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="map-tile-url" content="{{ config('cargotrace.map.tile_url') }}">
    <meta name="map-tile-attribution" content="{{ config('cargotrace.map.attribution') }}">

    <title>{{ $title ?? 'CargoTrace: Pure Maritime Container Tracking' }}</title>

    <!-- Instant Theme & Sidebar Rail Loader (Zero-Flicker, prefers-color-scheme fallback) -->
    <script>
        (function() {
            let savedTheme = localStorage.getItem('cargotrace_theme');
            if (!savedTheme) {
                savedTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
            }
            document.documentElement.setAttribute('data-theme', savedTheme);
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            }

            const savedSidebar = localStorage.getItem('cargotrace_sidebar_collapsed');
            if (savedSidebar === 'true') {
                document.documentElement.classList.add('sidebar-is-collapsed');
            }
        })();
    </script>

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="theme-page font-sans antialiased min-h-full flex flex-col selection:bg-cyan-500 selection:text-slate-950 transition-colors duration-200 relative">
    {{-- Faint Reusable SVG Network Motif (Fixed behind all application content, follows theme) --}}
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
        <x-svg-network-motif class="text-slate-500 dark:text-slate-300" />
    </div>

    <div class="min-h-full flex flex-col relative z-10">
        <!-- 1. RESPONSIVE ENTERPRISE MARITIME SIDEBAR (Fixed w-64 desktop, drawer mobile) -->
        @include('layouts.sidebar')

        <!-- 2. MAIN VIEW CONTAINER (Shifted right by w-64 or w-20 on desktop) -->
        <div id="main-content" class="lg:pl-64 pb-20 lg:pb-0 flex-1 flex flex-col min-h-screen theme-page transition-all duration-300">
            <!-- 3. TOP MINIMALIST HEADER BAR -->
            <header class="h-16 px-4 sm:px-8 flex items-center justify-between border-b theme-border theme-header backdrop-blur-md sticky top-0 z-40 transition-colors duration-200">
                <!-- Top-Left: Mobile Hamburger + Desktop Rail Toggle + Pill Theme Switch -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <!-- Mobile Hamburger Button -->
                    <button
                        type="button"
                        onclick="window.CargoTrace && window.CargoTrace.toggleSidebar ? window.CargoTrace.toggleSidebar() : null"
                        class="lg:hidden p-2 rounded-xl theme-card-elevated border theme-border text-slate-400 hover:text-cyan-400 transition"
                        aria-label="Open navigation menu"
                        title="Open menu"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Desktop Sidebar Rail Toggle Button -->
                    <button
                        type="button"
                        onclick="window.CargoTrace && window.CargoTrace.toggleSidebarCollapse ? window.CargoTrace.toggleSidebarCollapse() : null"
                        class="hidden lg:flex p-2 rounded-xl theme-card-elevated border theme-border text-slate-400 hover:text-cyan-400 transition"
                        aria-label="Toggle sidebar width"
                        title="Toggle sidebar rail"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                    </button>

                    <button
                        type="button"
                        id="theme-toggle-btn"
                        role="switch"
                        aria-checked="true"
                        aria-pressed="true"
                        tabindex="0"
                        onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.CargoTrace&&window.CargoTrace.toggleTheme?window.CargoTrace.toggleTheme():null}"
                        class="relative flex items-center p-1 rounded-full bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 w-16 h-8 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                        aria-label="Toggle dark and light theme"
                        title="Switch theme"
                    >
                        <!-- Moon Icon (Left) -->
                        <span class="w-6 h-6 flex items-center justify-center text-slate-400 dark:text-cyan-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                            </svg>
                        </span>
                        <!-- Sun Icon (Right) -->
                        <span class="w-6 h-6 flex items-center justify-center text-amber-500 dark:text-slate-500 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </span>
                    </button>

                    <!-- Public Portal link -->
                    <a href="{{ route('welcome') }}" target="_blank" class="hidden sm:inline-flex items-center text-xs theme-text-secondary hover:text-cyan-400 transition-colors">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Public Portal
                    </a>
                </div>

                <!-- Top-Right: New Shipment + User Pill + Notification Bell -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    @if(in_array(auth()->user()->role, ['admin', 'operator']))
                        <a href="{{ route('shipments.create') }}" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs shadow-md shadow-blue-600/25 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
                            New Shipment
                        </a>
                    @endif

                    <!-- User Pill (Evan H. Style) -->
                    <div class="flex items-center gap-2.5 p-1.5 pr-3 rounded-full theme-card-elevated border theme-border">
                        <div class="relative w-7 h-7 rounded-full bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center text-white text-[11px] font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-400 border border-slate-900"></span>
                        </div>
                        <span class="text-xs font-semibold theme-text-primary truncate max-w-[110px]">
                            {{ auth()->user()->name }}
                        </span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <!-- In-App Notifications Dropdown Button -->
                    @php
                        $unreadNotifications = auth()->user()->notifications()->whereNull('read_at')->latest()->take(5)->get();
                        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
                    @endphp
                    <div class="relative">
                        <button
                            id="notif-btn"
                            type="button"
                            onclick="document.getElementById('notif-menu').classList.toggle('hidden')"
                            class="relative p-2 rounded-full text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-900/60 border border-slate-300 dark:border-slate-800 transition"
                            aria-label="View notifications"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                            @endif
                        </button>

                        <div id="notif-menu" class="hidden absolute right-0 mt-3 w-80 sm:w-96 rounded-2xl theme-card-elevated border theme-border shadow-2xl p-4 z-50">
                            <div class="flex items-center justify-between pb-3 border-b theme-border">
                                <span class="text-xs font-semibold theme-heading">Maritime Alerts ({{ $unreadCount }})</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] text-cyan-500 dark:text-cyan-400 hover:underline font-medium">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="divide-y theme-border max-h-72 overflow-y-auto mt-2">
                                @forelse($unreadNotifications as $notif)
                                    <div class="py-2.5 space-y-1">
                                        <div class="flex items-center justify-between text-xs font-semibold theme-heading">
                                            <span>{{ $notif->title }}</span>
                                            <span class="text-[10px] theme-text-muted">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs theme-text-secondary leading-relaxed">{{ $notif->message }}</p>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-xs theme-text-muted">No unread alerts</div>
                                @endforelse
                            </div>
                            @if(Route::has('notifications.index'))
                                <div class="pt-3 border-t theme-border text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs text-cyan-500 dark:text-cyan-400 hover:underline font-medium">View all notifications &rarr;</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <!-- 4. PAGE CONTENT -->
            <main class="flex-1">
                @yield('content')
            </main>

            <!-- 5. FOOTER (Honest Maritime Attribution) -->
            <footer class="border-t theme-border py-6 text-xs theme-text-muted mt-auto theme-sidebar transition-colors duration-200">
                <div class="max-w-[1600px] mx-auto px-6 sm:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <span class="font-bold theme-text-primary">CargoTrace &bull; Ocean Container Visibility Platform</span>
                        <span class="text-slate-500">&bull;</span>
                        <span class="text-[11px] text-cyan-500 dark:text-cyan-400 font-mono">Pure Maritime Container Tracking</span>
                    </div>
                    <div class="flex items-center space-x-4 theme-text-muted text-[11px]">
                        <span>Interactive 3D Earth Globe &bull; Operator Checkpoints</span>
                        <a href="{{ route('welcome') }}" class="hover:text-cyan-400 transition">Public Portal</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
