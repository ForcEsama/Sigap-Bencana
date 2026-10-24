<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Risiko - SIGAP BENCANA</title>
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
        #risk-map { min-height: 360px; height: min(62vh, 640px); }
        .leaflet-control-layers { max-width: min(250px, calc(100vw - 56px)); }
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
            <a class="nav-item active" href="{{ route('risk-map.index') }}" aria-current="page"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
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
                    <h1 class="text-2xl font-semibold sm:text-3xl">Peta Risiko</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Informasi zona risiko berdasarkan data yang tersedia</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                <span class="text-xl" aria-label="Notifikasi"><i class="fa-regular fa-bell"></i></span>
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <section class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-4 rounded-lg border border-rose-100 bg-[#fff8f8] p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Indeks Risiko Banjir — InaRISK</h2>
                        <p class="mt-1 text-sm text-gray-600">Overlay raster InaRISK/BNPB ditampilkan terpisah dari zona risiko lokal.</p>
                    </div>
                    <a href="https://gis.bnpb.go.id/server/rest/services/inarisk/layer_risiko_banjir/ImageServer" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-sigap underline">Sumber layer: InaRISK/BNPB</a>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-gray-600">Tanggal pembaruan dataset dan legenda kelas risiko untuk layer ini belum terverifikasi. SIGAP BENCANA tidak menafsirkan nilai piksel menjadi kelas risiko.</p>
                <p id="inarisk-status" class="mt-2 text-sm text-gray-600" role="status" aria-live="polite">Menyiapkan layer InaRISK…</p>
            </div>

            <div id="risk-map" class="overflow-hidden rounded-lg" role="application" aria-label="Peta zona risiko bencana"></div>
            <p class="mt-2 text-xs text-gray-500">Lingkaran zona lokal menunjukkan radius perkiraan dari titik pusat, bukan batas wilayah resmi.</p>

                <div class="mt-5 rounded-lg bg-[#fff8f8] p-4">
                    <h2 class="mb-3 font-semibold">Legenda zona risiko lokal</h2>
                    <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm text-gray-700">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-red-600"></span>Risiko tinggi</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-amber-500"></span>Risiko sedang</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-600"></span>Risiko rendah</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-500"></span>Tingkat lain/tidak dikenali</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full border-2 border-slate-500 bg-slate-200/70"></span>Radius perkiraan bila tersedia</span>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Simbol dan warna membantu membedakan kategori; radius bukan batas wilayah resmi.</p>
                </div>

            @if ($zones->isEmpty())
                <div class="mt-5 rounded-lg border border-dashed border-rose-200 bg-[#fff8f8] px-5 py-10 text-center">
                    <i class="fa-solid fa-map-location-dot mb-3 text-4xl text-gray-400" aria-hidden="true"></i>
                    <h2 class="text-lg font-semibold">Data zona risiko lokal belum tersedia</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-relaxed text-gray-600">Belum ada zona lokal aktif. Overlay InaRISK tetap dapat diatur secara terpisah melalui kontrol layer pada peta.</p>
                </div>
            @else
                <div class="mt-7">
                    <h2 class="mb-4 text-xl font-semibold">Zona aktif</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($zones as $zone)
                            @php
                                $categoryLabels = ['gempa' => 'Gempa', 'banjir' => 'Banjir', 'longsor' => 'Tanah Longsor', 'kebakaran' => 'Kebakaran', 'tsunami' => 'Tsunami', 'lainnya' => 'Lainnya'];
                            @endphp
                            <article class="rounded-lg border border-gray-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <h3 class="font-semibold">{{ $zone->name }}</h3>
                                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs text-rose-800">{{ $zone->risk_level }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $categoryLabels[$zone->disaster_type] ?? $zone->disaster_type }} · {{ $zone->region_name }}</p>
                                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="text-xs text-gray-500">Sumber</dt>
                                        <dd>{{ $zone->source ?: 'Sumber belum dicantumkan' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500">Tanggal data</dt>
                                        <dd>{{ $zone->data_date?->locale('id')->translatedFormat('d F Y') ?? 'Tanggal data tidak tersedia' }}</dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                </div>

            @endif

            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script src="https://unpkg.com/esri-leaflet@3.0.19/dist/esri-leaflet.js"></script>
            <script>
                    const riskZones = @js($mapZones);
                    const map = L.map('risk-map', { scrollWheelZoom: false }).setView([-2, 118], 4);
                    const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    }).addTo(map);
                    const localZonesLayer = L.layerGroup().addTo(map);
                    const inariskStatus = document.getElementById('inarisk-status');
                    const inariskUrl = 'https://gis.bnpb.go.id/server/rest/services/inarisk/layer_risiko_banjir/ImageServer';
                    const overlayLayers = { 'Zona risiko lokal': localZonesLayer };

                    if (L.esri && typeof L.esri.imageMapLayer === 'function') {
                        const inariskLayer = L.esri.imageMapLayer({
                            url: inariskUrl,
                            opacity: 0.7,
                            useCors: true,
                            attribution: 'Sumber layer: <a href="https://gis.bnpb.go.id/server/rest/services/inarisk/layer_risiko_banjir/ImageServer" target="_blank" rel="noopener noreferrer">InaRISK/BNPB</a>',
                        });
                        overlayLayers['Indeks Risiko Banjir — InaRISK'] = inariskLayer;

                        inariskLayer.on('loading', () => {
                            inariskStatus.textContent = 'Memuat layer InaRISK…';
                            inariskStatus.className = 'mt-2 text-sm text-gray-600';
                        });
                        inariskLayer.on('load', () => {
                            inariskStatus.textContent = 'Layer InaRISK berhasil dimuat.';
                            inariskStatus.className = 'mt-2 text-sm text-emerald-700';
                        });
                        inariskLayer.on('error', () => {
                            inariskStatus.textContent = 'Layer InaRISK tidak dapat dimuat. Periksa koneksi atau kebijakan akses layanan; basemap dan zona lokal tetap tersedia.';
                            inariskStatus.className = 'mt-2 text-sm text-rose-700';
                        });
                        inariskLayer.addTo(map);
                    } else {
                        inariskStatus.textContent = 'Adapter InaRISK tidak tersedia. Basemap dan zona lokal tetap dapat digunakan.';
                        inariskStatus.className = 'mt-2 text-sm text-rose-700';
                    }

                    L.control.layers({ 'OpenStreetMap': osmLayer }, overlayLayers, { collapsed: window.innerWidth < 640 }).addTo(map);

                    const riskColor = (riskLevel) => {
                        const level = String(riskLevel ?? '').trim().toLowerCase();
                        if (['tinggi', 'high'].includes(level)) return '#dc2626';
                        if (['sedang', 'medium'].includes(level)) return '#f59e0b';
                        if (['rendah', 'low'].includes(level)) return '#059669';
                        return '#64748b';
                    };

                    const bounds = L.latLngBounds([]);

                    riskZones.forEach((zone) => {
                        const latitude = Number(zone.latitude);
                        const longitude = Number(zone.longitude);

                        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
                        if (latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return;

                        const color = riskColor(zone.risk_level);
                        const point = L.circleMarker([latitude, longitude], {
                            radius: 8,
                            color,
                            fillColor: color,
                            fillOpacity: 0.9,
                            weight: 2,
                        }).addTo(localZonesLayer);

                        const popup = document.createElement('div');
                        const heading = document.createElement('strong');
                        heading.textContent = zone.name;
                        popup.appendChild(heading);

                        [
                            ['Jenis bencana', zone.disaster_type],
                            ['Wilayah', zone.region_name],
                            ['Tingkat risiko', zone.risk_level],
                            ['Sumber', zone.source || 'Sumber belum dicantumkan'],
                            ['Tanggal data', zone.data_date || 'Tanggal data tidak tersedia'],
                        ].forEach(([label, value]) => {
                            const line = document.createElement('div');
                            line.textContent = `${label}: ${value}`;
                            popup.appendChild(line);
                        });

                        point.bindPopup(popup);
                        bounds.extend([latitude, longitude]);

                        const radius = Number(zone.radius_meters);
                        if (Number.isFinite(radius) && radius > 0) {
                            const estimate = L.circle([latitude, longitude], {
                                radius,
                                color,
                                fillColor: color,
                                fillOpacity: 0.12,
                                weight: 1,
                            }).addTo(localZonesLayer);
                            bounds.extend(estimate.getBounds());
                        }
                    });

                    if (bounds.isValid()) {
                        map.fitBounds(bounds.pad(0.15), { maxZoom: 12 });
                    }
            </script>
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
