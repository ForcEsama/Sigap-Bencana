<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>tailwind.config={theme:{extend:{fontFamily:{poppins:['Poppins','sans-serif']},colors:{sigap:'#D2002F',blush:'#FFECED'}}}};</script>
    <style>body{font-family:Poppins,sans-serif}.nav-item{display:flex;align-items:center;gap:14px;border-radius:8px;padding:10px 12px;color:#191919}.nav-item.active{color:#c9002c;background:#ffb8bd}@media(max-width:1023px){#sidebar{display:none}#sidebar.open{display:flex;position:fixed;inset:0 auto 0 0;z-index:50;width:min(270px,86vw);overflow-y:auto;box-shadow:10px 0 30px #0002}}</style>
</head>
<body class="min-h-screen bg-blush text-[#181818]"><div class="min-h-screen lg:flex">
    <aside id="sidebar" class="hidden w-[238px] shrink-0 flex-col bg-white px-5 py-6 lg:flex">
        <div class="mb-7 flex items-center justify-between px-2"><a href="{{ route('home') }}"><img src="{{ asset('images/SigapBencanaLogo.png') }}" alt="SIGAP BENCANA" class="h-12 w-auto"></a><button type="button" class="lg:hidden" onclick="toggleMenu()" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button></div>
        <nav class="flex flex-col gap-1 text-[15px]" aria-label="Navigasi utama">
            <a class="nav-item" href="{{ route('home') }}"><i class="fa-solid fa-house w-5 text-center"></i>Home</a>
            <a class="nav-item" href="{{ route('disaster-alerts.index') }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
            <a class="nav-item" href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
            <a class="nav-item" href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
            <a class="nav-item" href="{{ route('sos.create') }}"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</a>
            <a class="nav-item" href="{{ route('news-education.index') }}"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
            <a class="nav-item" href="{{ route('emergency-contacts.index') }}"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
            <a class="nav-item" href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
            <a class="nav-item" href="{{ route('profile.edit') }}"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
            <a class="nav-item active" href="{{ route('notifications.index') }}" aria-current="page"><i class="fa-regular fa-bell w-5 text-center"></i>Notifikasi @if ($unreadCount > 0)<span class="ml-auto rounded-full bg-sigap px-2 py-0.5 text-xs text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif</a>
            <form action="{{ route('logout') }}" method="POST" class="mt-1">@csrf<button class="nav-item w-full text-left" type="submit"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>Keluar</button></form>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4"><div class="flex min-w-0 gap-3"><button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button><div><h1 class="text-2xl font-semibold sm:text-3xl">Notifikasi</h1><p class="mt-1 text-sm text-gray-500 sm:text-base">Peringatan bencana, informasi cuaca, dan aktivitas laporan Anda.</p></div></div><div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3"><x-notification-bell /><span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="hidden sm:inline">{{ auth()->user()->name }}</span></div></header>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <nav class="flex max-w-full gap-2 overflow-x-auto pb-1" aria-label="Filter notifikasi">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('notifications.index', ['filter' => $key]) }}" @class(['shrink-0 rounded-lg border px-4 py-2 text-sm font-medium transition', 'border-sigap bg-sigap text-white' => $activeFilter === $key, 'border-rose-100 bg-white text-gray-700 hover:border-rose-300' => $activeFilter !== $key]) @if ($activeFilter === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            @if ($storageAvailable && $unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="filter" value="{{ $activeFilter }}"><button type="submit" class="rounded-lg px-2 py-2 text-sm font-medium text-sigap hover:underline">Tandai semua dibaca</button></form>
            @endif
        </div>

        @if (! $storageAvailable)
            <p role="status" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Penyimpanan status baca belum tersedia. Informasi eksternal yang tersedia tetap ditampilkan tanpa status baca.</p>
        @endif

        @if ($feedStates !== [])
            <section class="mb-5 rounded-xl border border-rose-100 bg-white p-4 shadow-sm" aria-label="Status sumber informasi">
                <h2 class="text-sm font-semibold">Status sumber informasi</h2>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-gray-600">
                    @foreach ($feedStates as $feedState)
                        <span><span class="mr-1 inline-block h-2 w-2 rounded-full {{ $feedState['status'] === 'available' ? 'bg-emerald-500' : ($feedState['status'] === 'partial' ? 'bg-amber-500' : ($feedState['status'] === 'stale' ? 'bg-orange-500' : 'bg-gray-400')) }}"></span>{{ $feedState['name'] }}: {{ ['available' => 'tersedia', 'partial' => 'sebagian tersedia', 'stale' => 'cache lama', 'unavailable' => 'tidak tersedia'][$feedState['status']] }}@if ($feedState['fetched_at']) <span class="text-gray-500">· diperiksa {{ \Carbon\Carbon::parse($feedState['fetched_at'])->timezone(config('app.display_timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d M Y H:i') }} WIB</span>@endif</span>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-500">Waktu periksa menunjukkan waktu pengambilan feed, bukan waktu terjadinya peristiwa. Item BMKG/BNPB membuka sumber aslinya.</p>
            </section>
        @endif

        @if ($notifications->isEmpty())
            <section class="rounded-xl border border-dashed border-rose-200 bg-white px-5 py-12 text-center shadow-sm" aria-live="polite"><i class="fa-regular fa-bell-slash mb-3 text-4xl text-gray-400" aria-hidden="true"></i><h2 class="font-semibold">Belum ada notifikasi pada kategori ini.</h2><p class="mt-2 text-sm text-gray-600">Notifikasi aktivitas akan muncul setelah ada aktivitas nyata pada akun Anda.</p></section>
        @else
            <section class="space-y-3" aria-label="Daftar notifikasi">
                @foreach ($notifications as $item)
                    @php
                        $categoryPresentation = match ($item['category']) {
                            'warning' => ['Peringatan', 'fa-triangle-exclamation', 'bg-rose-100 text-rose-700'],
                            'information' => ['Informasi', 'fa-circle-info', 'bg-sky-100 text-sky-700'],
                            default => ['Aktivitas', 'fa-list-check', 'bg-violet-100 text-violet-700'],
                        };
                    @endphp
                    <article @class(['flex min-w-0 gap-3 rounded-xl border p-4 shadow-sm sm:gap-4 sm:p-5', 'border-rose-200 bg-white' => ! $item['persistent'] || $item['read_at'], 'border-rose-300 bg-rose-50' => $item['persistent'] && ! $item['read_at']])>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $categoryPresentation[2] }}"><i class="fa-solid {{ $categoryPresentation[1] }}" aria-hidden="true"></i></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $categoryPresentation[2] }}">{{ $categoryPresentation[0] }}</span>@if ($item['persistent'] && ! $item['read_at'])<span class="h-2 w-2 rounded-full bg-sigap" aria-label="Belum dibaca"></span>@endif @if ($item['source_status'] === 'stale')<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-800">Cache sumber</span>@endif</div>
                            <h2 class="mt-2 break-words font-semibold leading-snug">{{ $item['title'] }}</h2>
                            <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-gray-700">{{ $item['message'] }}</p>
                            <p class="mt-2 text-xs text-gray-500">{{ $item['source'] }} @if ($item['created_at'])<span aria-hidden="true">·</span> {{ $item['created_at']->timezone(config('app.display_timezone', 'Asia/Jakarta'))->locale('id')->diffForHumans() }} ({{ $item['created_at']->timezone(config('app.display_timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y, H:i') }} WIB)@else <span aria-hidden="true">·</span> Waktu peristiwa tidak dicantumkan @endif @if ($item['expires_at'])<span aria-hidden="true">·</span> Berlaku sampai {{ $item['expires_at']->timezone(config('app.display_timezone', 'Asia/Jakarta'))->locale('id')->translatedFormat('d F Y, H:i') }} WIB @endif</p>
                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                                @if ($item['target_url'])<a href="{{ $item['target_url'] }}" @if ($item['source_url']) target="_blank" rel="noopener noreferrer" @endif class="inline-flex items-center gap-2 text-sm font-semibold text-sigap hover:underline">{{ $item['source_url'] ? 'Buka sumber' : 'Lihat aktivitas' }} <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i></a>@endif
                                @if ($item['persistent'] && ! $item['read_at'])<form action="{{ route('notifications.read', $item['notification_id']) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="filter" value="{{ $activeFilter }}"><button type="submit" class="text-sm text-gray-600 hover:text-sigap hover:underline">Tandai dibaca</button></form>@endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
            @if ($notifications->hasPages())<div class="mt-5">{{ $notifications->links() }}</div>@endif
        @endif
    </main>
</div><script>function toggleMenu(){document.getElementById('sidebar').classList.toggle('open');}</script></body>
</html>
