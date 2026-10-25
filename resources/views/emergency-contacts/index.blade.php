<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Darurat - SIGAP BENCANA</title>
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
            <a class="nav-item" href="{{ route('disaster-alerts.index') }}"><i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>Peringatan Bencana</a>
            <a class="nav-item" href="{{ route('risk-map.index') }}"><i class="fa-solid fa-map-location-dot w-5 text-center"></i>Peta Risiko</a>
            <a class="nav-item" href="{{ route('reports.create') }}"><i class="fa-solid fa-file-circle-plus w-5 text-center"></i>Lapor Kejadian</a>
            <a class="nav-item" href="{{ route('reports.index') }}"><i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>Riwayat Laporan Saya</a>
            <a class="nav-item" href="{{ route('evacuation-posts.index') }}"><i class="fa-solid fa-tent w-5 text-center"></i>Tempat Evakuasi</a>
            <a class="nav-item" href="{{ route('sos.create') }}"><i class="fa-regular fa-circle-dot w-5 text-center"></i>SOS</a>
            <a class="nav-item" href="{{ route('news-education.index') }}"><i class="fa-solid fa-newspaper w-5 text-center"></i>Berita &amp; Edukasi</a>
            <a class="nav-item active" href="{{ route('emergency-contacts.index') }}" aria-current="page"><i class="fa-solid fa-phone w-5 text-center"></i>Kontak Darurat</a>
            <a class="nav-item" href="{{ route('about.index') }}"><i class="fa-solid fa-circle-exclamation w-5 text-center"></i>Tentang</a>
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
                    <h1 class="text-2xl font-semibold sm:text-3xl">Kontak Darurat</h1>
                    <p class="mt-1 text-sm text-gray-500 sm:text-base">Temukan informasi kontak yang tersedia untuk membantu Anda mencari bantuan.</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1 sm:gap-3">
                <x-notification-bell />
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-500 text-sm text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="rounded-xl bg-white p-5 shadow-sm sm:p-7" aria-labelledby="available-contacts-heading">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#ffeced] text-lg text-sigap"><i class="fa-solid fa-address-book" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="available-contacts-heading" class="text-lg font-semibold sm:text-xl">Kontak yang Tersedia</h2>
                        <p class="text-sm text-gray-500">Informasi ditampilkan sesuai data yang tersedia.</p>
                    </div>
                </div>

                @if ($contacts->isEmpty())
                    <div class="rounded-lg border border-dashed border-rose-200 bg-[#fff8f8] px-5 py-12 text-center">
                        <i class="fa-solid fa-phone-slash mb-3 text-3xl text-gray-400" aria-hidden="true"></i>
                        <h3 class="font-semibold">Kontak belum tersedia</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-gray-600">Belum ada kontak aktif yang dapat ditampilkan saat ini. Silakan gunakan informasi yang telah Anda pastikan dari sumber setempat.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($contacts as $contact)
                            @php
                                $category = mb_strtolower((string) $contact->category);
                                $contactName = mb_strtolower(trim((string) $contact->name));
                                $logo = match ($contactName) {
                                    'bpbd kota padang' => 'images/emergency-contacts/bpbd-kota-padang.png',
                                    'polri' => 'images/emergency-contacts/polri.svg',
                                    'basarnas' => 'images/emergency-contacts/basarnas.png',
                                    default => null,
                                };
                                $icon = match (true) {
                                    str_contains($category, 'medis'), str_contains($category, 'kesehatan'), str_contains($category, 'ambulans') => 'fa-kit-medical',
                                    str_contains($category, 'polisi'), str_contains($category, 'keamanan') => 'fa-shield-halved',
                                    str_contains($category, 'pemadam'), str_contains($category, 'kebakaran') => 'fa-fire-extinguisher',
                                    str_contains($category, 'pencarian'), str_contains($category, 'pertolongan'), str_contains($category, 'sar') => 'fa-life-ring',
                                    str_contains($category, 'bencana'), str_contains($category, 'evakuasi') => 'fa-house-flood-water',
                                    default => 'fa-phone',
                                };
                                $phone = preg_replace('/[^0-9+]/', '', (string) $contact->phone);
                                $phoneIsValid = filled($contact->phone) && preg_match('/^\+?[0-9]{3,30}$/', $phone) === 1;
                                $websiteScheme = strtolower((string) parse_url((string) $contact->website_url, PHP_URL_SCHEME));
                                $websiteIsValid = filled($contact->website_url)
                                    && in_array($websiteScheme, ['http', 'https'], true)
                                    && filter_var($contact->website_url, FILTER_VALIDATE_URL);
                            @endphp
                            <article class="flex min-w-0 flex-col rounded-xl border border-rose-100 bg-white p-4 shadow-sm sm:p-5">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[#ffeced] p-1" aria-hidden="true">
                                        @if ($logo)
                                            <img src="{{ asset($logo) }}" alt="" class="h-full w-full object-contain">
                                        @else
                                            <i class="fa-solid {{ $icon }} text-lg text-sigap"></i>
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="break-words font-semibold">{{ $contact->name }}</h3>
                                        @if ($contact->description)
                                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $contact->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if ($contact->region || $contact->address)
                                    <p class="mt-3 text-xs text-gray-500">
                                        <i class="fa-solid fa-location-dot mr-1 text-sigap" aria-hidden="true"></i>
                                        {{ collect([$contact->region, $contact->address])->filter()->implode(' · ') }}
                                    </p>
                                @endif
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-rose-100 pt-3">
                                    @if ($phoneIsValid)
                                        <a href="tel:{{ $phone }}" class="inline-flex items-center gap-2 font-semibold text-sigap hover:underline focus:outline-none focus:ring-2 focus:ring-sigap focus:ring-offset-2" aria-label="Telepon {{ $contact->name }}">
                                            <i class="fa-solid fa-phone" aria-hidden="true"></i>{{ $contact->phone }}
                                        </a>
                                    @elseif (filled($contact->phone))
                                        <span class="text-sm text-gray-600">{{ $contact->phone }}</span>
                                    @else
                                        <span class="text-sm text-gray-500">Nomor telepon belum dicantumkan</span>
                                    @endif
                                    @if ($websiteIsValid)
                                        <a href="{{ $contact->website_url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-gray-700 underline decoration-rose-300 underline-offset-2 hover:text-sigap">Situs layanan</a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-1" aria-label="Panduan kontak darurat">
                <section class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#ffeced] text-sigap"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                        <h2 class="font-semibold">Dalam Situasi Darurat</h2>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600">Utamakan keselamatan diri. Hubungi layanan yang sesuai dan tersedia di wilayah Anda, lalu ikuti arahan petugas setempat.</p>
                </section>

                <section class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#ffeced] text-sigap"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
                        <h2 class="font-semibold">Tips Saat Menghubungi</h2>
                    </div>
                    <ul class="space-y-3 text-sm leading-relaxed text-gray-600">
                        <li class="flex gap-2"><i class="fa-solid fa-location-dot mt-1 text-sigap" aria-hidden="true"></i><span>Sampaikan lokasi kejadian sejelas mungkin.</span></li>
                        <li class="flex gap-2"><i class="fa-solid fa-circle-info mt-1 text-sigap" aria-hidden="true"></i><span>Jelaskan kejadian dan kebutuhan bantuan secara singkat.</span></li>
                        <li class="flex gap-2"><i class="fa-solid fa-ear-listen mt-1 text-sigap" aria-hidden="true"></i><span>Dengarkan dan ikuti instruksi petugas.</span></li>
                    </ul>
                </section>

                <section class="rounded-xl bg-white p-5 shadow-sm sm:col-span-2 sm:p-6 xl:col-span-1">
                    <div class="mb-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#ffeced] text-sigap"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i></span>
                        <h2 class="font-semibold">Layanan Lainnya</h2>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-600">Kontak layanan lain akan ditampilkan jika informasinya tersedia. Periksa kembali keakuratan dan cakupan layanan melalui sumber setempat.</p>
                </section>
            </aside>
        </div>
    </main>
</div>
<script>
    function toggleMenu() {
        document.getElementById('sidebar').classList.toggle('open');
    }
</script>
</body>
</html>
