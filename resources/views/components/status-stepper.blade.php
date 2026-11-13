@props([
    'currentStatus' => 'CREATED',
    'compact' => false,
])

@php
    $stages = [
        'CREATED' => ['label' => 'Order Created', 'icon' => '1'],
        'BOOKED' => ['label' => 'Carrier Booked', 'icon' => '2'],
        'PICKED_UP' => ['label' => 'Picked Up', 'icon' => '3'],
        'IN_TRANSIT' => ['label' => 'In Transit', 'icon' => '4'],
        'DELIVERED' => ['label' => 'Delivered', 'icon' => '✓'],
    ];

    $order = array_keys($stages);
    $currentIndex = array_search($currentStatus, $order);
    if ($currentIndex === false) $currentIndex = 0;
@endphp

@if($compact)
    <!-- Compact mini-stepper for tables / list items -->
    <div class="flex items-center space-x-1">
        @foreach($order as $idx => $st)
            <div
                title="{{ $stages[$st]['label'] }}"
                class="w-5 h-1.5 rounded-full {{ $idx <= $currentIndex ? ($st === 'DELIVERED' ? 'bg-emerald-500' : 'bg-blue-600') : 'bg-slate-300 dark:bg-slate-800' }}"
            ></div>
        @endforeach
    </div>
@else
    <!-- Full visual milestone stepper -->
    <div class="w-full py-4">
        <div class="grid grid-cols-5 relative">
            <div class="absolute top-4 left-6 right-6 h-0.5 bg-slate-300 dark:bg-slate-800 -z-0">
                <div
                    class="h-full bg-gradient-to-r from-blue-600 to-emerald-500 transition-all duration-500"
                    style="width: {{ ($currentIndex / 4) * 100 }}%"
                ></div>
            </div>

            @foreach($order as $idx => $st)
                @php
                    $isPassed = $idx < $currentIndex;
                    $isCurrent = $idx === $currentIndex;
                @endphp
                <div class="flex flex-col items-center text-center relative z-10 space-y-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono transition-all duration-300 {{ $isCurrent ? 'bg-amber-500 text-slate-950 ring-4 ring-amber-500/20 shadow-lg shadow-amber-500/30' : ($isPassed ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-500 dark:text-slate-400') }}">
                        @if($isPassed)
                            ✓
                        @else
                            {{ $stages[$st]['icon'] }}
                        @endif
                    </div>
                    <div class="hidden sm:block">
                        <span class="text-[11px] font-semibold block leading-tight {{ $isCurrent ? 'text-amber-500' : ($isPassed ? 'theme-heading' : 'theme-text-muted') }}">
                            {{ $stages[$st]['label'] }}
                        </span>
                        <span class="text-[9px] uppercase tracking-wider theme-text-muted font-mono">{{ $st }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

