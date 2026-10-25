<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panduan penggunaan SIGAP BENCANA dan jawaban atas pertanyaan yang sering diajukan.">
    <title>Pusat Bantuan - SIGAP BENCANA</title>
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
    <style>
        body { font-family: Poppins, sans-serif; }
        .faq-chevron { transition: transform .18s ease; }
        details[open] .faq-chevron { transform: rotate(180deg); }
    </style>
</head>
<body class="min-h-screen bg-[#fff8f8] font-poppins text-gray-900">
    <x-public-nav active="help" />

    <main>
        <section class="bg-gradient-to-br from-[#ffeced] via-white to-rose-50">
            <div class="mx-auto max-w-7xl px-6 py-12 sm:py-16 md:px-12 lg:px-16">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[.16em] text-sigap">Panduan Pengguna</p>
                <h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-5xl">Pusat Bantuan</h1>
                <p class="mt-4 max-w-3xl text-sm leading-7 text-gray-700 sm:text-lg">Temukan panduan penggunaan SIGAP BENCANA dan jawaban atas pertanyaan yang sering diajukan.</p>
            </div>
        </section>

        @php
            $categories = [
                'umum' => 'Pertanyaan Umum',
                'peta' => 'Peta Risiko',
                'laporan' => 'Laporan Bencana',
                'sos' => 'SOS dan Keadaan Darurat',
                'kontak' => 'Kontak Darurat',
                'evakuasi' => 'Tempat Evakuasi',
                'akun' => 'Akun dan Privasi',
            ];
            $faqs = [
                ['category' => 'umum', 'question' => 'Apa itu SIGAP BENCANA?', 'answer' => 'SIGAP BENCANA adalah platform informasi kebencanaan yang dirancang untuk membantu masyarakat mengakses informasi, memahami risiko, dan meningkatkan kesiapsiagaan menghadapi bencana.'],
                ['category' => 'umum', 'question' => 'Apakah saya harus login untuk membaca informasi di SIGAP BENCANA?', 'answer' => 'Tidak. Halaman publik seperti Tentang dan Pusat Bantuan dapat diakses tanpa login. Fitur yang berkaitan dengan akun atau aktivitas pribadi mungkin memerlukan login.'],
                ['category' => 'umum', 'question' => 'Bagaimana cara membuat akun?', 'answer' => 'Pilih tombol Daftar Akun pada halaman utama, lalu isi nama, email, nomor telepon jika ingin, kata sandi dan konfirmasinya, serta setujui syarat yang ditampilkan. Setelah pendaftaran berhasil, masuk menggunakan akun tersebut.'],
                ['category' => 'umum', 'question' => 'Bagaimana cara masuk ke akun?', 'answer' => 'Pilih tombol Masuk pada halaman utama, kemudian masukkan email atau nomor telepon dan kata sandi pada formulir login.'],
                ['category' => 'umum', 'question' => 'Apakah informasi pada platform selalu real-time?', 'answer' => 'Ketersediaan dan waktu pembaruan informasi bergantung pada sumber serta integrasi masing-masing fitur. Untuk keputusan darurat, periksa informasi terbaru dan ikuti arahan instansi berwenang.'],
                ['category' => 'peta', 'question' => 'Bagaimana cara menggunakan Peta Risiko?', 'answer' => 'Buka menu Peta Risiko, pilih jenis bencana yang ingin dilihat, lalu gunakan peta dan keterangan yang tersedia untuk memahami informasi pada wilayah terkait. Layer mungkin tidak tersedia untuk setiap kombinasi atau wilayah.'],
                ['category' => 'peta', 'question' => 'Apakah peta risiko dapat dijadikan satu-satunya acuan evakuasi?', 'answer' => 'Tidak. Peta risiko merupakan sarana informasi untuk membantu memahami potensi risiko, bukan pengganti instruksi evakuasi resmi. Ikuti arahan petugas dan informasi dari instansi berwenang.'],
                ['category' => 'peta', 'question' => 'Mengapa informasi atau legenda peta tertentu mungkin tidak tersedia?', 'answer' => 'Ketersediaan bergantung pada data dan sumber yang digunakan oleh layer peta tersebut. Keterangan yang tidak tersedia bukan informasi yang telah terverifikasi.'],
                ['category' => 'laporan', 'question' => 'Bagaimana cara membuat laporan bencana?', 'answer' => 'Masuk ke akun, buka Lapor Kejadian, pilih jenis bencana, isi alamat dan titik koordinat, jelaskan kejadian serta waktu jika diketahui, lalu kirim formulir. Foto JPG atau PNG bersifat opsional.'],
                ['category' => 'laporan', 'question' => 'Apakah semua laporan langsung diverifikasi petugas?', 'answer' => 'Tidak. Status awal laporan adalah menunggu verifikasi. Laporan yang terkirim tidak otomatis berarti telah diverifikasi atau ditindaklanjuti. Periksa status yang tercatat pada aplikasi.'],
                ['category' => 'laporan', 'question' => 'Di mana saya dapat melihat laporan yang sudah dibuat?', 'answer' => 'Masuk ke akun, lalu buka Riwayat Laporan Saya. Halaman tersebut menampilkan laporan yang terkait dengan akun Anda.'],
                ['category' => 'sos', 'question' => 'Apa fungsi tombol SOS?', 'answer' => 'Tombol SOS menjalankan alur untuk mencatat permintaan bantuan pada aplikasi. Periksa jenis keadaan darurat dan lokasi sebelum mengirim.'],
                ['category' => 'sos', 'question' => 'Apakah mengirim SOS menjamin petugas akan menerima permintaan?', 'answer' => 'Tidak. Implementasi saat ini mencatat permintaan SOS di aplikasi dan menampilkan status yang tersimpan. Pengiriman formulir bukan bukti bahwa permintaan telah diteruskan atau diterima petugas.'],
                ['category' => 'sos', 'question' => 'Bagaimana cara melihat riwayat SOS?', 'answer' => 'Masuk ke akun, buka Riwayat Laporan Saya, lalu pilih tab Riwayat SOS. Halaman menampilkan permintaan yang tersimpan dan terkait dengan akun Anda.'],
                ['category' => 'sos', 'question' => 'Apa yang harus dilakukan ketika terjadi keadaan darurat nyata?', 'answer' => 'Segera cari tempat aman jika memungkinkan, ikuti instruksi petugas, dan hubungi layanan darurat resmi yang relevan di wilayah Anda. Jangan menunda meminta bantuan nyata hanya untuk mengisi formulir aplikasi.'],
                ['category' => 'kontak', 'question' => 'Di mana saya dapat menemukan nomor darurat?', 'answer' => 'Setelah masuk, buka halaman Kontak Darurat untuk melihat layanan, nomor telepon, dan tautan informasi instansi yang tersedia pada aplikasi.'],
                ['category' => 'kontak', 'question' => 'Apakah nomor dan ketersediaan layanan berlaku di semua wilayah?', 'answer' => 'Tidak selalu. Ketersediaan layanan dapat berbeda menurut wilayah dan kondisi jaringan. Periksa cakupan layanan melalui sumber setempat.'],
                ['category' => 'evakuasi', 'question' => 'Bagaimana cara melihat lokasi evakuasi?', 'answer' => 'Setelah masuk, buka fitur Tempat Evakuasi untuk melihat informasi lokasi yang tersedia. Periksa sumber dan status verifikasi setiap informasi.'],
                ['category' => 'evakuasi', 'question' => 'Apakah semua lokasi yang ditampilkan sudah diverifikasi?', 'answer' => 'Tidak. Status bergantung pada sumber masing-masing lokasi. Kandidat dari dokumen perencanaan lama tidak boleh dianggap sebagai tempat evakuasi aktif yang telah diverifikasi.'],
                ['category' => 'evakuasi', 'question' => 'Apa yang harus dilakukan jika informasi lokasi berbeda dengan kondisi di lapangan?', 'answer' => 'Utamakan keselamatan dan ikuti arahan petugas atau pemerintah setempat. Jangan menggunakan koordinat yang belum diverifikasi sebagai satu-satunya panduan evakuasi.'],
                ['category' => 'akun', 'question' => 'Mengapa beberapa fitur meminta saya login?', 'answer' => 'Fitur yang memerlukan identitas pengguna, seperti pengelolaan profil dan riwayat pribadi, memerlukan login agar data ditampilkan kepada pemilik akun yang benar.'],
                ['category' => 'akun', 'question' => 'Bagaimana cara melihat riwayat aktivitas saya?', 'answer' => 'Masuk ke akun, kemudian buka Riwayat Laporan Saya. Data yang ditampilkan bergantung pada aktivitas yang benar-benar tercatat pada akun Anda.'],
                ['category' => 'akun', 'question' => 'Apa yang harus dilakukan jika mengalami masalah saat menggunakan aplikasi?', 'answer' => 'Periksa koneksi internet, muat ulang halaman jika diperlukan, dan pastikan informasi yang dimasukkan sesuai petunjuk. Jika masalah tetap terjadi, gunakan kanal bantuan atau kontak yang benar-benar tersedia pada aplikasi.'],
            ];
        @endphp

        <section class="mx-auto max-w-5xl px-6 py-10 md:px-12" aria-labelledby="faq-heading">
            <div class="rounded-2xl bg-white p-5 shadow-sm sm:p-8">
                <div class="mb-6">
                    <h2 id="faq-heading" class="text-xl font-bold sm:text-2xl">Pertanyaan yang sering diajukan</h2>
                    <p class="mt-2 text-sm text-gray-600">Pilih kategori atau cari kata di pertanyaan dan jawaban.</p>
                </div>

                <div class="mb-6 grid gap-3 sm:grid-cols-[minmax(0,1fr)_240px]">
                    <label class="sr-only" for="faq-search">Cari pertanyaan</label>
                    <div class="flex min-h-12 items-center gap-3 rounded-lg border border-gray-300 px-4 focus-within:border-sigap focus-within:ring-2 focus-within:ring-rose-100">
                        <i class="fa-solid fa-magnifying-glass text-gray-500" aria-hidden="true"></i>
                        <input id="faq-search" type="search" autocomplete="off" placeholder="Cari pertanyaan atau kata kunci" class="min-w-0 flex-1 bg-transparent text-sm outline-none">
                    </div>
                    <label class="sr-only" for="faq-category">Pilih kategori FAQ</label>
                    <select id="faq-category" class="min-h-12 rounded-lg border border-gray-300 bg-white px-3 text-sm focus:border-sigap focus:outline-none focus:ring-2 focus:ring-rose-100">
                        <option value="all">Semua kategori</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <p id="faq-result-status" class="mb-3 text-sm text-gray-600" role="status" aria-live="polite">{{ count($faqs) }} pertanyaan tersedia.</p>
                <div id="faq-empty" class="mb-4 hidden rounded-lg border border-dashed border-rose-200 bg-[#fffafa] px-4 py-8 text-center text-sm text-gray-600" role="status">Tidak ada pertanyaan yang cocok. Coba kata kunci atau kategori lain.</div>

                <div id="faq-list" class="space-y-3">
                    @foreach ($faqs as $faq)
                        @php($searchText = mb_strtolower($faq['question'].' '.$faq['answer']))
                        <details class="faq-item group rounded-lg border border-rose-100 bg-[#fffafa] px-4 sm:px-5" data-category="{{ $faq['category'] }}" data-search="{{ $searchText }}">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 font-medium focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap [&::-webkit-details-marker]:hidden">
                                <span class="flex items-start gap-3"><span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#ffeced] text-xs font-semibold text-sigap">{{ $loop->iteration }}</span><span>{{ $faq['question'] }}</span></span>
                                <i class="faq-chevron fa-solid fa-chevron-down shrink-0 text-sm text-sigap" aria-hidden="true"></i>
                            </summary>
                            <div class="border-t border-rose-100 pb-4 pt-3 pl-9 text-sm leading-7 text-gray-700">{{ $faq['answer'] }}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-4 px-6 pb-12 md:grid-cols-2 md:px-12 lg:px-16" aria-label="Informasi darurat dan navigasi">
            <div class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-[#ffeced] text-lg text-sigap"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
                    <div>
                        <h2 class="font-semibold">Dalam keadaan darurat</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Utamakan keselamatan dan hubungi layanan darurat resmi yang berlaku di lokasi Anda. Jangan menunda menghubungi bantuan nyata untuk mengisi aplikasi. Nomor dan cakupan layanan dapat berbeda menurut wilayah.</p>
                    </div>
                </div>
                <a href="{{ route('login') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-md bg-sigap px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap focus-visible:ring-offset-2">Masuk untuk melihat Kontak Darurat</a>
            </div>
            <div class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm sm:p-7">
                <h2 class="font-semibold">Masih ingin mengenal aplikasi?</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600">Baca penjelasan fitur, tujuan, dan batasan informasi SIGAP BENCANA sebelum menggunakan aplikasi.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('about.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-sigap px-5 py-2.5 text-sm font-semibold text-sigap transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">Tentang SIGAP BENCANA</a>
                    <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold text-gray-800 transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sigap">Daftar Akun</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-rose-100 bg-white px-6 py-5 text-center text-xs text-gray-600 sm:text-sm">SIGAP BENCANA &copy; 2026 — Siaga, Tanggap, Selamat.</footer>

    <script>
        (() => {
            const search = document.getElementById('faq-search');
            const category = document.getElementById('faq-category');
            const items = Array.from(document.querySelectorAll('.faq-item'));
            const emptyState = document.getElementById('faq-empty');
            const resultStatus = document.getElementById('faq-result-status');
            if (!search || !category || !emptyState || !resultStatus) return;

            const filterFaqs = () => {
                const query = search.value.trim().toLocaleLowerCase('id-ID');
                const selectedCategory = category.value;
                let shown = 0;

                items.forEach((item) => {
                    const categoryMatches = selectedCategory === 'all' || item.dataset.category === selectedCategory;
                    const textMatches = query === '' || (item.dataset.search || '').includes(query);
                    const visible = categoryMatches && textMatches;
                    item.hidden = !visible;
                    if (visible) shown += 1;
                });

                emptyState.classList.toggle('hidden', shown > 0);
                resultStatus.textContent = shown === 0
                    ? 'Tidak ada pertanyaan yang cocok.'
                    : `${shown} dari ${items.length} pertanyaan ditampilkan.`;
            };

            search.addEventListener('input', filterFaqs);
            category.addEventListener('change', filterFaqs);
        })();
    </script>
</body>
</html>
