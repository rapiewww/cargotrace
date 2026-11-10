<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth dark" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'CargoTrace — Pure Maritime Container Tracking' }}</title>

    <!-- Instant Theme Loader (Zero-Flicker) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('cargotrace_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            }
        })();
    </script>

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="theme-page font-sans antialiased min-h-full flex flex-col selection:bg-cyan-500 selection:text-slate-950 transition-colors duration-200">
    <div class="min-h-full flex flex-col">
        <!-- 1. LEFT VERTICAL ICON SIDEBAR (Fixed w-20, Global View Style) -->
        <aside class="hidden lg:flex w-20 fixed inset-y-0 left-0 z-50 flex-col items-center justify-between py-6 theme-sidebar border-r theme-border transition-colors duration-200">
            <!-- Brand Logo at Top -->
            <div class="flex flex-col items-center">
                <a href="{{ route('dashboard') }}" class="group relative flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 text-white shadow-lg shadow-blue-500/25 transition-transform duration-200 hover:scale-105" title="CargoTrace Maritime">
                    <!-- Inverted Triangle / Maritime Shield Logo -->
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                    </svg>
                </a>
            </div>

            <!-- Navigation Menu Icons (Center) -->
            <nav class="flex flex-col items-center space-y-4 my-auto">
                <!-- 1. Global View / Dashboard -->
                <div class="relative group flex items-center justify-center">
                    @if(request()->routeIs('dashboard'))
                        <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                    @endif
                    <a href="{{ route('dashboard') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Global View">
                        <!-- Globe Icon -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </a>
                    <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                        Global View
                    </div>
                </div>

                <!-- 2. Container Shipments -->
                <div class="relative group flex items-center justify-center">
                    @if(request()->routeIs('shipments.*'))
                        <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                    @endif
                    <a href="{{ route('shipments.index') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('shipments.*') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Container Shipments">
                        <!-- Container / Cube Icon -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </a>
                    <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                        Container Shipments
                    </div>
                </div>

                <!-- 3. Fleet & Shipping Lines -->
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <div class="relative group flex items-center justify-center">
                        @if(request()->routeIs('operations.carriers'))
                            <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                        @endif
                        <a href="{{ route('operations.carriers') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('operations.carriers') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Shipping Lines & Fleet">
                            <!-- Vessel / Fleet Bar Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </a>
                        <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                            Fleet & Shipping Lines
                        </div>
                    </div>

                    <!-- 4. Seaports Network -->
                    <div class="relative group flex items-center justify-center">
                        @if(request()->routeIs('operations.locations'))
                            <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                        @endif
                        <a href="{{ route('operations.locations') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('operations.locations') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Seaports Network">
                            <!-- Anchor / Seaport Nodes Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </a>
                        <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                            Seaports Network
                        </div>
                    </div>

                    <!-- 5. Maritime Exceptions -->
                    <div class="relative group flex items-center justify-center">
                        @if(request()->routeIs('operations.exceptions'))
                            <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                        @endif
                        <a href="{{ route('operations.exceptions') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('operations.exceptions') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Maritime Exceptions">
                            <!-- Alert / Incident Bell Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </a>
                        <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                            Maritime Exceptions
                        </div>
                    </div>
                @endif

                <!-- 6. Audit Logs (Admin only) -->
                @if(auth()->user()->role === 'admin')
                    <div class="relative group flex items-center justify-center">
                        @if(request()->routeIs('operations.audit-logs'))
                            <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                        @endif
                        <a href="{{ route('operations.audit-logs') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('operations.audit-logs') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Audit Logs">
                            <!-- Document / Shield Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </a>
                        <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                            Audit Logs
                        </div>
                    </div>

                    <!-- 7. Users Management (Admin only) -->
                    @if(Route::has('admin.users.index'))
                        <div class="relative group flex items-center justify-center">
                            @if(request()->routeIs('admin.users.*'))
                                <span class="absolute -left-4 w-1.5 h-7 bg-cyan-400 rounded-r-full shadow-[0_0_12px_#38BDF8]"></span>
                            @endif
                            <a href="{{ route('admin.users.index') }}" class="relative p-3 rounded-2xl transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-blue-600/25 text-cyan-400 border border-blue-500/40 shadow-inner' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900' }}" aria-label="Users Management">
                                <!-- User Icon -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </a>
                            <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                                Users Management
                            </div>
                        </div>
                    @endif
                @endif
            </nav>

            <!-- Bottom Actions (Settings & Logout) -->
            <div class="flex flex-col items-center space-y-3">
                @if(auth()->user()->role === 'admin' && Route::has('admin.settings'))
                    <div class="relative group flex items-center justify-center">
                        <a href="{{ route('admin.settings') }}" class="p-3 rounded-2xl text-slate-400 hover:text-white hover:bg-slate-800/50 light:hover:bg-slate-100 light:hover:text-slate-900 transition" aria-label="Settings">
                            <!-- Gear Icon -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </a>
                        <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                            Settings
                        </div>
                    </div>
                @endif

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" class="relative group flex items-center justify-center">
                    @csrf
                    <button type="submit" class="p-3 rounded-2xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition" aria-label="Sign Out" title="Sign Out">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                    <div class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-slate-100 text-[11px] font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition duration-150 z-50 border border-slate-700 shadow-xl">
                        Sign Out
                    </div>
                </form>
            </div>
        </aside>

        <!-- Mobile Bottom Navigation Bar (< lg screens) -->
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-50 h-16 theme-sidebar border-t theme-border flex items-center justify-around px-2 shadow-2xl transition-colors duration-200">
            <a href="{{ route('dashboard') }}" class="p-2.5 rounded-xl {{ request()->routeIs('dashboard') ? 'text-cyan-400 bg-blue-600/20' : 'theme-text-secondary hover:text-cyan-400' }}" aria-label="Global View">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </a>
            <a href="{{ route('shipments.index') }}" class="p-2.5 rounded-xl {{ request()->routeIs('shipments.*') ? 'text-cyan-400 bg-blue-600/20' : 'theme-text-secondary hover:text-cyan-400' }}" aria-label="Shipments">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </a>
            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('operations.carriers') }}" class="p-2.5 rounded-xl {{ request()->routeIs('operations.carriers*') ? 'text-cyan-400 bg-blue-600/20' : 'theme-text-secondary hover:text-cyan-400' }}" aria-label="Carriers">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                </a>
                <a href="{{ route('operations.exceptions') }}" class="p-2.5 rounded-xl {{ request()->routeIs('operations.exceptions*') ? 'text-rose-400 bg-rose-500/20' : 'theme-text-secondary hover:text-rose-400' }}" aria-label="Exceptions">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="p-2.5 rounded-xl text-rose-400 hover:bg-rose-500/10" aria-label="Sign Out">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </nav>

        <!-- 2. MAIN VIEW CONTAINER (Shifted right by w-20 on desktop) -->
        <div class="lg:pl-20 pb-20 lg:pb-0 flex-1 flex flex-col min-h-screen theme-page transition-colors duration-200">
            <!-- 3. TOP MINIMALIST HEADER BAR -->
            <header class="h-16 px-6 sm:px-8 flex items-center justify-between border-b theme-border theme-header backdrop-blur-md sticky top-0 z-40 transition-colors duration-200">
                <!-- Top-Left: Pill Toggle Switch Dark / Light Mode (Global View style) -->
                <div class="flex items-center space-x-3">
                    <button
                        type="button"
                        onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                        class="relative flex items-center p-1 rounded-full bg-slate-900 dark:bg-slate-900 light:bg-slate-200 border border-slate-800 light:border-slate-300 w-16 h-8 transition-colors duration-200 focus:outline-none"
                        aria-label="Toggle dark and light theme"
                        title="Switch theme"
                    >
                        <!-- Moon Icon (Left) -->
                        <span class="w-6 h-6 flex items-center justify-center text-cyan-400 dark:text-cyan-400 light:text-slate-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                            </svg>
                        </span>
                        <!-- Sun Icon (Right) -->
                        <span class="w-6 h-6 flex items-center justify-center text-slate-500 dark:text-slate-500 light:text-amber-500 transition-colors">
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
                            class="relative p-2 rounded-full text-slate-400 hover:text-white bg-slate-900/60 dark:bg-slate-900/60 light:bg-slate-100 border border-slate-800 light:border-slate-300 transition"
                            aria-label="View notifications"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                            @endif
                        </button>

                        <div id="notif-menu" class="hidden absolute right-0 mt-3 w-80 sm:w-96 rounded-2xl bg-[#0B101E] light:bg-white border border-slate-800 light:border-slate-200 shadow-2xl p-4 z-50">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 light:border-slate-200">
                                <span class="text-xs font-semibold text-white light:text-slate-900">Maritime Alerts ({{ $unreadCount }})</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] text-cyan-400 hover:underline font-medium">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            <div class="divide-y divide-slate-800/80 light:divide-slate-100 max-h-72 overflow-y-auto mt-2">
                                @forelse($unreadNotifications as $notif)
                                    <div class="py-2.5 space-y-1">
                                        <div class="flex items-center justify-between text-xs font-semibold text-white light:text-slate-900">
                                            <span>{{ $notif->title }}</span>
                                            <span class="text-[10px] text-slate-500">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400 light:text-slate-600 leading-relaxed">{{ $notif->message }}</p>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-xs text-slate-500">No unread alerts</div>
                                @endforelse
                            </div>
                            @if(Route::has('notifications.index'))
                                <div class="pt-3 border-t border-slate-800 light:border-slate-200 text-center">
                                    <a href="{{ route('notifications.index') }}" class="text-xs text-cyan-400 hover:underline font-medium">View all notifications &rarr;</a>
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
                        <span class="font-bold theme-text-primary">CargoTrace &bull; Ocean Container Visibility & AIS Platform</span>
                        <span class="text-slate-500">&bull;</span>
                        <span class="text-[11px] text-cyan-500 dark:text-cyan-400 font-mono">Pure Maritime Container Tracking</span>
                    </div>
                    <div class="flex items-center space-x-4 theme-text-muted text-[11px]">
                        <span>Interactive 3D Earth Globe &bull; Live Seaport Telemetry</span>
                        <a href="{{ route('welcome') }}" class="hover:text-cyan-400 transition">Public Portal</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
