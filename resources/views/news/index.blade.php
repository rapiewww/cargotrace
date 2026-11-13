@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Header with Source Status & Search Form -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b theme-border">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-[11px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-400 font-bold px-2 py-0.5 rounded-md bg-cyan-500/10 border border-cyan-500/20">
                    International Maritime RSS Wire
                </span>
                <x-data-source-badge :source="$dataSource" />
            </div>
            <h1 class="text-2xl sm:text-3xl font-black theme-heading tracking-tight mt-1.5">
                Maritime News Wire & Shipping Intelligence
            </h1>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Global industry bulletins covering container shipping, port terminal congestion, decarbonization, and waterway corridors.
            </p>
        </div>

        <!-- Search Bar & Source Filter Form -->
        <form method="GET" action="{{ route('news.index') }}" class="flex flex-wrap items-center gap-2">
            @if($selectedCategory !== 'all')
                <input type="hidden" name="category" value="{{ $selectedCategory }}">
            @endif

            <div class="relative min-w-[200px]">
                <input
                    type="text"
                    name="q"
                    value="{{ $searchQuery }}"
                    placeholder="Search maritime news..."
                    class="w-full pl-8 pr-3 py-2 rounded-xl theme-card-elevated border theme-border text-xs theme-heading focus:outline-none focus:border-cyan-500 placeholder:text-slate-500"
                >
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Source Dropdown -->
            <select
                name="source"
                onchange="this.form.submit()"
                class="px-3 py-2 rounded-xl theme-card-elevated border theme-border text-xs font-semibold theme-heading focus:outline-none focus:border-cyan-500 cursor-pointer"
            >
                <option value="all">All Sources</option>
                @foreach($sources as $s)
                    <option value="{{ $s }}" {{ strcasecmp($selectedSource, $s) === 0 ? 'selected' : '' }}>
                        {{ $s }}
                    </option>
                @endforeach
            </select>

            <button
                type="submit"
                class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition shadow-sm"
            >
                Filter
            </button>
            @if(!empty($searchQuery) || $selectedSource !== 'all' || $selectedCategory !== 'all' || $onlyRelevant)
                <a
                    href="{{ route('news.index') }}"
                    class="px-2.5 py-2 rounded-xl theme-card-elevated border theme-border text-xs theme-text-secondary hover:theme-heading hover:border-blue-500 transition"
                    title="Clear filters"
                >
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Category Filter Pills & Relevant Switcher -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 focus:outline-none">
        <!-- Relevant to your shipments pill -->
        @if($relevantCount > 0)
            <a
                href="{{ route('news.index', array_merge(request()->except(['relevant', 'page']), $onlyRelevant ? [] : ['relevant' => 1])) }}"
                class="px-3.5 py-1.5 rounded-full text-xs font-bold transition whitespace-nowrap border flex items-center gap-1.5 {{ $onlyRelevant ? 'bg-cyan-600 text-white border-cyan-600 shadow-md shadow-cyan-500/20' : 'bg-cyan-500/10 border-cyan-500/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-500/20' }}"
            >
                <span>⭐ Relevant to Your Shipments</span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full bg-cyan-700/30 text-cyan-200">{{ $relevantCount }}</span>
            </a>
        @endif

        <a
            href="{{ route('news.index', array_merge(request()->except(['category', 'page']), ['category' => 'all'])) }}"
            class="px-3.5 py-1.5 rounded-full text-xs font-bold transition whitespace-nowrap border {{ $selectedCategory === 'all' && !$onlyRelevant ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/20' : 'theme-card-elevated theme-border theme-text-secondary hover:theme-heading hover:border-blue-500' }}"
        >
            All Categories
        </a>
        @foreach($categories as $cat)
            <a
                href="{{ route('news.index', array_merge(request()->except(['category', 'page']), ['category' => $cat])) }}"
                class="px-3.5 py-1.5 rounded-full text-xs font-bold transition whitespace-nowrap border {{ strcasecmp($selectedCategory, $cat) === 0 ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/20' : 'theme-card-elevated theme-border theme-text-secondary hover:theme-heading hover:border-blue-500' }}"
            >
                {{ $cat }}
            </a>
        @endforeach
    </div>

    <!-- Grid of Maritime News Cards with Fixed Aspect Ratio & Lazy Loaded Thumbnails -->
    @if($paginatedNews->isEmpty())
        <div class="p-12 text-center rounded-3xl theme-card border space-y-4">
            <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold theme-heading">No maritime news articles found</h3>
                <p class="text-xs theme-text-secondary max-w-md mx-auto mt-1">
                    No bulletins match your current query or filter parameters. If external feeds are unreachable, try retrying.
                </p>
            </div>
            <div class="pt-2 flex items-center justify-center gap-3">
                <a href="{{ route('news.index') }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition">
                    Reset All Filters
                </a>
                <button type="button" onclick="window.location.reload()" class="px-4 py-2 rounded-xl theme-card-elevated border theme-border text-xs font-bold theme-heading hover:bg-slate-500/10 transition">
                    Retry Feed Connection
                </button>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($paginatedNews as $item)
                <article class="rounded-3xl theme-card border overflow-hidden flex flex-col justify-between hover:border-cyan-500/40 transition group">
                    <!-- Thumbnail with Fixed Aspect Ratio (16/9) preventing layout shift -->
                    <div class="relative aspect-video w-full bg-slate-200 dark:bg-slate-900 overflow-hidden">
                        <img
                            src="{{ $item['thumbnail'] ?? asset('images/news-placeholder.svg') }}"
                            alt="{{ $item['headline'] }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                            onerror="this.onerror=null;this.src='{{ asset('images/news-placeholder.svg') }}';"
                        >
                        <!-- Category Overlay & Relevant Badge -->
                        <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 pointer-events-none">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold uppercase tracking-wider bg-slate-950/85 backdrop-blur-md text-cyan-400 border border-white/10 shadow-lg">
                                {{ $item['category'] ?? 'Shipping & Trade' }}
                            </span>
                            @if(!empty($item['is_relevant']))
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold uppercase tracking-wider bg-blue-600/90 backdrop-blur-md text-white border border-blue-400/40 shadow-lg">
                                    ⭐ Shipment Match
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Content Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-mono">
                                <span class="font-bold text-cyan-600 dark:text-cyan-400 uppercase">
                                    {{ $item['source'] ?? 'Maritime Wire' }}
                                </span>
                                <span class="theme-text-muted">
                                    {{ \Carbon\Carbon::parse($item['published_at'])->format('M d, Y') }}
                                </span>
                            </div>

                            <h2 class="text-base font-extrabold theme-heading group-hover:text-cyan-500 transition line-clamp-2 leading-snug">
                                <a href="{{ $item['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                                    {{ $item['headline'] }}
                                </a>
                            </h2>

                            <p class="text-xs theme-text-secondary leading-relaxed line-clamp-3">
                                {{ $item['summary'] }}
                            </p>
                        </div>

                        <!-- Read Link (Source, Diff time, External Link) -->
                        <div class="pt-3 border-t theme-border flex items-center justify-between">
                            <span class="text-[10px] font-mono theme-text-muted">
                                {{ \Carbon\Carbon::parse($item['published_at'])->diffForHumans() }}
                            </span>
                            <a
                                href="{{ $item['url'] ?? '#' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-cyan-400 hover:text-blue-500 dark:hover:text-cyan-300 transition group/link"
                            >
                                <span>Read Full Report</span>
                                <svg class="w-3.5 h-3.5 group-hover/link:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Pagination Links -->
        <div class="pt-6 border-t theme-border flex justify-center">
            {{ $paginatedNews->links() }}
        </div>
    @endif
</div>
@endsection
