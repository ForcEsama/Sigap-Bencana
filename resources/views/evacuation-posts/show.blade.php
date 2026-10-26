<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post['name'] }} - Tempat Evakuasi SIGAP BENCANA</title>
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
            <a class="nav-item text-sigap" href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
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
    <x-guest-sidebar active="evacuation" />
@endauth

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <a href="{{ route('evacuation-posts.index') }}" class="text-sm font-medium text-sigap hover:underline"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>Kembali ke daftar</a>
                    <h1 class="mt-2 text-2xl font-semibold sm:text-3xl">Detail Tempat Evakuasi</h1>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                @auth <x-notification-bell /> @endauth
                @auth <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span> @endauth
                @auth <span class="hidden sm:inline">{{ auth()->user()->name }}</span> @endauth
            </div>
        </header>

        @php
            $status = strtolower(trim((string) $post['status']));
            [$statusLabel, $statusClass] = match ($status) {
                'active' => ['Aktif; ketersediaan belum diketahui', 'bg-blue-50 text-blue-700'],
                'available' => ['Tersedia', 'bg-emerald-50 text-emerald-700'],
                'almost_full', 'almost full' => ['Hampir penuh', 'bg-amber-50 text-amber-800'],
                'full' => ['Penuh', 'bg-rose-50 text-rose-700'],
                'inactive' => ['Tidak aktif', 'bg-gray-100 text-gray-600'],
                default => ['Status belum dikonfirmasi', 'bg-gray-100 text-gray-600'],
            };
            $mapQuery = $post['has_valid_coordinates']
                ? $post['latitude'].','.$post['longitude']
                : (filled($post['address']) ? $post['address'] : null);
            $phone = preg_replace('/[^0-9+]/', '', (string) $post['contact_phone']);
            $phoneIsValid = filled($post['contact_phone']) && preg_match('/^\+?[0-9]{3,20}$/', $phone) === 1;
        @endphp

        <section class="rounded-xl bg-white p-5 shadow-sm sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-[#ffeced] text-3xl text-sigap" aria-hidden="true"><i class="fa-solid fa-tent"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="break-words text-xl font-semibold sm:text-2xl">{{ $post['name'] }}</h2>
                            <p class="mt-1 text-sm text-gray-500">Tempat evakuasi</p>
                        </div>
                        <span class="rounded-full px-3 py-1.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    @if ($post['description'])
                        <p class="mt-5 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $post['description'] }}</p>
                    @endif

                    <dl class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-[#fff4f4] p-4 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-location-dot mr-2 text-sigap" aria-hidden="true"></i>Alamat</dt>
                            <dd class="mt-1 text-sm">{{ $post['address'] ?: 'Alamat belum dicantumkan' }}</dd>
                        </div>
                        @if ($post['capacity'] !== null)
                            <div class="rounded-lg bg-[#fff4f4] p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-people-group mr-2 text-sigap" aria-hidden="true"></i>Kapasitas</dt>
                                <dd class="mt-1 text-sm">{{ $post['capacity'] }} orang</dd>
                            </div>
                        @endif
                        @if ($post['current_occupancy'] !== null && $post['current_occupancy'] > 0)
                            <div class="rounded-lg bg-[#fff4f4] p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-users mr-2 text-sigap" aria-hidden="true"></i>Jumlah penghuni tercatat</dt>
                                <dd class="mt-1 text-sm">{{ $post['current_occupancy'] }} orang</dd>
                            </div>
                        @endif
                        @if ($post['facilities'])
                            <div class="rounded-lg bg-[#fff4f4] p-4 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-list-check mr-2 text-sigap" aria-hidden="true"></i>Fasilitas</dt>
                                <dd class="mt-1 whitespace-pre-line text-sm">{{ $post['facilities'] }}</dd>
                            </div>
                        @endif
                        @if ($post['contact_person'])
                            <div class="rounded-lg bg-[#fff4f4] p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-user mr-2 text-sigap" aria-hidden="true"></i>Kontak</dt>
                                <dd class="mt-1 text-sm">{{ $post['contact_person'] }}</dd>
                            </div>
                        @endif
                        @if ($post['contact_phone'])
                            <div class="rounded-lg bg-[#fff4f4] p-4">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-phone mr-2 text-sigap" aria-hidden="true"></i>Telepon</dt>
                                <dd class="mt-1 text-sm">
                                    @if ($phoneIsValid)
                                        <a class="font-medium text-sigap hover:underline" href="tel:{{ $phone }}">{{ $post['contact_phone'] }}</a>
                                    @else
                                        {{ $post['contact_phone'] }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if ($post['has_valid_coordinates'])
                            <div class="rounded-lg bg-[#fff4f4] p-4 sm:col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500"><i class="fa-solid fa-crosshairs mr-2 text-sigap" aria-hidden="true"></i>Koordinat</dt>
                                <dd class="mt-1 text-sm">{{ $post['latitude'] }}, {{ $post['longitude'] }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if ($mapQuery)
                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($mapQuery) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-sigap px-4 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-sigap focus:ring-offset-2">
                                <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>Buka navigasi
                            </a>
                        @endif
                        <a href="{{ route('evacuation-posts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sigap">
                            <i class="fa-solid fa-list" aria-hidden="true"></i>Daftar lokasi
                        </a>
                    </div>
                </div>
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
