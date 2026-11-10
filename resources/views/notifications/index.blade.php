@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white font-sans">
                    Notifications & Alerts
                </h1>
                @if($unreadCount > 0)
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 font-mono">
                        {{ $unreadCount }} Unread
                    </span>
                @endif
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Real-time dispatch alerts, ETA delay adjustments, and shipment exception reports.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            @if($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-teal-300 border border-teal-500/30 text-xs font-semibold transition">
                        Mark All as Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="flex items-center space-x-2 text-xs font-semibold">
        <a href="{{ route('notifications.index') }}" class="px-3 py-1.5 rounded-lg border {{ !request('filter') ? 'bg-teal-500/20 text-teal-300 border-teal-500/40' : 'bg-slate-900/50 text-slate-400 border-slate-800 hover:text-white' }} transition">
            All Notifications
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="px-3 py-1.5 rounded-lg border {{ request('filter') === 'unread' ? 'bg-teal-500/20 text-teal-300 border-teal-500/40' : 'bg-slate-900/50 text-slate-400 border-slate-800 hover:text-white' }} transition">
            Unread Only ({{ $unreadCount }})
        </a>
    </div>

    <!-- Notification List -->
    <x-card :padded="false" class="divide-y divide-white/5">
        @forelse($notifications as $notif)
            <div class="p-4 sm:p-5 flex items-start justify-between gap-4 transition hover:bg-white/[0.02] {{ is_null($notif->read_at) ? 'bg-amber-500/[0.03]' : '' }}">
                <div class="flex items-start space-x-3.5">
                    @php
                        $iconClass = match($notif->type) {
                            'SHIPMENT_DELAY_ALERT' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                            'EXCEPTION_DETECTED' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                            default => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
                        };
                    @endphp
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border {{ $iconClass }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-white">{{ $notif->title }}</h3>
                            @if(is_null($notif->read_at))
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $notif->message }}</p>
                        <div class="flex items-center gap-3 pt-1 text-[11px] font-mono text-slate-500">
                            <span>{{ $notif->created_at?->diffForHumans() }}</span>
                            @if($notif->shipment)
                                <a href="{{ route('shipments.show', $notif->shipment) }}" class="text-teal-300 hover:underline">
                                    Waybill: {{ $notif->shipment->tracking_number }} &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if(is_null($notif->read_at))
                    <form action="{{ route('notifications.read', $notif) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-2.5 py-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 border border-white/5 text-[11px] transition">
                            Mark Read
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="py-12 text-center text-slate-500 text-xs">
                No notifications matching current criteria.
            </div>
        @endforelse
    </x-card>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</div>
@endsection

