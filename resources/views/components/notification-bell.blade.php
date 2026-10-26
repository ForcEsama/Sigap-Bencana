@php
    $notificationUnreadCount = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('notifications') && auth()->check()) {
            $notificationUnreadCount = auth()->user()->notifications()->withValidSosReference()->whereNull('read_at')->count();
        }
    } catch (\Throwable) {
        $notificationUnreadCount = 0;
    }
@endphp
<a href="{{ route('notifications.index') }}" class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xl hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-sigap" aria-label="Notifikasi{{ $notificationUnreadCount > 0 ? ', '.$notificationUnreadCount.' belum dibaca' : '' }}">
    <i class="fa-regular fa-bell" aria-hidden="true"></i>
    @if ($notificationUnreadCount > 0)
        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-sigap px-1 text-[9px] font-semibold leading-none text-white">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>
    @endif
</a>
