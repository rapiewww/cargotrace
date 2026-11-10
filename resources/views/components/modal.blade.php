@props([
    'id',
    'title',
    'maxWidth' => 'max-w-lg',
])

<div
    id="{{ $id }}"
    class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto"
    aria-labelledby="{{ $id }}-title"
    role="dialog"
    aria-modal="true"
>
    <div class="relative w-full {{ $maxWidth }} rounded-2xl bg-[#0B1728] border border-white/10 p-6 sm:p-7 shadow-2xl space-y-5 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 id="{{ $id }}-title" class="text-base font-bold text-white tracking-tight">{{ $title }}</h3>
            <button
                type="button"
                onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
                class="text-slate-400 hover:text-white text-xl leading-none p-1 rounded-lg hover:bg-white/5 transition"
                aria-label="Close modal"
            >&times;</button>
        </div>

        <div>
            {{ $slot }}
        </div>
    </div>
</div>
