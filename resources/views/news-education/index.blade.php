<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita &amp; Edukasi - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>tailwind.config={theme:{extend:{fontFamily:{poppins:['Poppins','sans-serif']},colors:{sigap:'#D2002F',blush:'#FFECED'}}}};</script>
    <style>
        body{font-family:Poppins,sans-serif}.nav-item{display:flex;align-items:center;gap:14px;border-radius:8px;padding:10px 12px;color:#191919}.nav-item.active{color:#c9002c;background:#ffb8bd}
        @media(max-width:1023px){#sidebar{display:none}#sidebar.open{display:flex;position:fixed;inset:0 auto 0 0;z-index:50;width:min(270px,86vw);overflow-y:auto;box-shadow:10px 0 30px #0002}}
    </style>
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
            <a class="nav-item active" href="{{ route('news-education.index') }}" aria-current="page"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
            <a class="nav-item" href="{{ route('emergency-contacts.index') }}"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
            <a class="nav-item" href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
            <a class="nav-item" href="{{ route('profile.edit') }}"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
            <form action="{{ route('logout') }}" method="POST" class="mt-1">@csrf<button class="nav-item w-full text-left" type="submit"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>Keluar</button></form>
        </nav>
    </aside>
    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4"><div class="flex gap-3"><button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button><div><h1 class="text-2xl font-semibold sm:text-3xl">Berita &amp; Edukasi</h1><p class="mt-1 text-sm text-gray-500 sm:text-base">Dapatkan informasi terbaru seputar bencana dan tingkatkan kesiapsiagaan Anda</p></div></div><div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3"><x-notification-bell /><span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="hidden sm:inline">{{ auth()->user()->name }}</span></div></header>

        <nav class="mb-6 flex max-w-full gap-2 overflow-x-auto pb-1" aria-label="Kategori berita dan edukasi">
            @foreach ($categories as $key => $label)
                <a href="{{ route('news-education.index', ['category' => $key]) }}" @class(['shrink-0 rounded-lg border px-4 py-2 text-sm font-medium transition', 'border-sigap bg-sigap text-white' => $category === $key, 'border-gray-200 bg-white text-gray-700 hover:border-rose-300' => $category !== $key]) @if ($category === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        @if ($feedsStale)
            <p class="mb-4 text-sm text-amber-800" role="status">
                Sebagian informasi memakai hasil cache terakhir
                @if ($staleFetchedAt)
                    (diambil {{ \Carbon\Carbon::parse($staleFetchedAt)->locale('id')->translatedFormat('d F Y H:i') }} WIB)
                @endif
                ; periksa tanggal dan tautan sumber sebelum digunakan.
            </p>
        @endif
        @if ($items === [])
            <section class="rounded-xl border border-dashed border-rose-200 bg-white px-5 py-12 text-center shadow-sm" aria-live="polite"><i class="fa-regular fa-newspaper mb-3 text-4xl text-gray-400" aria-hidden="true"></i><h2 class="font-semibold">{{ $feedsUnavailable ? 'Konten belum dapat dimuat.' : 'Belum ada konten bergambar yang tersedia pada kategori ini.' }}</h2><p class="mt-2 text-sm text-gray-600">Konten hanya ditampilkan jika sumber, tanggal berita bila tersedia, dan gambar dapat diverifikasi.</p></section>
        @else
            <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-label="Daftar berita dan edukasi">
                @foreach ($items as $item)
                    <article class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-rose-100 bg-white shadow-sm">
                        <a href="{{ route('news-education.show', [$item['source_type'], $item['id']]) }}" class="block aspect-[16/10] overflow-hidden bg-gray-100"><img src="{{ $item['image_url'] }}" alt="{{ $item['title'] }}" loading="lazy" class="h-full w-full object-cover" onerror="this.closest('article').remove()"></a>
                        <div class="flex flex-1 flex-col p-4 sm:p-5"><span class="mb-2 w-fit rounded-full bg-rose-100 px-3 py-1 text-xs font-medium text-rose-800">{{ $item['category'] }}</span><h2 class="line-clamp-3 text-base font-semibold leading-snug">{{ $item['title'] }}</h2><p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-600">{{ $item['summary'] }}</p><div class="mt-auto pt-4"><p class="text-xs text-gray-500">@if ($item['published_at']){{ \Carbon\Carbon::parse($item['published_at'])->locale('id')->translatedFormat('d F Y') }}@else Tanggal publikasi tidak dicantumkan @endif <span aria-hidden="true">·</span> {{ $item['source'] }}</p>@if ($item['image_attribution'])<p class="mt-1 text-[11px] text-gray-500">{{ $item['image_attribution'] }}</p>@endif<a href="{{ route('news-education.show', [$item['source_type'], $item['id']]) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-sigap hover:underline">Baca Selengkapnya <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i></a></div></div>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
</div><script>function toggleMenu(){document.getElementById('sidebar').classList.toggle('open');}</script></body>
</html>
