@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
    'padding' => 'p-6',
    'class' => '',
])

<div {{ $attributes->merge(['class' => "rounded-2xl theme-card border {$padding} {$class} transition-colors duration-200"]) }}>
    @if($title || $action)
        <div class="flex items-center justify-between pb-4 mb-4 border-b theme-border">
            <div>
                @if($subtitle)
                    <span class="text-[10px] uppercase tracking-wider theme-text-muted font-semibold block">{{ $subtitle }}</span>
                @endif
                <h3 class="text-base font-bold theme-heading tracking-tight">{{ $title }}</h3>
            </div>
            @if($action)
                <div class="flex items-center space-x-2">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
