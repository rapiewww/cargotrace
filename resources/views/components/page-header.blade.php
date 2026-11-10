@props([
    'title',
    'badge' => null,
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/10']) }}>
    <div>
        @if(count($breadcrumbs) > 0)
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1.5 font-mono">
                @foreach($breadcrumbs as $label => $url)
                    @if($url)
                        <a href="{{ $url }}" class="hover:text-teal-300 transition-colors">{{ $label }}</a>
                        <span>&rarr;</span>
                    @else
                        <span class="text-teal-300">{{ $label }}</span>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="flex items-center space-x-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">{{ $title }}</h1>
            @if($badge)
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">
                    {{ $badge }}
                </span>
            @endif
        </div>

        @if($description)
            <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl leading-relaxed">{{ $description }}</p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="flex items-center space-x-3 self-start sm:self-auto shrink-0">
            {{ $slot }}
        </div>
    @endif
</div>
