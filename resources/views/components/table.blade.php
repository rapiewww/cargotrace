@props([
    'headers' => [],
])

<div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 overflow-hidden shadow-2xl">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            @if(count($headers) > 0)
                <thead class="bg-[#071220] text-slate-400 uppercase tracking-wider text-[10px] border-b border-white/10 font-semibold font-sans">
                    <tr>
                        @foreach($headers as $header)
                            <th class="py-4 px-5 {{ str_contains(strtolower($header), 'action') ? 'text-right' : '' }}">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-white/5 text-slate-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
