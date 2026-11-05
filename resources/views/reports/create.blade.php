<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Kejadian - SIGAP BENCANA</title>
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
        .field { width: 100%; border: 1px solid #999; border-radius: 8px; background: #fff; padding: 11px 13px; outline: none; }
        .field:focus { border-color: #d2002f; box-shadow: 0 0 0 2px rgb(210 0 47 / 12%); }
        .nav-item { display: flex; align-items: center; gap: 14px; border-radius: 8px; padding: 10px 12px; color: #191919; }
        .nav-item.active { color: #c9002c; background: #ffb8bd; }
        .nav-item.disabled { cursor: default; }
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
            <a class="nav-item active" href="{{ route('reports.create') }}" aria-current="page"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
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

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start justify-between gap-4">
            <div class="flex gap-3">
                <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <h1 class="text-2xl font-semibold sm:text-3xl">Lapor Kejadian</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Bantu kami mendapatkan informasi lebih cepat untuk penanganan bencana</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                <span class="text-xl" aria-label="Notifikasi"><i class="fa-regular fa-bell"></i></span>
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">Periksa kembali data laporan yang ditandai.</div>
        @endif

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,0.95fr)]">
            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <h2 class="mb-5 text-2xl font-semibold">Form Laporan</h2>
                <form action="{{ route('reports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="disaster_type" class="mb-1 block font-medium">Jenis Bencana <span class="text-red-700">*</span></label>
                        <select id="disaster_type" name="disaster_type" class="field" required>
                            <option value="">Pilih jenis bencana</option>
                            @foreach (['gempa' => 'Gempa bumi', 'banjir' => 'Banjir', 'longsor' => 'Tanah longsor', 'kebakaran' => 'Kebakaran', 'tsunami' => 'Tsunami', 'lainnya' => 'Lainnya'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('disaster_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('disaster_type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="address" class="mb-1 block font-medium">Lokasi Kejadian <span class="text-red-700">*</span></label>
                        <div class="flex gap-2">
                            <div class="relative min-w-0 flex-1">
                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input id="address" name="address" type="text" value="{{ old('address') }}" class="field pl-11" placeholder="Cari lokasi atau masukkan alamat" maxlength="1000" required>
                            </div>
                            <button type="button" onclick="useCurrentLocation()" class="h-[46px] w-[54px] shrink-0 rounded-lg border border-gray-400 text-xl hover:border-sigap hover:text-sigap" aria-label="Gunakan lokasi saya" title="Gunakan lokasi saya"><i class="fa-solid fa-location-dot"></i></button>
                        </div>
                        @error('address')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <label for="latitude" class="mb-1 block text-xs text-gray-600">Latitude <span class="text-red-700">*</span></label>
                                <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude') }}" class="field" placeholder="Masukkan latitude" required>
                                @error('latitude')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="longitude" class="mb-1 block text-xs text-gray-600">Longitude <span class="text-red-700">*</span></label>
                                <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude') }}" class="field" placeholder="Masukkan longitude" required>
                                @error('longitude')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <p id="location-message" class="mt-1 text-xs text-gray-500" aria-live="polite">Masukkan koordinat sendiri atau gunakan lokasi perangkat. Alamat perkiraan disediakan OpenStreetMap; periksa sebelum mengirim.</p>
                    </div>

                    <div>
                        <label for="description" class="mb-1 block font-medium">Deskripsi Kejadian <span class="text-red-700">*</span></label>
                        <textarea id="description" name="description" rows="4" class="field resize-y" placeholder="Jelaskan kejadian yang terjadi..." maxlength="5000" required>{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="occurred_at" class="mb-1 block font-medium">Waktu Kejadian <span class="text-sm font-normal text-gray-500">(Opsional, zona waktu UTC)</span></label>
                        <input id="occurred_at" name="occurred_at" type="datetime-local" value="{{ old('occurred_at') }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="field">
                        @error('occurred_at')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="photo" class="mb-1 block font-medium">Foto <span class="text-sm font-normal text-gray-500">(Opsional, JPG/PNG maks. 10 MB)</span></label>
                        <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="peer sr-only">
                        <label for="photo" class="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-lg bg-[#ffedef] px-4 py-5 text-center hover:bg-[#ffe2e5] peer-focus:ring-2 peer-focus:ring-sigap">
                            <i class="fa-regular fa-image mb-2 text-3xl text-gray-500"></i>
                            <span id="photo-label" class="font-medium">Klik untuk mengunggah foto</span>
                            <span class="text-sm text-gray-500">Video tidak didukung</span>
                        </label>
                        @error('photo')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-sigap px-5 py-3 text-lg font-medium text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">Kirim Laporan</button>
                </form>
            </section>

            <aside class="space-y-5">
                <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                    <div class="rounded-lg bg-[#ffedef] p-5 sm:p-6">
                        <h2 class="mb-5 flex items-center gap-3 text-xl font-semibold"><i class="fa-solid fa-circle-info text-3xl text-sigap"></i>Informasi Penting</h2>
                        <ul class="space-y-4 text-sm leading-relaxed text-gray-600">
                            <li class="flex gap-3"><i class="fa-solid fa-circle-exclamation mt-1 text-xl text-[#ff465a]"></i><span>Berikan informasi yang jelas dan akurat</span></li>
                            <li class="flex gap-3"><i class="fa-solid fa-location-dot mt-1 text-xl text-[#ff465a]"></i><span>Sertakan lokasi yang tepat</span></li>
                            <li class="flex gap-3"><i class="fa-solid fa-camera mt-1 text-xl text-[#ff465a]"></i><span>Tambahkan foto jika memungkinkan</span></li>
                            <li class="flex gap-3"><i class="fa-solid fa-circle-check mt-1 text-xl text-[#ff465a]"></i><span>Laporan akan ditinjau untuk tindak lanjut</span></li>
                        </ul>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                    <div class="mb-5 rounded-lg bg-[#ffedef] p-5">
                        <h2 class="mb-2 flex items-center gap-3 text-lg font-semibold"><i class="fa-solid fa-triangle-exclamation text-2xl text-sigap"></i>Butuh Bantuan Segera?</h2>
                        <p class="text-sm leading-relaxed text-gray-600">Jika dalam situasi darurat, segera hubungi nomor berikut:</p>
                    </div>
                    <a href="tel:112" class="flex items-center gap-4 rounded-lg bg-[#ffb8bd] p-4 text-sigap hover:bg-[#ffaab1]" aria-label="Hubungi 112, Panggilan Darurat Nasional">
                        <span class="flex h-14 w-14 items-center justify-center rounded-lg bg-sigap text-2xl text-white"><i class="fa-solid fa-phone-volume"></i></span>
                        <span><strong class="block text-2xl leading-none">112</strong><span class="text-sm text-gray-700">Panggilan Darurat Nasional</span></span>
                    </a>
                </section>
            </aside>
        </div>
    </main>
</div>
<script>
    function toggleMenu() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('open');
    }

    async function useCurrentLocation() {
        const message = document.getElementById('location-message');
        const locateButton = document.querySelector('[aria-label="Gunakan lokasi saya"]');

        if (!navigator.geolocation) {
            message.textContent = 'Browser tidak mendukung geolokasi. Silakan isi koordinat secara manual.';
            return;
        }

        locateButton.disabled = true;
        message.textContent = 'Meminta izin lokasi perangkat...';
        navigator.geolocation.getCurrentPosition(async (position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            document.getElementById('latitude').value = latitude.toFixed(7);
            document.getElementById('longitude').value = longitude.toFixed(7);
            document.getElementById('address').value = '';
            message.textContent = 'Koordinat GPS ditemukan (akurasi sekitar ' + Math.round(position.coords.accuracy) + ' meter). Mencari alamat...';

            try {
                const address = await reverseGeocode(latitude, longitude);
                if (address) {
                    document.getElementById('address').value = address;
                    message.textContent = 'Perkiraan alamat dari OpenStreetMap: ' + address + '. Periksa sebelum mengirim.';
                } else {
                    message.textContent = 'Koordinat GPS ditemukan, tetapi alamat belum tersedia. Isi alamat kejadian secara manual.';
                }
            } catch (error) {
                message.textContent = error.message === 'RATE_LIMITED'
                    ? 'Koordinat GPS ditemukan, tetapi layanan alamat sedang sibuk. Isi alamat kejadian secara manual.'
                    : 'Koordinat GPS ditemukan, tetapi alamat gagal dimuat. Periksa koneksi atau isi alamat kejadian secara manual.';
            } finally {
                locateButton.disabled = false;
            }
        }, (error) => {
            locateButton.disabled = false;
            if (error.code === error.PERMISSION_DENIED) {
                message.textContent = 'Izin lokasi ditolak. Silakan isi koordinat dan alamat secara manual.';
            } else if (error.code === error.TIMEOUT) {
                message.textContent = 'Permintaan lokasi melewati batas waktu. Silakan coba lagi atau isi secara manual.';
            } else {
                message.textContent = 'Lokasi tidak tersedia. Silakan isi koordinat dan alamat secara manual.';
            }
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    let lastReverseGeocodeRequestAt = 0;

    async function reverseGeocode(latitude, longitude) {
        const elapsed = Date.now() - lastReverseGeocodeRequestAt;
        if (elapsed < 1000) await new Promise((resolve) => window.setTimeout(resolve, 1000 - elapsed));
        lastReverseGeocodeRequestAt = Date.now();
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.search = new URLSearchParams({
            format: 'jsonv2',
            lat: latitude.toString(),
            lon: longitude.toString(),
            'accept-language': 'id',
            zoom: '18',
            addressdetails: '1',
        });
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 8000);

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (response.status === 429) throw new Error('RATE_LIMITED');
            if (!response.ok) throw new Error('GEOCODING_FAILED');

            const result = await response.json();
            const address = result.address || {};
            const street = [address.house_number, address.road || address.pedestrian || address.residential]
                .filter(Boolean)
                .join(' ');
            const parts = [
                street,
                address.neighbourhood || address.suburb || address.village || address.hamlet,
                address.city_district,
                address.city || address.town || address.municipality || address.county,
                address.state,
            ].filter(Boolean);
            const uniqueParts = [...new Map(parts.map((part) => [part.toLocaleLowerCase('id'), part])).values()];

            return uniqueParts.join(', ') || result.display_name || null;
        } finally {
            window.clearTimeout(timeout);
        }
    }
    document.getElementById('photo').addEventListener('change', (event) => {
        document.getElementById('photo-label').textContent = event.target.files[0]?.name ?? 'Klik untuk mengunggah foto';
    });
</script>
</body>
</html>
