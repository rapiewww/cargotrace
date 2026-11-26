@props([
    'title',
    'badge' => null,
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border']) }}>
    <div>
        @if(count($breadcrumbs) > 0)
            <div class="flex items-center space-x-2 text-xs theme-text-muted mb-1.5 font-mono">
                @foreach($breadcrumbs as $label => $url)
                    @if($url)
                        <a href="{{ $url }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">{{ $label }}</a>
                        <span>&rarr;</span>
                    @else
                        <span class="text-blue-600 dark:text-cyan-400">{{ $label }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="flex items-center space-x-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading">{{ $title }}</h1>
            @if($badge)
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border border-cyan-500/25">
                    {{ $badge }}
                </span>
            @endif
        </div>

        @if($description)
            <p class="text-xs sm:text-sm theme-text-secondary mt-1 max-w-2xl leading-relaxed">{{ $description }}</p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="flex items-center space-x-3 self-start sm:self-auto shrink-0">
            {{ $slot }}
        </div>
    @endif
</div>

