@props([
    'headers' => [],
])

<div class="rounded-2xl theme-card border overflow-hidden transition-colors duration-200">
    <div class="overflow-x-auto">
        <table class="theme-table w-full text-left text-xs">
            @if(count($headers) > 0)
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th class="py-4 px-5 {{ str_contains(strtolower($header), 'action') ? 'text-right' : '' }}">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
