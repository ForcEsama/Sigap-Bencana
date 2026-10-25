<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kenali tujuan, informasi, dan fitur SIGAP BENCANA untuk mendukung kesiapsiagaan masyarakat.">
    <title>Tentang SIGAP BENCANA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] },
                    colors: { sigap: '#D2002F', blush: '#FFECED' }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen bg-[#fff8f8] font-poppins text-gray-900">
    <x-public-nav active="about" />

    <main>
        <section class="relative overflow-hidden bg-gradient-to-br from-[#ffeced] via-white to-rose-50">
            <div class="mx-auto max-w-7xl px-6 py-14 sm:py-20 md:px-12 lg:px-16">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[.16em] text-sigap">Tentang Kami</p>
                <h1 class="max-w-4xl text-3xl font-bold leading-tight tracking-tight sm:text-5xl">Tentang SIGAP BENCANA</h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-gray-700 sm:text-lg">Kenali SIGAP BENCANA, platform informasi dan kesiapsiagaan kebencanaan untuk masyarakat.</p>
                <p class="mt-5 max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">SIGAP BENCANA merupakan aplikasi web yang membantu masyarakat mengakses informasi kebencanaan, melihat informasi risiko yang tersedia, membuat laporan, dan menemukan panduan kesiapsiagaan. Fitur dan informasi dapat memiliki cakupan serta ketersediaan yang berbeda.</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('landing') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-sigap px-6 py-3 font-semibold text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2"><i class="fa-solid fa-house" aria-hidden="true"></i>Kembali ke Beranda</a>
                    <a href="{{ route('help.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md border border-sigap bg-white px-6 py-3 font-semibold text-sigap transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2"><i class="fa-regular fa-circle-question" aria-hidden="true"></i>Lihat Bantuan</a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-md px-5 py-3 font-semibold text-gray-800 transition hover:bg-white/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">Masuk ke SIGAP BENCANA</a>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-12 md:px-12 lg:px-16" aria-labelledby="goals-heading">
            <div class="mb-6 max-w-3xl">
                <h2 id="goals-heading" class="text-2xl font-bold sm:text-3xl">Tujuan platform</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600 sm:text-base">Mendukung masyarakat agar lebih mudah mengakses informasi dan menyiapkan diri, dengan tetap memperhatikan sumber serta batasan data.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $goals = [
                        ['icon' => 'fa-circle-info', 'title' => 'Informasi Bencana', 'text' => 'Membantu pengguna mengakses informasi kebencanaan yang tersedia pada aplikasi.'],
                        ['icon' => 'fa-map-location-dot', 'title' => 'Pemetaan Risiko', 'text' => 'Membantu pengguna memahami informasi risiko melalui layer peta yang tersedia.'],
                        ['icon' => 'fa-file-circle-plus', 'title' => 'Pelaporan Bencana', 'text' => 'Menyediakan sarana untuk mencatat dan mengirim laporan kejadian melalui aplikasi.'],
                        ['icon' => 'fa-book-open', 'title' => 'Kesiapsiagaan', 'text' => 'Menyediakan informasi edukasi dan panduan untuk membantu persiapan menghadapi bencana.'],
                        ['icon' => 'fa-phone', 'title' => 'Informasi Darurat', 'text' => 'Memudahkan pengguna menemukan kontak layanan yang tercatat pada aplikasi.'],
                        ['icon' => 'fa-people-group', 'title' => 'Partisipasi Masyarakat', 'text' => 'Mendukung keterlibatan masyarakat agar lebih siap dan tanggap terhadap risiko bencana.'],
                    ];
                @endphp
                @foreach ($goals as $goal)
                    <article class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-6">
                        <span class="mb-4 flex h-11 w-11 items-center justify-center rounded-lg bg-[#ffeced] text-lg text-sigap"><i class="fa-solid {{ $goal['icon'] }}" aria-hidden="true"></i></span>
                        <h3 class="font-semibold">{{ $goal['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $goal['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="bg-white" aria-labelledby="features-heading">
            <div class="mx-auto max-w-7xl px-6 py-12 md:px-12 lg:px-16">
                <div class="mb-6 max-w-3xl">
                    <h2 id="features-heading" class="text-2xl font-bold sm:text-3xl">Fitur SIGAP BENCANA</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600 sm:text-base">Beberapa fitur memerlukan akun. Klik fitur untuk melanjutkan ke halaman terkait; halaman pribadi akan meminta Anda masuk.</p>
                </div>
                @php
                    $features = [
                        ['icon' => 'fa-map-location-dot', 'title' => 'Peta Risiko Bencana', 'text' => 'Menampilkan peta dan layer risiko yang tersedia. Cakupan data, koneksi layanan, dan keterangan legenda dapat terbatas.', 'route' => 'risk-map.index'],
                        ['icon' => 'fa-newspaper', 'title' => 'Berita dan Edukasi', 'text' => 'Menggabungkan informasi dari sumber yang tersedia, termasuk konten edukasi dan berita kebencanaan.', 'route' => 'news-education.index'],
                        ['icon' => 'fa-triangle-exclamation', 'title' => 'Peringatan Bencana', 'text' => 'Menampilkan informasi peringatan dan kejadian dari feed sumber yang digunakan. Tidak semua kartu merupakan peringatan aktif.', 'route' => 'disaster-alerts.index'],
                        ['icon' => 'fa-file-circle-plus', 'title' => 'Laporan Bencana', 'text' => 'Mencatat laporan kejadian yang dikirim melalui formulir. Laporan yang terkirim belum tentu sudah diverifikasi atau ditindaklanjuti.', 'route' => 'reports.create'],
                        ['icon' => 'fa-circle-dot', 'title' => 'SOS', 'text' => 'Mencatat permintaan bantuan pada aplikasi. Pengiriman formulir bukan jaminan permintaan diterima oleh petugas atau layanan darurat.', 'route' => 'sos.create'],
                        ['icon' => 'fa-phone', 'title' => 'Kontak Darurat', 'text' => 'Menampilkan kontak aktif yang tersedia pada aplikasi. Periksa cakupan wilayah dan ketersediaannya.', 'route' => 'emergency-contacts.index'],
                        ['icon' => 'fa-tent', 'title' => 'Informasi Tempat Evakuasi', 'text' => 'Menampilkan lokasi yang tersedia. Kandidat dari dokumen perencanaan dapat belum diverifikasi dan bukan bukti lokasi aktif.', 'route' => 'evacuation-posts.index'],
                        ['icon' => 'fa-user', 'title' => 'Profil dan Riwayat Aktivitas', 'text' => 'Pengguna yang masuk dapat mengelola profil dan melihat riwayat laporan serta SOS yang tercatat.', 'route' => 'reports.index'],
                    ];
                @endphp
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($features as $feature)
                        <a href="{{ route($feature['route']) }}" class="group rounded-xl border border-rose-100 bg-[#fffafa] p-5 transition hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">
                            <span class="mb-4 flex h-11 w-11 items-center justify-center rounded-lg bg-[#ffeced] text-lg text-sigap"><i class="fa-solid {{ $feature['icon'] }}" aria-hidden="true"></i></span>
                            <h3 class="font-semibold group-hover:text-sigap">{{ $feature['title'] }}<i class="fa-solid fa-arrow-up-right-from-square ml-2 text-xs" aria-hidden="true"></i></h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600">{{ $feature['text'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-12 md:px-12 lg:px-16">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 sm:p-7">
                <h2 class="flex items-center gap-2 font-semibold text-amber-950"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>Komitmen informasi</h2>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-amber-950">Gunakan informasi kebencanaan secara bijak. Ketersediaan dan pembaruan data bergantung pada sumber masing-masing fitur. SIGAP BENCANA bukan pengganti instruksi dari instansi berwenang; dalam kondisi darurat, ikuti arahan petugas dan informasi resmi setempat.</p>
            </div>
        </section>
    </main>

    <footer class="border-t border-rose-100 bg-white px-6 py-5 text-center text-xs text-gray-600 sm:text-sm">SIGAP BENCANA &copy; 2026 — Siaga, Tanggap, Selamat.</footer>
</body>
</html>
