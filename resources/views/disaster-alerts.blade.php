<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringatan Bencana - SIGAP BENCANA</title>
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
        .nav-item.active { color: #c9002c; background: #ffb8bd; }
        .alert-list { display: grid; gap: 12px; width: 100%; min-width: 0; }
        .alert-card { display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 16px; width: 100%; min-width: 0; padding: 16px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; box-shadow: 0 1px 2px #0000000d; }
        .alert-card__image, .alert-card__image-fallback { display: flex; width: 120px; height: 120px; flex: 0 0 120px; align-items: center; justify-content: center; border-radius: 7px; object-fit: cover; }
        .alert-card__media { position: relative; width: 120px; height: 120px; min-width: 0; }
        .alert-card__media .alert-card__image, .alert-card__media .alert-card__image-fallback { width: 100%; height: 100%; }
        .alert-card__image-fallback { background: #fff1f2; color: #fda4af; font-size: 24px; }
        .alert-card__body { display: grid; grid-template-columns: minmax(0, 1fr) 140px; gap: 16px; min-width: 0; }
        .alert-card__copy { min-width: 0; overflow-wrap: break-word; word-break: normal; white-space: normal; }
        .alert-card__side { display: flex; min-width: 0; flex-direction: column; align-items: flex-end; justify-content: space-between; gap: 10px; }
        .alert-card__badges { display: flex; max-width: 100%; flex-wrap: wrap; justify-content: flex-end; gap: 4px; }
        .alert-card__title, .alert-card__summary, .alert-card__metadata { max-width: 100%; overflow-wrap: break-word; word-break: normal; white-space: normal; }
        @media (max-width: 1023px) {
            #sidebar { display: none; }
            #sidebar.open { display: flex; position: fixed; inset: 0 auto 0 0; z-index: 50; width: min(270px, 86vw); overflow-y: auto; box-shadow: 10px 0 30px #0002; }
        }
        @media (max-width: 767px) {
            .alert-card { grid-template-columns: 88px minmax(0, 1fr); gap: 12px; padding: 12px; }
            .alert-card__media { width: 88px; height: 88px; }
            .alert-card__image, .alert-card__image-fallback { flex-basis: 88px; }
            .alert-card__body { grid-template-columns: minmax(0, 1fr); gap: 10px; }
            .alert-card__side { flex-direction: row; align-items: center; justify-content: space-between; border-top: 1px solid #f3f4f6; padding-top: 8px; }
            .alert-card__badges { justify-content: flex-start; }
        }
        @media (max-width: 420px) {
            .alert-card { grid-template-columns: 72px minmax(0, 1fr); gap: 10px; padding: 10px; }
            .alert-card__media { width: 72px; height: 72px; }
            .alert-card__image, .alert-card__image-fallback { flex-basis: 72px; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#FFECED] text-[#181818]">
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
            <a class="nav-item active" href="{{ route('disaster-alerts.index') }}" aria-current="page"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
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
    <x-guest-sidebar active="warnings" />
@endauth

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Peringatan Bencana</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Peringatan resmi aktif dan catatan kejadian terbaru dari BMKG serta BNPB</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                @auth <x-notification-bell /> @endauth
                @auth <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span> @endauth
                @auth <span class="hidden sm:inline">{{ auth()->user()->name }}</span> @endauth
            </div>
        </header>

        <form action="{{ route('disaster-alerts.index') }}" method="GET" class="mb-4 grid min-w-0 gap-2 sm:grid-cols-[minmax(0,1fr)_170px_190px_auto] sm:gap-3">
            <label class="relative block min-w-0">
                <span class="sr-only">Cari peringatan aktif</span>
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $search }}" maxlength="120" placeholder="Cari peringatan aktif..." class="w-full rounded-md border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm outline-none focus:border-sigap focus:ring-2 focus:ring-rose-100">
            </label>
            <label>
                <span class="sr-only">Filter jenis bencana</span>
                <select name="type" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-sigap focus:ring-2 focus:ring-rose-100">
                    <option value="">Semua jenis</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected($typeFilter === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Filter wilayah</span>
                <select name="region" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-sigap focus:ring-2 focus:ring-rose-100">
                    <option value="">Semua wilayah</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region }}" @selected($regionFilter === $region)>{{ $region }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-md bg-sigap px-4 py-2.5 text-sm font-medium text-white bg-red-700 hover:bg-red-800">Terapkan</button>
        </form>

        @if ($incidentFeedStatus === 'unavailable')
            <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                Catatan kejadian BNPB belum dapat dihubungi. Peringatan BMKG tetap ditampilkan jika tersedia.
            </p>
        @elseif ($incidentFeedStatus === 'stale')
            <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                Catatan kejadian BNPB menggunakan data tersimpan karena pembaruan sumber gagal.
            </p>
        @endif

        @if ($cards === [])
            <section class="rounded-lg border border-dashed border-rose-200 bg-white px-5 py-12 text-center shadow-sm" aria-live="polite">
                <i class="fa-regular fa-newspaper mb-3 text-4xl text-gray-400" aria-hidden="true"></i>
                <h2 class="font-semibold">{{ $allSourcesUnavailable ? 'Informasi bencana belum dapat dimuat.' : (($search !== '' || $typeFilter !== '' || $regionFilter !== '') ? 'Tidak ada informasi yang cocok dengan pencarian atau filter.' : 'Tidak ada peringatan aktif atau catatan kejadian terbaru saat ini.') }}</h2>
                @if ($allSourcesUnavailable)
                    <p class="mt-2 text-sm text-gray-600">Sumber resmi sedang tidak tersedia. Silakan coba kembali nanti.</p>
                @endif
            </section>
        @else
            <section class="alert-list" aria-label="Daftar peringatan resmi yang aktif">
                @foreach ($cards as $card)
                    <article class="alert-card">
                        <div class="alert-card__media">
                            @if ($card['image_url'])
                                <img src="{{ $card['image_url'] }}" alt="{{ $card['image_label'] | $card['title'] }}" data-fallback-src="{{ $card['image_fallback_url'] | '' }}" data-fallback-label="{{ $card['image_fallback_label'] | '' }}" loading="lazy" onerror="if(this.dataset.fallbackSrc&amp;&amp;this.dataset.fallbackSrc!==this.src){this.src=this.dataset.fallbackSrc;this.alt=this.dataset.fallbackLabel||this.alt;return;}this.hidden=true;this.nextElementSibling.hidden=false" class="alert-card__image">
                                <div hidden class="alert-card__image-fallback" aria-label="Gambar tidak tersedia">
                                    <i class="fa-regular fa-image" aria-hidden="true"></i>
                                </div>
                            @else
                                <div class="alert-card__image-fallback" aria-label="Gambar tidak tersedia">
                                    <i class="fa-regular fa-image" aria-hidden="true"></i>
                                </div>
                            @endif
                        </div>
                        <div class="alert-card__body">
                            <div class="alert-card__copy">
                                <div class="mb-1 flex flex-wrap items-center gap-1.5">
                                    @if ($card['kind'] === 'warning')
                                        <span class="inline-flex max-w-full rounded bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-900 sm:text-xs">Peringatan Dini</span>
                                    @elseif ($card['kind'] === 'incident')
                                        <span class="inline-flex max-w-full rounded bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-800 sm:text-xs">Kejadian Tercatat</span>
                                    @endif
                                    <span class="inline-flex max-w-full rounded bg-rose-100 px-2 py-1 text-[11px] font-medium text-rose-800 sm:text-sm">{{ $card['type_label'] }}</span>
                                </div>
                                <h2 class="alert-card__title mt-1 text-base font-semibold leading-snug sm:text-xl">{{ $card['title'] }}</h2>
                                @if ($card['region'])
                                    <p class="mt-1 text-[14px] text-gray-600 sm:text-sm">Wilayah: {{ $card['region'] }}</p>
                                @endif
                                @if ($card['summary'])
                                    <p class="alert-card__summary mt-1 line-clamp-2 text-base leading-relaxed text-gray-600">{{ $card['summary'] }}</p>
                                @endif
                                @if ($card['details'])
                                    <p class="mt-1 line-clamp-1 break-words text-[14px] text-gray-600">{{ implode(' · ', $card['details']) }}</p>
                                @endif
                                @if ($card['starts_at'] && $card['expires_at'])
                                    <p class="mt-1 text-[14px] text-gray-600">Berlaku {{ \Carbon\Carbon::parse($card['starts_at'])->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y H:i') }}–{{ \Carbon\Carbon::parse($card['expires_at'])->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y H:i') }} WIB</p>
                                @endif
                                <p class="alert-card__metadata mt-2 text-[12px] text-gray-500 sm:text-[14px]">
                                    @if ($card['published_at'])
                                        <i class="fa-regular fa-clock mr-1" aria-hidden="true"></i>{{ $card['time_label'] }} {{ \Carbon\Carbon::parse($card['published_at'])->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y H:i') }} WIB
                                    @else
                                        Tanggal tidak tersedia
                                    @endif
                                    · {{ $card['source'] }}
                                    @if ($card['feed_status'] === 'stale' && $card['fetched_at'])
                                        · Cache diperbarui {{ \Carbon\Carbon::parse($card['fetched_at'])->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y H:i') }} WIB
                                    @endif
                                </p>
                            </div>
                            <aside class="alert-card__side">
                                <div class="alert-card__badges">
                                    @if ($card['handling_status'])
                                        <span class="rounded bg-emerald-100 px-2 py-1 text-[10px] font-medium text-emerald-800">{{ $card['handling_status'] }}</span>
                                    @endif
                                </div>
                                <a href="{{ $card['source_url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center gap-1 text-base font-medium text-red-700 hover:underline">Lihat Detail <span aria-hidden="true">→</span></a>
                            </aside>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
</div>
<script>
    function toggleMenu() { document.getElementById('sidebar').classList.toggle('open'); }
</script>
</body>
</html>
