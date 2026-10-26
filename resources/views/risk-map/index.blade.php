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
            <a class="nav-item active" href="{{ route('risk-map.index') }}" aria-current="page"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
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
    <x-guest-sidebar active="risk-map" />
@endauth

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
                @auth <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span> @endauth
                @auth <span class="hidden sm:inline">{{ auth()->user()->name }}</span> @endauth
            </div>
        </header>

        <section class="rounded-lg bg-white p-4 shadow-sm sm:p-6">
            @php
                $riskLayerConfigs = config('risk_map.layers');
                $defaultRiskLayer = config('risk_map.default_layer');
            @endphp
            <div class="mb-4 rounded-lg border border-rose-100 bg-[#fff8f8] p-4">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <label for="risk-type-filter" class="mb-1 block text-sm font-medium">Jenis peta risiko</label>
                        <select id="risk-type-filter" class="w-full max-w-md rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-sigap focus:outline-none focus:ring-2 focus:ring-rose-200">
                            @foreach ($riskLayerConfigs as $key => $riskLayer)
                                <option value="{{ $key }}" @selected($key === $defaultRiskLayer)>{{ $riskLayer['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a id="risk-layer-source-link" href="{{ $riskLayerConfigs[$defaultRiskLayer]['service_url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-sigap underline">Sumber layer: InaRISK/BNPB</a>
                </div>
                <h2 id="risk-layer-title" class="mt-4 font-semibold">Peta Risiko {{ $riskLayerConfigs[$defaultRiskLayer]['label'] }} — InaRISK</h2>
                <p id="risk-layer-classification" class="mt-1 text-sm text-gray-600">Peta risiko BNPB. Layer peta bahaya, kerentanan, dan kejadian terkini tidak dicampur pada filter ini.</p>
                <p id="risk-layer-date" class="mt-2 text-xs text-gray-600">Tanggal data tidak dicantumkan pada metadata REST yang diverifikasi.</p>
                <p id="inarisk-status" class="mt-2 text-sm text-gray-600" role="status" aria-live="polite">Menyiapkan layer InaRISK…</p>

                <div class="mt-5 rounded-lg bg-white p-4">
                    <h2 class="mb-3 font-semibold">Legenda Zona Risiko SIGAP BENCANA</h2>
                    <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm text-gray-700">
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-red-600"></span>Risiko tinggi</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-amber-500"></span>Risiko sedang</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-600"></span>Risiko rendah</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-500"></span>Tingkat lain/tidak dikenali</span>
                        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full border-2 border-slate-500 bg-slate-200/70"></span>Radius perkiraan bila tersedia</span>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Simbol dan warna membantu membedakan kategori; radius bukan batas wilayah resmi.</p>
                </div>
            </div>

            <div id="risk-map" class="overflow-hidden rounded-lg" role="application" aria-label="Peta risiko bencana InaRISK"></div>






            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script src="https://unpkg.com/esri-leaflet@3.0.19/dist/esri-leaflet.js"></script>
            <script>
                    const riskLayerConfigs = @js($riskLayerConfigs);
                    const defaultRiskLayer = @js($defaultRiskLayer);
                    const map = L.map('risk-map', { scrollWheelZoom: false }).setView([-2, 118], 4);
                    const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    }).addTo(map);
                    const inariskStatus = document.getElementById('inarisk-status');
                    const inariskLegend = document.getElementById('inarisk-legend');
                    const inariskLegendTitle = document.getElementById('inarisk-legend-title');
                    const riskTypeFilter = document.getElementById('risk-type-filter');
                    const riskLayerTitle = document.getElementById('risk-layer-title');
                    const riskLayerDate = document.getElementById('risk-layer-date');
                    const riskLayerSourceLink = document.getElementById('risk-layer-source-link');
                    let activeRiskLayer = null;
                    let riskLayerRequest = 0;
                    let layersControl = null;

                    const setStatus = (message, isError = false) => {
                        inariskStatus.textContent = message;
                        inariskStatus.className = `mt-2 text-sm ${isError ? 'text-rose-700' : 'text-gray-600'}`;
                    };

                    const updateLayersControl = () => {
                        if (layersControl) map.removeControl(layersControl);
                        const overlays = {};
                        if (activeRiskLayer) {
                            overlays[`Peta risiko ${riskLayerConfigs[riskTypeFilter.value].label} — InaRISK`] = activeRiskLayer;
                        }
                        layersControl = L.control.layers({ 'OpenStreetMap': osmLayer }, overlays, {
                            collapsed: window.innerWidth < 640,
                        }).addTo(map);
                    };

                    const fetchServiceJson = async (url) => {
                        const controller = new AbortController();
                        const timeout = window.setTimeout(() => controller.abort(), 15000);
                        try {
                            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error(`HTTP ${response.status}`);
                            const payload = await response.json();
                            if (payload.error) throw new Error(payload.error.message || 'ArcGIS REST mengembalikan error.');
                            return payload;
                        } finally {
                            window.clearTimeout(timeout);
                        }
                    };

                    const renderOfficialLegend = async (layerConfig, requestId) => {
                        inariskLegendTitle.textContent = `Legenda resmi — ${layerConfig.label}`;
                        inariskLegend.textContent = 'Meminta legenda resmi dari layanan…';
                        try {
                            const legendData = await fetchServiceJson(layerConfig.legend_url);
                            if (requestId !== riskLayerRequest) return;
                            const entries = (legendData.layers || []).flatMap((legendLayer) => legendLayer.legend || []);
                            if (entries.length === 0) {
                                inariskLegend.textContent = layerConfig.legend_fallback;
                                return;
                            }
                            inariskLegend.replaceChildren();
                            entries.forEach((entry) => {
                                const item = document.createElement('div');
                                item.className = 'flex items-center gap-2';
                                if (entry.imageData && entry.contentType) {
                                    const symbol = document.createElement('img');
                                    symbol.src = `data:${entry.contentType};base64,${entry.imageData}`;
                                    symbol.alt = '';
                                    symbol.className = 'max-h-7 max-w-7 object-contain';
                                    item.appendChild(symbol);
                                }
                                const label = document.createElement('span');
                                label.textContent = entry.label || 'Kelas tanpa label pada layanan';
                                item.appendChild(label);
                                inariskLegend.appendChild(item);
                            });
                        } catch (error) {
                            if (requestId === riskLayerRequest) {
                                inariskLegend.textContent = `${layerConfig.legend_fallback} Legenda tidak dapat dimuat saat ini.`;
                            }
                        }
                    };

                    const isOutsideServiceExtent = (mapBounds, metadata) => {
                        const extent = metadata.fullExtent;
                        if (!extent || !extent.spatialReference || extent.spatialReference.wkid !== 3395) return null;
                        const southWest = L.CRS.EPSG3395.project(mapBounds.getSouthWest());
                        const northEast = L.CRS.EPSG3395.project(mapBounds.getNorthEast());
                        return northEast.x < extent.xmin || southWest.x > extent.xmax
                            || northEast.y < extent.ymin || southWest.y > extent.ymax;
                    };

                    const selectRiskLayer = async (key) => {
                        const layerConfig = riskLayerConfigs[key];
                        const requestId = ++riskLayerRequest;
                        if (activeRiskLayer) {
                            map.removeLayer(activeRiskLayer);
                            activeRiskLayer = null;
                            updateLayersControl();
                        }
                        riskLayerTitle.textContent = `Peta Risiko ${layerConfig.label} — InaRISK`;
                        riskLayerDate.textContent = layerConfig.data_date
                            ? `Tanggal data: ${layerConfig.data_date}`
                            : 'Tanggal data tidak dicantumkan pada metadata REST yang diverifikasi.';
                        riskLayerSourceLink.href = layerConfig.service_url;
                        setStatus(`Memeriksa metadata dan cakupan ${layerConfig.label}…`);

                        try {
                            const metadata = await fetchServiceJson(layerConfig.metadata_url);
                            if (requestId !== riskLayerRequest) return;
                            const isOutside = isOutsideServiceExtent(map.getBounds(), metadata);
                            if (isOutside === true) {
                                setStatus(`Layer ${layerConfig.label} tidak mencakup area peta saat ini. Geser peta ke area layanan untuk melihat data.`, true);
                                await renderOfficialLegend(layerConfig, requestId);
                                return;
                            }
                            if (!L.esri || typeof L.esri.imageMapLayer !== 'function') {
                                throw new Error('Adapter Esri Leaflet ImageServer tidak tersedia.');
                            }
                            activeRiskLayer = L.esri.imageMapLayer({ url: layerConfig.service_url, opacity: 0.7, useCors: true });
                            activeRiskLayer.on('loading', () => setStatus(`Memuat peta risiko ${layerConfig.label}…`));
                            activeRiskLayer.on('load', () => {
                                if (requestId !== riskLayerRequest) return;
                                setStatus(isOutside === null
                                    ? `Layer ${layerConfig.label} dimuat. Cakupan area tidak dapat dipastikan dari metadata layanan.`
                                    : `Layer ${layerConfig.label} dimuat. Area tanpa data pada raster dapat tampil kosong.`);
                            });
                            activeRiskLayer.on('error', () => {
                                if (requestId === riskLayerRequest) {
                                    setStatus(`Layer ${layerConfig.label} gagal dimuat dari BNPB. Basemap OpenStreetMap tetap tersedia.`, true);
                                }
                            });
                            activeRiskLayer.addTo(map);
                            updateLayersControl();
                            await renderOfficialLegend(layerConfig, requestId);
                        } catch (error) {
                            if (requestId !== riskLayerRequest) return;
                            setStatus(`Metadata atau layanan ${layerConfig.label} tidak dapat diakses. Periksa koneksi atau coba lagi. Basemap OpenStreetMap tetap tersedia.`, true);
                            inariskLegendTitle.textContent = `Legenda resmi — ${layerConfig.label}`;
                            inariskLegend.textContent = layerConfig.legend_fallback;
                        }
                    };

                    riskTypeFilter.addEventListener('change', (event) => selectRiskLayer(event.target.value));
                    updateLayersControl();
                    selectRiskLayer(defaultRiskLayer);

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
