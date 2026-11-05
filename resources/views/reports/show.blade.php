<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan Saya - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] },
                    colors: { sigap: '#D2002F', blush: '#FFECED' },
                },
            },
        };
    </script>
    <style>
        body { font-family: Poppins, sans-serif; }
        .nav-item { display: flex; align-items: center; gap: 14px; border-radius: 8px; padding: 10px 12px; color: #191919; }
        .nav-item.active { color: #c9002c; background: #ffb8bd; }
        @media (max-width: 1023px) {
            #sidebar { display: none; }
            #sidebar.open { display: flex; position: fixed; inset: 0 auto 0 0; z-index: 50; width: min(270px, 86vw); overflow-y: auto; box-shadow: 10px 0 30px #0002; }
        }
    </style>
</head>
<body class="min-h-screen bg-blush text-[#181818]">
<div class="min-h-screen lg:flex">
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
            <a class="nav-item active" href="{{ route('reports.index') }}" aria-current="page"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
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

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Detail Laporan Saya</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Informasi lengkap laporan kejadian Anda</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                <span class="text-xl" aria-label="Notifikasi"><i class="fa-regular fa-bell"></i></span>
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        @php
            $categoryLabels = ['gempa' => 'Gempa', 'banjir' => 'Banjir', 'longsor' => 'Tanah Longsor', 'kebakaran' => 'Kebakaran', 'tsunami' => 'Tsunami', 'lainnya' => 'Lainnya'];
            $verificationLabels = ['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];
            $handlingLabels = ['unhandled' => 'Belum ditangani', 'in_progress' => 'Sedang ditangani', 'resolved' => 'Selesai ditangani'];
        @endphp

        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-5">
                <div>
                    <p class="text-sm font-medium text-sigap">{{ $categoryLabels[$report->disaster_type] ?? $report->disaster_type }}</p>
                    <h2 class="mt-1 break-words text-xl font-semibold sm:text-2xl">{{ $report->title }}</h2>
                    <p class="mt-2 text-sm text-gray-500">Dilaporkan {{ $report->created_at?->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-rose-50 px-3 py-1.5 text-rose-800">{{ $verificationLabels[$report->verification_status] ?? $report->verification_status }}</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1.5 text-gray-700">{{ $handlingLabels[$report->handling_status] ?? $report->handling_status }}</span>
                </div>
            </div>

            <dl class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Deskripsi kejadian</dt>
                    <dd class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed">{{ $report->description ?: 'Deskripsi tidak tersedia.' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Alamat kejadian</dt>
                    <dd class="mt-1 break-words text-sm">{{ $report->address ?: 'Alamat tidak tersedia.' }}</dd>
                </div>
                @if ($report->latitude !== null && $report->longitude !== null)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Koordinat</dt>
                        <dd class="mt-1 text-sm">{{ $report->latitude }}, {{ $report->longitude }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Waktu kejadian</dt>
                    <dd class="mt-1 text-sm">{{ $report->occurred_at?->locale('id')->translatedFormat('d F Y, H:i') ?? 'Tidak dicantumkan' }}</dd>
                </div>
            </dl>

            <div class="mt-7 border-t border-gray-100 pt-5">
                <a href="{{ route('reports.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-800 transition hover:border-sigap hover:text-sigap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke Riwayat Laporan Saya
                </a>
            </div>
        </section>
    </main>
</div>
<script>
    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('open');
    }
</script>
</body>
</html>
