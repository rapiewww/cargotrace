<!-- Mobile Drawer Backdrop -->
<div
    id="sidebar-backdrop"
    onclick="window.CargoTrace && window.CargoTrace.closeSidebar ? window.CargoTrace.closeSidebar() : null"
    class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"
    aria-hidden="true"
></div>

<!-- Global Sidebar (Desktop Fixed w-64, Mobile Slide-out Drawer) -->
<aside
    id="main-sidebar"
    class="fixed inset-y-0 left-0 z-50 w-64 theme-sidebar border-r theme-border flex flex-col justify-between py-5 px-4 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none"
    aria-label="Main Navigation"
>
    <!-- 1. Brand Logo & Title -->
    <div class="space-y-6">
        <div class="flex items-center justify-between pb-3 border-b theme-border">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group" title="CargoTrace Maritime Intelligence">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 text-white shadow-md shadow-blue-500/25 group-hover:scale-105 transition-transform duration-200">
                    <!-- Inverted Maritime Shield / Hull Mark -->
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight theme-heading block leading-none">
                        CargoTrace
                    </span>
                    <span class="text-[10px] font-mono tracking-wider uppercase text-cyan-600 dark:text-cyan-400 font-bold">
                        Pure Maritime
                    </span>
                </div>
            </a>

            <!-- Mobile Close Button -->
            <button
                type="button"
                onclick="window.CargoTrace && window.CargoTrace.closeSidebar ? window.CargoTrace.closeSidebar() : null"
                class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                aria-label="Close navigation"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- 2. Grouped Nav Links -->
        <nav class="space-y-5 overflow-y-auto max-h-[calc(100vh-230px)] pr-1 focus:outline-none">
            <!-- SECTION A: CORE NAVIGATION -->
            <div class="space-y-1">
                <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-mono block mb-1.5">
                    Main Navigation
                </span>

                <!-- 1. Global View -->
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Global View</span>
                </a>

                <!-- 2. Shipments -->
                <a
                    href="{{ route('shipments.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('shipments.*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0 {{ request()->routeIs('shipments.*') ? 'text-blue-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Shipments</span>
                </a>

                <!-- 3. Exceptions -->
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <a
                        href="{{ route('operations.exceptions') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('operations.exceptions*') ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-r-2 border-rose-500 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 flex-shrink-0 {{ request()->routeIs('operations.exceptions*') ? 'text-rose-500' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Exceptions</span>
                        </div>
                    </a>
                @endif
            </div>

            <!-- SECTION B: MARITIME INTELLIGENCE -->
            <div class="space-y-1">
                <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-mono block mb-1.5">
                    Maritime Intelligence
                </span>

                <!-- 4. Seaport Weather -->
                <a
                    href="{{ route('weather.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('weather.*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0 {{ request()->routeIs('weather.*') ? 'text-blue-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                    <span>Seaport Weather</span>
                </a>

                <!-- 5. Currency FX -->
                <a
                    href="{{ route('currency.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('currency.*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0 {{ request()->routeIs('currency.*') ? 'text-blue-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span>Currency FX</span>
                </a>

                <!-- 6. Maritime News -->
                <a
                    href="{{ route('news.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('news.*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                >
                    <svg class="w-4 h-4 flex-shrink-0 {{ request()->routeIs('news.*') ? 'text-blue-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    <span>Maritime News</span>
                </a>
            </div>

            <!-- SECTION C: FLEET & MASTER DATA (Admin & Operator) -->
            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-mono block mb-1.5">
                        Fleet & Master Data
                    </span>

                    <!-- Shipping Lines -->
                    <a
                        href="{{ route('operations.carriers') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('operations.carriers*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                    >
                        <svg class="w-4 h-4 flex-shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Shipping Lines</span>
                    </a>

                    <!-- Seaports Network -->
                    <a
                        href="{{ route('operations.locations') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('operations.locations*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                    >
                        <svg class="w-4 h-4 flex-shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Seaports & Nodes</span>
                    </a>

                    @if(auth()->user()->role === 'admin')
                        <!-- Audit Logs -->
                        <a
                            href="{{ route('operations.audit-logs') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('operations.audit-logs*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                        >
                            <svg class="w-4 h-4 flex-shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Audit Logs</span>
                        </a>

                        <!-- Users Management -->
                        @if(Route::has('admin.users.index'))
                            <a
                                href="{{ route('admin.users.index') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                            >
                                <svg class="w-4 h-4 flex-shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>User Directory</span>
                            </a>
                        @endif

                        <!-- System Settings -->
                        @if(Route::has('admin.settings'))
                            <a
                                href="{{ route('admin.settings') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ request()->routeIs('admin.settings*') ? 'bg-blue-600/10 text-blue-600 dark:text-cyan-400 border-r-2 border-blue-500 dark:border-cyan-400 font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                            >
                                <svg class="w-4 h-4 flex-shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Delay Thresholds</span>
                            </a>
                        @endif
                    @endif
                </div>
            @endif
        </nav>
    </div>

    <!-- 3. Footer: User Status & Sign Out -->
    <div class="pt-3 border-t theme-border space-y-3">
        <div class="p-2.5 rounded-xl theme-card-elevated border theme-border flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-xs font-bold theme-text-primary block truncate">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-400 font-semibold block">
                        {{ auth()->user()->role }}
                    </span>
                </div>
            </div>

            <!-- Sign Out Icon Button -->
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button
                    type="submit"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 transition"
                    title="Sign Out"
                    aria-label="Sign Out"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

