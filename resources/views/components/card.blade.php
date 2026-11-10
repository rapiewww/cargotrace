@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
    'padding' => 'p-6',
    'class' => '',
])

<div {{ $attributes->merge(['class' => "rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl {$padding} {$class}"]) }}>
    @if($title || $action)
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-white/5">
            <div>
                @if($subtitle)
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold block">{{ $subtitle }}</span>
                @endif
                <h3 class="text-base font-bold text-white tracking-tight">{{ $title }}</h3>
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
