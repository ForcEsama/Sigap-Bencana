<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - SIGAP BENCANA</title>
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
    <x-information-sidebar active="about" />

    <main class="min-w-0 flex-1 px-4 py-5 sm:px-7 lg:px-10 lg:py-7">
        <header class="mb-6 flex items-start gap-3">
            <button type="button" class="mt-1 text-xl lg:hidden" onclick="toggleMenu()" aria-label="Buka menu"><i class="fa-solid fa-bars"></i></button>
            <div>
                <h1 class="text-2xl font-semibold sm:text-3xl">Tentang Sigap Bencana</h1>
                <p class="mt-1 text-sm text-gray-600 sm:text-base">Informasi aplikasi dan fitur komunitas yang tersedia</p>
            </div>
        </header>

        <div class="space-y-5">
            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-[#ffedef] text-xl text-sigap"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="text-xl font-semibold">Tentang Sigap Bencana</h2>
                        <p class="mt-3 max-w-4xl text-sm leading-7 text-gray-600 sm:text-base">SIGAP BENCANA adalah aplikasi web yang bertujuan mendukung penyampaian informasi, pemantauan, dan pelaporan bencana. Aplikasi ini menyediakan sarana bagi masyarakat untuk mengirim laporan kejadian dan mengakses informasi yang tersedia guna mendukung kesiapsiagaan.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-xl font-semibold">Tujuan aplikasi</h2>
                <p class="mt-2 max-w-4xl text-sm leading-7 text-gray-600 sm:text-base">Membantu pengguna menyampaikan informasi kejadian melalui formulir laporan dan mengakses fitur informasi yang tersedia di aplikasi.</p>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <h2 class="mb-4 text-xl font-semibold">Fitur komunitas</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('reports.create') }}" class="group rounded-lg border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-[#fffafa] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">
                        <h3 class="font-semibold group-hover:text-sigap"><i class="fa-solid fa-file-circle-plus mr-2 text-sigap" aria-hidden="true"></i>Lapor Kejadian</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Kirim informasi kejadian, lokasi, deskripsi, dan foto opsional melalui formulir.</p>
                    </a>
                    <a href="{{ route('reports.index') }}" class="group rounded-lg border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-[#fffafa] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">
                        <h3 class="font-semibold group-hover:text-sigap"><i class="fa-solid fa-clock-rotate-left mr-2 text-sigap" aria-hidden="true"></i>Riwayat Laporan Saya</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Lihat daftar dan detail laporan yang dikirim dari akun Anda, termasuk status yang tersedia.</p>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="group rounded-lg border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-[#fffafa] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">
                        <h3 class="font-semibold group-hover:text-sigap"><i class="fa-regular fa-user mr-2 text-sigap" aria-hidden="true"></i>Profil</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Kelola informasi profil dan kata sandi akun Anda.</p>
                    </a>
                    <a href="{{ route('risk-map.index') }}" class="group rounded-lg border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-[#fffafa] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">
                        <h3 class="font-semibold group-hover:text-sigap"><i class="fa-solid fa-map-location-dot mr-2 text-sigap" aria-hidden="true"></i>Peta Risiko</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Lihat zona risiko lokal yang tersedia dan prototipe overlay risiko banjir InaRISK.</p>
                    </a>
                </div>
            </section>

            <section class="rounded-lg bg-white p-5 shadow-sm sm:p-7">
                <h2 class="mb-4 text-xl font-semibold">Cara menggunakan aplikasi</h2>
                <ol class="space-y-3 text-sm leading-6 text-gray-600 sm:text-base">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#ffedef] text-sm font-semibold text-sigap">1</span><span>Masuk ke akun untuk membuka halaman komunitas.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#ffedef] text-sm font-semibold text-sigap">2</span><span>Pilih <strong class="font-medium text-gray-800">Lapor Kejadian</strong>, lalu isi jenis bencana, lokasi beserta koordinat, deskripsi, dan waktu kejadian bila diketahui. Foto bersifat opsional.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#ffedef] text-sm font-semibold text-sigap">3</span><span>Buka <strong class="font-medium text-gray-800">Riwayat Laporan Saya</strong> untuk melihat laporan dan detail milik akun Anda.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#ffedef] text-sm font-semibold text-sigap">4</span><span>Gunakan menu <strong class="font-medium text-gray-800">Profil</strong> untuk memperbarui informasi akun atau kata sandi, dan <strong class="font-medium text-gray-800">Peta Risiko</strong> untuk melihat lapisan peta yang tersedia.</span></li>
                </ol>
            </section>

            <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 sm:p-7" aria-labelledby="information-note-title">
                <h2 id="information-note-title" class="flex items-center gap-2 font-semibold text-amber-900"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>Catatan informasi</h2>
                <p class="mt-2 text-sm leading-6 text-amber-900">Data peta dan layanan eksternal dapat memiliki keterbatasan pembaruan atau ketersediaan. Tanggal pembaruan dan legenda kelas pada prototipe layer InaRISK belum terverifikasi di aplikasi. Pengiriman laporan juga tidak menjamin respons atau tindak lanjut tertentu.</p>
            </section>
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
