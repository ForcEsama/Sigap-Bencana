<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan Saya - SIGAP BENCANA</title>
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
            <span class="nav-item"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</span>
            @if (auth()->user()->role === 'masyarakat')
                <a class="nav-item" href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            @else
                <span class="nav-item"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Resiko</span>
            @endif
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item active" href="{{ route('reports.index') }}" aria-current="page"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
            <span class="nav-item"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</span>
            <span class="nav-item"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</span>
            <span class="nav-item"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</span>
            <span class="nav-item"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</span>
            <span class="nav-item"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</span>
            @if (auth()->user()->role === 'masyarakat')
                <a class="nav-item" href="{{ route('profile.edit') }}"><i class="fa-regular fa-user w-5 text-center"></i>Profil</a>
            @else
                <span class="nav-item"><i class="fa-regular fa-user w-5 text-center"></i>Profil</span>
            @endif
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
                    <h1 class="text-2xl font-semibold sm:text-3xl">Riwayat Laporan Saya</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Pantau laporan kejadian yang telah Anda kirim</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                <span class="text-xl" aria-label="Notifikasi"><i class="fa-regular fa-bell"></i></span>
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold">Laporan Terbaru</h2>
                <a href="{{ route('reports.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-sigap px-4 py-2 text-sm font-medium text-white transition hover:bg-red-800">
                    <i class="fa-solid fa-plus"></i> Lapor Kejadian
                </a>
            </div>

            @forelse ($reports as $report)
                @php
                    $verificationLabels = ['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];
                    $handlingLabels = ['unhandled' => 'Belum ditangani', 'in_progress' => 'Sedang ditangani', 'resolved' => 'Selesai ditangani'];
                    $categoryLabels = ['gempa' => 'Gempa', 'banjir' => 'Banjir', 'longsor' => 'Tanah Longsor', 'kebakaran' => 'Kebakaran', 'tsunami' => 'Tsunami', 'lainnya' => 'Lainnya'];
                @endphp
                <a href="{{ route('reports.show', $report->id) }}" class="group mb-4 block rounded-lg border border-gray-200 text-inherit no-underline transition hover:border-rose-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">
                <article class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-sigap">{{ $categoryLabels[$report->disaster_type] ?? $report->disaster_type }}</p>
                            <h3 class="mt-1 text-lg font-semibold">{{ $report->title }}</h3>
                            <p class="mt-2 text-sm text-gray-600"><i class="fa-solid fa-location-dot mr-1 text-sigap"></i>{{ $report->address ?: 'Alamat tidak tersedia' }}</p>
                            <p class="mt-2 text-xs text-gray-500">Dilaporkan {{ $report->created_at?->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-rose-50 px-3 py-1.5 text-rose-800">{{ $verificationLabels[$report->verification_status] ?? $report->verification_status }}</span>
                            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-gray-700">{{ $handlingLabels[$report->handling_status] ?? $report->handling_status }}</span>
                        </div>
                        <span class="self-center text-gray-400 transition group-hover:text-sigap" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                    </div>
                </article>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-rose-200 bg-[#fff8f8] px-5 py-12 text-center">
                    <i class="fa-regular fa-clipboard mb-3 text-4xl text-gray-400"></i>
                    <h3 class="text-lg font-semibold">Belum ada laporan</h3>
                    <p class="mt-1 text-sm text-gray-600">Laporan yang Anda kirim akan muncul di halaman ini.</p>
                    <a href="{{ route('reports.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-sigap px-4 py-2 text-sm font-medium text-white hover:bg-red-800">
                        <i class="fa-solid fa-plus"></i> Buat Laporan
                    </a>
                </div>
            @endforelse

            @if ($reports->hasPages())
                <div class="mt-6">{{ $reports->links() }}</div>
            @endif
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
