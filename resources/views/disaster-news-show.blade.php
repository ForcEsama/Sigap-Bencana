<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article['title'] }} - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { poppins: ['Poppins', 'sans-serif'] }, colors: { sigap: '#D2002F', blush: '#FFECED' } } };
    </script>
    <style>
        body { font-family: Poppins, sans-serif; }
        .nav-item { display: flex; align-items: center; gap: 14px; border-radius: 8px; padding: 10px 12px; color: #191919; }
        @media (max-width: 1023px) {
            #sidebar { display: none; }
            #sidebar.open { display: flex; position: fixed; inset: 0 auto 0 0; z-index: 50; width: min(270px, 86vw); overflow-y: auto; box-shadow: 10px 0 30px #0002; }
        }
    </style>
</head>
<body class="min-h-screen bg-blush text-[#181818]">
<div class="min-h-screen lg:flex">
    @auth
    <aside id="sidebar" class="hidden w-[238px] shrink-0 flex-col bg-white px-5 py-6 lg:flex">
        <div class="mb-7 flex items-center justify-between px-2">
            <a href="{{ route('home') }}"><img src="{{ asset('images/SigapBencanaLogo.png') }}" alt="SIGAP BENCANA" class="h-12 w-auto"></a>
            <button type="button" class="lg:hidden" onclick="toggleMenu()" aria-label="Tutup menu"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <nav class="flex flex-col gap-1 text-[15px]" aria-label="Navigasi utama">
            <a class="nav-item" href="{{ route('home') }}"><i class="fa-solid fa-house w-5 text-center"></i>Home</a>
            @include('components.operational-dashboard-link')
            <a class="nav-item" href="{{ route('disaster-alerts.index') }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
            <a class="nav-item" href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
            <a class="nav-item" href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
            <a class="nav-item" href="{{ route('sos.create') }}"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</a>
            <a class="nav-item" href="{{ route('news-education.index') }}"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
            <a class="nav-item" href="{{ route('emergency-contacts.index') }}"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
            <a class="nav-item" href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
            <a class="nav-item" href="{{ route('help.index') }}"><i class="fa-regular fa-circle-question w-5 text-center"></i>Bantuan</a>
            <a class="nav-item" href="{{ route('profile.edit') }}"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
            <form action="{{ route('logout') }}" method="POST" class="mt-1">
                @csrf
                <button class="nav-item w-full text-left" type="submit"><i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>Keluar</button>
            </form>
        </nav>
    </aside>
@else
    <x-guest-sidebar active="news" />
@endauth

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Detail Berita</h1>
                    <p class="mt-1 text-sm text-gray-500">Informasi berita dari SIGAP BENCANA</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                @auth <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span> @endauth
                @auth <span class="hidden sm:inline">{{ auth()->user()->name }}</span> @endauth
            </div>
        </header>

        <article class="rounded-lg bg-white p-5 shadow-sm sm:p-8">
            <a href="{{ route('disaster-alerts.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-sigap hover:underline"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Kembali ke Peringatan Bencana</a>
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <span class="rounded bg-rose-100 px-2 py-1 text-xs font-medium text-rose-800">Berita</span>
                @if ($article['category'])<span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $article['category'] }}</span>@endif
            </div>
            <h2 class="mt-3 text-2xl font-semibold leading-tight sm:text-3xl">{{ $article['title'] }}</h2>
            <p class="mt-3 text-sm text-gray-500"><i class="fa-regular fa-clock mr-1" aria-hidden="true"></i>{{ $article['published_at']->locale('id')->translatedFormat('d F Y, H:i') }}</p>
            @if ($article['image_url'])
                <img src="{{ $article['image_url'] }}" alt="" class="mt-6 max-h-[480px] w-full rounded-lg object-cover">
            @else
                <div class="mt-6 flex h-48 items-center justify-center rounded-lg bg-rose-50 text-5xl text-rose-300" aria-label="Gambar berita tidak tersedia"><i class="fa-regular fa-image" aria-hidden="true"></i></div>
            @endif
            @if ($article['excerpt'])<p class="mt-6 text-base font-medium leading-relaxed text-gray-700">{{ $article['excerpt'] }}</p>@endif
            <div class="mt-5 whitespace-pre-line text-sm leading-7 text-gray-700">{{ $article['content'] }}</div>
            @if ($article['source'] || $article['source_url'])
                <div class="mt-7 border-t border-gray-100 pt-4 text-sm text-gray-600">
                    <span>Sumber: {{ $article['source'] ?: 'Tidak dicantumkan' }}</span>
                    @if ($article['source_url'])
                        <a href="{{ $article['source_url'] }}" target="_blank" rel="noopener noreferrer" class="ml-2 text-sigap underline">Buka sumber</a>
                    @endif
                </div>
            @endif
        </article>
    </main>
</div>
<script>
    function toggleMenu() { document.getElementById('sidebar').classList.toggle('open'); }
</script>
</body>
</html>
