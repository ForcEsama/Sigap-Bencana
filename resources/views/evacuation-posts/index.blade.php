<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempat Evakuasi - SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
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
        #evacuation-map { min-height: 390px; height: min(68vh, 700px); }
        #planning-preview-map { min-height: 430px; height: min(72vh, 760px); }
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
            <a class="nav-item active" href="{{ route('evacuation-posts.index') }}" aria-current="page"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
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
        <header class="mb-5 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Tempat Evakuasi</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Temukan lokasi evakuasi terdekat di wilayah Anda.</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                @auth <x-notification-bell /> @endauth
                @auth <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span> @endauth
                @auth <span class="hidden sm:inline">{{ auth()->user()->name }}</span> @endauth
            </div>
        </header>

        <section class="mb-5 flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:p-5" aria-label="Pencarian tempat evakuasi">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Cari lokasi atau alamat</span>
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input id="search-input" type="search" placeholder="Cari lokasi atau masukkan alamat" class="w-full rounded-lg border border-gray-300 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-sigap focus:ring-2 focus:ring-rose-100">
            </label>
            <label class="sm:w-56">
                <span class="sr-only">Filter wilayah atau alamat</span>
                <select id="area-filter" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-sm outline-none focus:border-sigap focus:ring-2 focus:ring-rose-100">
                    <option value="">Semua wilayah</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area }}">{{ $area }}</option>
                    @endforeach
                </select>
            </label>
            <button id="locate-button" type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-rose-200 px-4 text-sm font-medium text-sigap transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-sigap">
                <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>Gunakan lokasi saya
            </button>
        </section>

        <section class="mb-5 rounded-xl border border-amber-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="planning-preview-heading">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="planning-preview-heading" class="text-lg font-semibold">Pratinjau lokasi TEA dari dokumen perencanaan</h2>
                    <p class="mt-1 text-sm text-gray-600">Sumber awal: <span class="font-medium">LAMP 2 - PETA JALUR EVAKUASI &amp; TABEL IDENTIFIKASI KOTA PADANG.pdf</span></p>
                    <p class="mt-1 text-xs text-gray-500">{{ count($planningDestinations) }} lokasi pada draf CSV</p>
                </div>
            </div>

            @if (count($planningDestinations) > 0)
                <div class="grid items-start gap-4 xl:grid-cols-[minmax(300px,0.85fr)_minmax(0,1.4fr)]">
                  <div class="max-h-[72vh] space-y-3 overflow-y-auto pr-1" aria-label="Daftar kandidat TEA">
                    @foreach ($planningDestinations as $destination)
                        <article class="rounded-xl border border-amber-100 bg-[#fffdf8] p-4">
                            @php
                                $photoCode = (string) $destination['location_code'];
                                $photoFiles = [
                                    'A1' => 'tea-a1.jpg', 'A2' => 'tea-a2.jpg', 'B1' => 'tea-b1.jpg',
                                    'B2' => 'tea-b2.jpg', 'C1' => 'tea-c1.jpg', 'D1' => 'tea-d1.jpg',
                                    'D2' => 'tea-d2.jpg', 'E1' => 'tea-e1.jpg', 'F1' => 'tea-f1.jpg',
                                    'G1' => 'tea-g1.jpg', 'H1' => 'tea-h1.jpg', 'I1' => 'tea-i1.jpg',
                                ];
                                $photoFilename = $photoFiles[$photoCode] ?? null;
                                $photoPath = public_path('images/evacuation/'.$photoFilename);
                                $photoUrl = $photoFilename !== null && is_file($photoPath)
                                    ? asset('images/evacuation/'.$photoFilename)
                                    : null;
                            @endphp
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                <div class="relative flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-center sm:h-32 sm:w-32" @if (! $photoUrl) role="img" aria-label="Foto lokasi belum tersedia" @endif>
                                    @if ($photoUrl)
                                        <img data-evacuation-photo src="{{ $photoUrl }}" alt="Foto kawasan untuk referensi visual" class="h-full w-full object-cover" loading="lazy" decoding="async">
                                        <span data-evacuation-photo-fallback hidden class="px-2 text-xs text-gray-500">Foto lokasi belum tersedia</span>
                                    @else
                                        <span class="px-2 text-xs text-gray-500">Foto lokasi belum tersedia</span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-sigap">{{ $destination['location_code'] }} · {{ $destination['site_type'] }}</p>
                                    <h3 class="mt-1 break-words font-semibold">{{ $destination['name'] }}</h3>
                                </div>
                                <i class="fa-solid fa-flag-checkered mt-1 text-amber-700" aria-hidden="true"></i>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-gray-700"><span class="font-medium">Keterangan lokasi:</span> {{ $destination['address'] }}</p>
                            @if ($destination['capacity'] !== null)
                                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-950">
                                    Daya tampung rekomendasi dokumen: {{ number_format((int) $destination['capacity'], 0, ',', '.') }} jiwa
                                </p>
                            @endif
                            @if ($destination['has_candidate_coordinates'])
                                <button type="button" data-focus-planning="{{ $destination['location_code'] }}" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-sigap hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-sigap">
                                    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>Lihat kandidat di peta
                                </button>
                            @else
                                <p class="mt-2 text-xs text-gray-600">Koordinat kandidat tidak tersedia atau tidak valid; marker tidak dibuat.</p>
                            @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="overflow-hidden rounded-xl border border-amber-100 bg-white p-3 xl:mt-0">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-semibold">Peta pratinjau kandidat TEA</h3>
                            <p class="text-xs text-gray-500">Basemap OpenStreetMap; titik berasal dari CSV koordinat awal.</p>
                        </div>
                    </div>
                    <div id="planning-preview-map" class="overflow-hidden rounded-lg bg-gray-100" role="application" aria-label="Peta pratinjau koordinat kandidat TEA"></div>
                    <p id="planning-map-message" class="mt-3 text-xs leading-relaxed text-gray-600" role="status" aria-live="polite">Memuat peta pratinjau. Posisi marker tidak boleh digunakan sebagai panduan evakuasi.</p>
                </div>
                </div>
            @else
                <p class="rounded-lg border border-dashed border-amber-200 px-4 py-8 text-center text-sm text-gray-600">Pratinjau CSV tidak tersedia atau format berkas tidak sesuai.</p>
            @endif
        </section>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(340px,0.9fr)_minmax(0,1.1fr)]">
            <section class="rounded-xl bg-white p-4 shadow-sm sm:p-5" aria-labelledby="evacuation-list-heading">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 id="evacuation-list-heading" class="text-lg font-semibold">Lokasi Evakuasi</h2>
                        <p id="result-count" class="mt-0.5 text-xs text-gray-500">{{ $posts->count() }} lokasi</p>
                    </div>
                    <i class="fa-solid fa-list-ul text-sigap" aria-hidden="true"></i>
                </div>

                <div id="no-posts" @if ($posts->isNotEmpty()) hidden @endif class="rounded-lg border border-dashed border-rose-200 bg-[#fff8f8] px-5 py-12 text-center">
                    <i class="fa-solid fa-tent mb-3 text-3xl text-gray-400" aria-hidden="true"></i>
                    <h3 class="font-semibold">Data tempat evakuasi belum tersedia</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-gray-600">Belum ada lokasi yang tercatat untuk ditampilkan.</p>
                </div>
                <div id="no-results" hidden class="rounded-lg border border-dashed border-rose-200 bg-[#fff8f8] px-5 py-12 text-center">
                    <i class="fa-solid fa-magnifying-glass mb-3 text-3xl text-gray-400" aria-hidden="true"></i>
                    <h3 class="font-semibold">Lokasi tidak ditemukan</h3>
                    <p class="mt-2 text-sm text-gray-600">Coba ubah kata pencarian atau filter wilayah.</p>
                </div>

                <div id="post-list" class="max-h-[72vh] space-y-3 overflow-y-auto pr-1">
                    @foreach ($posts as $post)
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
                        @endphp
                        <article data-post-card="{{ $post['id'] }}" data-name="{{ mb_strtolower($post['name']) }}" data-address="{{ mb_strtolower($post['address']) }}" class="rounded-xl border border-rose-100 p-4 transition hover:border-rose-300 hover:shadow-sm">
                            <div class="flex items-start gap-3">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-[#ffeced] text-xl text-sigap" aria-hidden="true"><i class="fa-solid fa-tent"></i></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h3 class="break-words font-semibold">{{ $post['name'] }}</h3>
                                            <p class="mt-0.5 text-xs text-gray-500">Tempat evakuasi</p>
                                        </div>
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </div>
                                    <p class="mt-2 flex gap-2 text-sm leading-relaxed text-gray-600">
                                        <i class="fa-solid fa-location-dot mt-1 shrink-0 text-sigap" aria-hidden="true"></i>
                                        <span>{{ $post['address'] ?: 'Alamat belum dicantumkan' }}</span>
                                    </p>
                                    @if ($post['capacity'] !== null)
                                        <p class="mt-2 text-xs text-gray-500"><i class="fa-solid fa-people-group mr-1" aria-hidden="true"></i>Kapasitas: {{ $post['capacity'] }} orang</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-rose-100 pt-3">
                                        <button type="button" data-focus-post="{{ $post['id'] }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-sigap hover:underline focus:outline-none focus:ring-2 focus:ring-sigap" @disabled(! $post['has_valid_coordinates'])>
                                            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>Lihat di peta
                                        </button>
                                        <a href="{{ route('evacuation-posts.show', $post['id']) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-sigap focus:outline-none focus:ring-2 focus:ring-sigap">
                                            Detail<i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
                                        </a>
                                        @if ($mapQuery)
                                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($mapQuery) }}" target="_blank" rel="noopener noreferrer" class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-sigap focus:outline-none focus:ring-2 focus:ring-sigap">
                                                <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>Navigasi
                                            </a>
                                        @endif
                                    </div>
                                    <p data-distance-for="{{ $post['id'] }}" hidden class="mt-2 text-xs text-gray-500"></p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-xl bg-white p-3 shadow-sm sm:p-4" aria-labelledby="map-heading">
                <div class="mb-3 flex items-center justify-between gap-3 px-1">
                    <div>
                        <h2 id="map-heading" class="font-semibold">Peta Lokasi</h2>
                        <p class="text-xs text-gray-500">Marker hanya ditampilkan untuk koordinat yang valid.</p>
                    </div>
                    <span class="flex items-center gap-2 text-xs text-gray-500"><i class="fa-solid fa-location-dot text-sigap" aria-hidden="true"></i>Tempat evakuasi</span>
                </div>
                @if ($posts->contains('has_valid_coordinates', true))
                    <div id="evacuation-map" class="overflow-hidden rounded-lg bg-gray-100" role="application" aria-label="Peta lokasi tempat evakuasi"></div>
                    <p id="map-message" class="mt-3 text-xs leading-relaxed text-gray-500" role="status" aria-live="polite">Memuat peta.</p>
                @else
                    <div class="flex min-h-[390px] flex-col items-center justify-center rounded-lg border border-dashed border-rose-200 bg-[#fff8f8] px-5 py-10 text-center">
                        <i class="fa-solid fa-map-location-dot mb-3 text-3xl text-gray-400" aria-hidden="true"></i>
                        @if ($posts->isEmpty())
                            <h3 class="font-semibold">Belum ada lokasi dengan koordinat untuk dipetakan</h3>
                            <p class="mt-2 max-w-sm text-sm leading-relaxed text-gray-600">Pratinjau TEA dari dokumen perencanaan belum memiliki koordinat terverifikasi, sehingga tidak dibuatkan marker.</p>
                        @else
                            <h3 class="font-semibold">Lokasi belum dapat dipetakan</h3>
                            <p class="mt-2 max-w-sm text-sm leading-relaxed text-gray-600">Lokasi tetap tersedia di daftar, tetapi belum memiliki koordinat dalam rentang yang valid untuk marker.</p>
                        @endif
                    </div>
                    <p id="map-message" class="sr-only" role="status" aria-live="polite"></p>
                @endif
            </section>
        </div>
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.querySelectorAll('[data-evacuation-photo]').forEach((image) => {
        image.addEventListener('error', () => {
            image.hidden = true;
            const fallback = image.nextElementSibling;
            if (fallback) fallback.hidden = false;
            image.parentElement.setAttribute('role', 'img');
            image.parentElement.setAttribute('aria-label', 'Foto lokasi belum tersedia');
        });
    });

    const evacuationPosts = @json($posts);
    const planningDestinations = @json($planningDestinations);
    const planningMapElement = document.getElementById('planning-preview-map');
    const planningMapMessage = document.getElementById('planning-map-message');
    const locateButton = document.getElementById('locate-button');
    const planningMarkers = new Map();
    const mappedPlanningDestinations = planningDestinations.filter((destination) =>
        destination.has_candidate_coordinates
        && Number.isFinite(Number(destination.latitude))
        && Number.isFinite(Number(destination.longitude))
        && Number(destination.latitude) >= -90
        && Number(destination.latitude) <= 90
        && Number(destination.longitude) >= -180
        && Number(destination.longitude) <= 180
    );
    let planningMap = null;
    let planningUserMarker = null;

    function createPlanningMap(center = null, zoom = 13) {
        if (!planningMapElement || typeof L === 'undefined') return null;

        planningMap = L.map(planningMapElement, { scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(planningMap);
        if (center) planningMap.setView(center, zoom);

        return planningMap;
    }

    if (planningMapElement && typeof L !== 'undefined' && mappedPlanningDestinations.length > 0) {
        createPlanningMap();

        function planningPopup(destination) {
            const wrapper = document.createElement('div');
            wrapper.style.width = '230px';
            wrapper.style.maxWidth = '68vw';

            const code = document.createElement('p');
            code.textContent = `${destination.location_code} / ${destination.site_type}`;
            code.className = 'mt-2 font-semibold';
            wrapper.appendChild(code);

            if (destination.image_path) {
                const image = document.createElement('img');
                image.src = `/${destination.image_path}`;
                image.alt = 'Foto kawasan untuk referensi visual';
                image.width = 56;
                image.height = 56;
                image.style.width = '56px';
                image.style.height = '56px';
                image.style.objectFit = 'cover';
                image.style.borderRadius = '6px';
                image.style.float = 'right';
                image.style.margin = '0 0 8px 10px';
                wrapper.appendChild(image);
            }

            const name = document.createElement('p');
            name.textContent = destination.name;
            wrapper.appendChild(name);

            if (destination.capacity !== null) {
                const capacity = document.createElement('p');
                capacity.textContent = `Daya tampung rekomendasi dokumen: ${Number(destination.capacity).toLocaleString('id-ID')} jiwa`;
                capacity.className = 'mt-1';
                wrapper.appendChild(capacity);
            }

            const caution = document.createElement('p');
            caution.textContent = 'Hanya untuk pemeriksaan visual; bukan panduan evakuasi.';
            caution.className = 'mt-2 text-red-800';
            wrapper.appendChild(caution);
            return wrapper;
        }

        mappedPlanningDestinations.forEach((destination) => {
            const marker = L.marker([Number(destination.latitude), Number(destination.longitude)]).addTo(planningMap);
            marker.bindPopup(planningPopup(destination));
            marker.on('click', () => {
                const card = document.getElementById(`planning-card-${destination.location_code}`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    card.classList.add('ring-2', 'ring-red-300');
                    window.setTimeout(() => card.classList.remove('ring-2', 'ring-red-300'), 1800);
                }
            });
            planningMarkers.set(String(destination.location_code), marker);
        });

        planningMap.fitBounds(L.latLngBounds(mappedPlanningDestinations.map((destination) => [Number(destination.latitude), Number(destination.longitude)])).pad(0.12));
        planningMapMessage.textContent = `${mappedPlanningDestinations.length} marker kandidat ditampilkan dari CSV koordinat awal. Semua belum diverifikasi dan tidak boleh digunakan sebagai panduan evakuasi.`;
    } else if (planningMapElement) {
        planningMapMessage.textContent = mappedPlanningDestinations.length === 0
            ? 'Tidak ada koordinat kandidat valid untuk ditampilkan sebagai marker.'
            : 'Peta tidak dapat dimuat. Daftar kandidat tetap tersedia.';
    }

    document.querySelectorAll('[data-focus-planning]').forEach((button) => {
        button.addEventListener('click', () => {
            const marker = planningMarkers.get(button.dataset.focusPlanning);
            if (!planningMap || !marker) return;
            planningMap.setView(marker.getLatLng(), 14);
            marker.openPopup();
        });
    });

    const mapElement = document.getElementById('evacuation-map');
    const mapMessage = document.getElementById('map-message');
    let map = null;
    const markers = new Map();
    const validPosts = evacuationPosts.filter((post) => post.has_valid_coordinates);

    if (mapElement && typeof L !== 'undefined') {
        map = L.map(mapElement, { scrollWheelZoom: false }).setView([validPosts[0].latitude, validPosts[0].longitude], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
    } else if (mapElement) {
        mapMessage.textContent = 'Peta tidak dapat dimuat. Daftar lokasi tetap dapat digunakan.';
    }

    function popupContent(post) {
        const wrapper = document.createElement('div');
        const title = document.createElement('strong');
        title.textContent = post.name;
        wrapper.appendChild(title);

        const address = document.createElement('p');
        address.textContent = post.address || 'Alamat belum dicantumkan';
        address.className = 'mt-1';
        wrapper.appendChild(address);

        const detail = document.createElement('a');
        detail.textContent = 'Lihat detail';
        detail.href = post.detail_url;
        detail.className = 'mt-2 inline-block text-red-700 underline';
        wrapper.appendChild(detail);

        return wrapper;
    }

    if (map) {
        validPosts.forEach((post) => {
            const marker = L.marker([post.latitude, post.longitude]).addTo(map);
            marker.bindPopup(popupContent(post));
            markers.set(String(post.id), marker);
        });

        if (validPosts.length > 1) {
            map.fitBounds(L.latLngBounds(validPosts.map((post) => [post.latitude, post.longitude])).pad(0.15));
        }
    }

    const searchInput = document.getElementById('search-input');
    const areaFilter = document.getElementById('area-filter');
    const cards = Array.from(document.querySelectorAll('[data-post-card]'));
    const emptyPosts = document.getElementById('no-posts');
    const noResults = document.getElementById('no-results');
    const resultCount = document.getElementById('result-count');

    function filterPosts() {
        const term = searchInput.value.trim().toLocaleLowerCase('id');
        const area = areaFilter.value.toLocaleLowerCase('id');
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesTerm = !term || card.dataset.name.includes(term) || card.dataset.address.includes(term);
            const matchesArea = !area || card.dataset.address === area;
            const visible = matchesTerm && matchesArea;
            card.hidden = !visible;
            if (visible) visibleCount++;
        });

        emptyPosts.hidden = cards.length !== 0;
        noResults.hidden = cards.length === 0 || visibleCount !== 0;
        resultCount.textContent = `${visibleCount} lokasi`;

        markers.forEach((marker, id) => {
            if (!map) return;
            const card = document.querySelector(`[data-post-card="${CSS.escape(id)}"]`);
            const shouldShow = card && !card.hidden;
            if (shouldShow && !map.hasLayer(marker)) marker.addTo(map);
            if (!shouldShow && map.hasLayer(marker)) map.removeLayer(marker);
        });
    }

    searchInput.addEventListener('input', filterPosts);
    areaFilter.addEventListener('change', filterPosts);

    document.querySelectorAll('[data-focus-post]').forEach((button) => {
        if (!map) button.disabled = true;
        button.addEventListener('click', () => {
            const marker = markers.get(button.dataset.focusPost);
            if (!map || !marker) return;
            map.setView(marker.getLatLng(), 15);
            marker.openPopup();
        });
    });

    let userMapMarker = null;
    locateButton.addEventListener('click', () => {
        const locationMessage = planningMapMessage ?? mapMessage;

        if (window.isSecureContext === false) {
            locationMessage.textContent = 'Lokasi perangkat hanya dapat digunakan melalui HTTPS atau localhost. Peta kandidat tetap dapat digunakan.';
            return;
        }
        if (!navigator.geolocation) {
            locationMessage.textContent = 'Browser ini tidak mendukung geolokasi. Peta kandidat tetap dapat digunakan.';
            return;
        }

        locateButton.disabled = true;
        locateButton.setAttribute('aria-busy', 'true');
        locationMessage.textContent = 'Meminta izin lokasi perangkat…';
        navigator.geolocation.getCurrentPosition((position) => {
            const latitude = Number(position.coords.latitude);
            const longitude = Number(position.coords.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) {
                locationMessage.textContent = 'Perangkat mengembalikan koordinat yang tidak valid. Peta tidak dipindahkan.';
                locateButton.disabled = false;
                locateButton.removeAttribute('aria-busy');
                return;
            }

            const origin = [latitude, longitude];
            const earthRadiusKm = 6371;

            function straightLineDistanceKm(destinationLatitude, destinationLongitude) {
                const toRadians = (degrees) => degrees * Math.PI / 180;
                const latitudeDelta = toRadians(destinationLatitude - latitude);
                const longitudeDelta = toRadians(destinationLongitude - longitude);
                const value = Math.sin(latitudeDelta / 2) ** 2
                    + Math.cos(toRadians(latitude)) * Math.cos(toRadians(destinationLatitude)) * Math.sin(longitudeDelta / 2) ** 2;
                return 2 * earthRadiusKm * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
            }

            let previewLocationDisplayed = false;
            evacuationPosts.forEach((post) => {
                const distanceLabel = document.querySelector(`[data-distance-for="${CSS.escape(String(post.id))}"]`);
                if (!distanceLabel || !post.has_valid_coordinates) return;

                const distance = straightLineDistanceKm(post.latitude, post.longitude);
                distanceLabel.textContent = `Jarak garis lurus sekitar ${distance < 1 ? `${Math.round(distance * 1000)} m` : `${distance.toFixed(1)} km`}`;
                distanceLabel.hidden = false;
            });

            if (planningMapElement && typeof L !== 'undefined') {
                if (!planningMap) createPlanningMap(origin, 13);
                else planningMap.setView(origin, 13);

                if (planningMap) {
                    if (!planningUserMarker) {
                        planningUserMarker = L.circleMarker(origin, {
                            radius: 9,
                            color: '#ffffff',
                            weight: 3,
                            fillColor: '#2563eb',
                            fillOpacity: 1,
                        }).addTo(planningMap);
                        planningUserMarker.bindTooltip('Lokasi Anda (dari perangkat)');
                    } else {
                        planningUserMarker.setLatLng(origin).addTo(planningMap);
                    }
                    planningMap.invalidateSize();
                    previewLocationDisplayed = true;
                }
            }

            if (map) {
                map.setView(origin, 11);
                if (!userMapMarker) {
                    userMapMarker = L.circleMarker(origin, { radius: 7, color: '#2563eb', fillColor: '#60a5fa', fillOpacity: 0.9 }).addTo(map);
                    userMapMarker.bindTooltip('Lokasi Anda (dari perangkat)');
                } else {
                    userMapMarker.setLatLng(origin).addTo(map);
                }
            }

            let nearestCandidateMessage = 'Tidak ada koordinat kandidat TEA yang valid; hanya posisi Anda yang ditampilkan.';
            if (previewLocationDisplayed && mappedPlanningDestinations.length > 0) {
                const nearest = mappedPlanningDestinations
                    .map((destination) => ({ destination, distance: straightLineDistanceKm(Number(destination.latitude), Number(destination.longitude)) }))
                    .sort((first, second) => first.distance - second.distance)[0];
                const distanceLabel = nearest.distance < 1
                    ? `${Math.round(nearest.distance * 1000)} m`
                    : `${nearest.distance.toFixed(1)} km`;
                nearestCandidateMessage = `Kandidat terdekat berdasarkan koordinat awal: ${nearest.destination.location_code} (sekitar ${distanceLabel} garis lurus). Koordinat belum diverifikasi; jarak ini bukan jarak perjalanan atau rute evakuasi.`;
            }

            locationMessage.textContent = previewLocationDisplayed
                ? `Posisi perangkat ditampilkan pada peta pratinjau. ${nearestCandidateMessage}`
                : 'Lokasi perangkat berhasil diperoleh, tetapi peta pratinjau tidak dapat dimuat.';
            if (mapMessage && map) {
                mapMessage.textContent = `Lokasi perangkat ditampilkan. Jarak garis lurus dari kandidat database bukan jarak rute jalan.`;
            }
            locateButton.disabled = false;
            locateButton.removeAttribute('aria-busy');
        }, (error) => {
            if (error.code === error.PERMISSION_DENIED) {
                locationMessage.textContent = 'Izin lokasi ditolak. Tidak ada posisi pengguna yang ditampilkan.';
            } else if (error.code === error.TIMEOUT) {
                locationMessage.textContent = 'Permintaan lokasi melewati batas waktu. Peta tidak dipindahkan.';
            } else {
                locationMessage.textContent = 'Perangkat tidak dapat menentukan lokasi. Peta tidak dipindahkan.';
            }
            locateButton.disabled = false;
            locateButton.removeAttribute('aria-busy');
        }, { enableHighAccuracy: false, timeout: 12000, maximumAge: 60000 });
    });

    if (validPosts.length > 0 && map) {
        mapMessage.textContent = `Menampilkan ${validPosts.length} lokasi dengan koordinat valid. Peta menggunakan OpenStreetMap.`;
    }

    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('open');
    }
</script>
</body>
</html>
