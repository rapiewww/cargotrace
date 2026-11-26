@props([
    'title' => 'No records found',
    'message' => 'Try refining your search or filters.',
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'py-16 px-6 text-center flex flex-col items-center justify-center space-y-3']) }}>
    <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    </div>
    <div class="space-y-1">
        <h4 class="text-sm font-bold theme-heading">{{ __($title) }}</h4>
        <p class="text-xs theme-text-secondary max-w-sm">{{ __($message) }}</p>
    </div>
    @if($action)
        <div class="pt-2">
            {{ $action }}
        </div>
    @endif
</div>

