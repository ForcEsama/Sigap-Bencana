# Referensi Visual UI SIGAP BENCANA

Dokumen ini memetakan semua halaman PNG pada paket mockup ke layar produk. Contact sheet yang diperiksa: 3 mobile dan 4 desktop. PNG resolusi penuh diperiksa untuk layar yang sudah memiliki padanan implementasi (mobile 03–07 dan desktop 01–05). Untuk halaman lain, pemetaan ini berdasarkan contact sheet; buka PNG individual resolusi penuh sebelum mengimplementasikan halaman tersebut.

## Aturan penggunaan

- Gunakan PNG sebagai acuan visual dan BRD sebagai acuan kebutuhan, aturan bisnis, keamanan, serta status data.
- Nilai, koordinat, identitas, tanggal, berita, peringatan, kapasitas, dan status bantuan yang tampak pada mockup adalah data contoh, bukan informasi bencana resmi.
- Pastikan sumber serta masa berlaku peringatan, verifikasi laporan, waktu pembaruan evakuasi, izin lokasi, dan status SOS ditampilkan dengan jujur sesuai BRD.
- BRD mencatat inkonsistensi visual yang perlu diselesaikan: timeline SOS berbeda/kurang tahap, waktu halaman “Bantuan Tiba” tidak konsisten, kategori notifikasi tidak konsisten, jumlah kontak berbeda, peta memakai legenda/istilah berbeda, serta daftar jenis darurat belum final. Keputusan terkait tetap terbuka (antara lain OD-05, OD-14, OD-16).
- Palet yang dinyatakan pada BRD: tombol utama `#D2002F`, aksen `#FF465A`, `#FF0520`, `#FF8794`, latar `#FFECED`, font Poppins. Halaman mobile 01 adalah referensi palet dari Figma, bukan layar aplikasi.

## Mockup mobile

Sumber menurut `docs/reference/README.md`: `Mockup Sigap Bencana(1).pdf`. Total 26 halaman. File PDF bernama demikian tidak ada pada folder; paket PNG yang tersedia dipetakan sesuai inventaris README dan visualnya.

| Hal. | PNG | Layar / tujuan | Detail yang terlihat | Padanan kode saat ini |
|---:|---|---|---|---|
| 01 | [page_01.png](reference/mobile_mockup/page_01.png) | Palet warna / aset Figma | Baris swatch merah, pink, dan netral; bukan layar aplikasi. | Tidak ada. Gunakan warna BRD sebagai token. |
| 02 | [page_02.png](reference/mobile_mockup/page_02.png) | Splash | Foto latar gunung, tagline “Bersama lebih siap hadapi bencana”, indikator bawah. | Belum ada splash terpisah; `mobile/lib/main.dart` langsung menampilkan Welcome. |
| 03 | [page_03.png](reference/mobile_mockup/page_03.png) | Welcome | Logo dan tagline di atas foto relawan/gunung dengan potongan lengkung, latar pink. | `WelcomePage` di `mobile/lib/main.dart`; PNG resolusi penuh diperiksa. |
| 04 | [page_04.png](reference/mobile_mockup/page_04.png) | Daftar akun | Form nama, email, telepon, sandi/konfirmasi, persetujuan syarat/privasi, tombol merah. | `mobile/lib/register_page.dart`; PNG resolusi penuh diperiksa. |
| 05 | [page_05.png](reference/mobile_mockup/page_05.png) | Verifikasi akun | Ikon email, tujuan email, enam kotak kode, hitung ulang 00:30, tombol verifikasi. | Belum ada screen/verifikasi API. PNG resolusi penuh diperiksa. |
| 06 | [page_06.png](reference/mobile_mockup/page_06.png) | Masuk | Email/telepon, sandi, Ingat saya, lupa sandi, Google, tautan daftar. | `mobile/lib/login_page.dart`; PNG resolusi penuh diperiksa. |
| 07 | [page_07.png](reference/mobile_mockup/page_07.png) | Home | Lokasi, kartu peringatan merah, empat layanan, berita, bottom nav Home/Map/SOS/Notification/Profile. | `mobile/lib/home_page.dart`; PNG resolusi penuh diperiksa. Konten masih statis. |
| 08 | [page_08.png](reference/mobile_mockup/page_08.png) | Peta risiko | Peta warna zona, pencarian, marker lokasi/posko/laporan, legenda, bottom nav. | Belum ada screen peta/API GIS. |
| 09 | [page_09.png](reference/mobile_mockup/page_09.png) | Aktifkan SOS | Peta diredupkan, tombol SOS yang ditahan 3 detik dan tombol batal. | Belum ada alur SOS. |
| 10 | [page_10.png](reference/mobile_mockup/page_10.png) | Notifikasi | Filter Semua/Peringatan/Informasi/Aktivitas, kartu bertipe dan waktu. | Belum ada screen/API notifikasi. |
| 11 | [page_11.png](reference/mobile_mockup/page_11.png) | Profil Saya / menu | Identitas akun, Data Pribadi, Lokasi Tersimpan, Preferensi, Kontak Darurat Saya, Riwayat, Bantuan, Tentang. | Belum ada screen profil. |
| 12 | [page_12.png](reference/mobile_mockup/page_12.png) | Tempat Evakuasi | Peta dan daftar lokasi dengan jarak serta kapasitas/status. | Belum ada screen/API evakuasi. |
| 13 | [page_13.png](reference/mobile_mockup/page_13.png) | Pilih jenis SOS | Lima pilihan jenis darurat dan tombol lanjut. Daftar final belum disepakati. | Belum ada alur SOS. |
| 14 | [page_14.png](reference/mobile_mockup/page_14.png) | Status SOS: dikirim/menunggu | ID SOS, waktu, status menunggu konfirmasi dan timeline. | Belum ada alur SOS/API. Status harus dibedakan dari dispatch aktual. |
| 15 | [page_15.png](reference/mobile_mockup/page_15.png) | Data pribadi | Profil, kontak, alamat domisili, tanggal lahir, gender, aksi edit. | Belum ada screen/API profil. |
| 16 | [page_16.png](reference/mobile_mockup/page_16.png) | Peringatan bencana | Pencarian, filter dini/kejadian aktif, kartu gambar, risiko/status, detail. | Belum ada screen/API peringatan. |
| 17 | [page_17.png](reference/mobile_mockup/page_17.png) | Konfirmasi lokasi SOS | Jenis darurat, peta/pin dan alamat, ubah jenis, perbarui lokasi, kirim. | Belum ada alur SOS/lokasi. |
| 18 | [page_18.png](reference/mobile_mockup/page_18.png) | Status SOS: perkembangan | Kartu permintaan, timeline bantuan dan estimasi perjalanan pada contoh. | Belum ada alur SOS. Estimasi hanya boleh tampil bila datanya memadai. |
| 19 | [page_19.png](reference/mobile_mockup/page_19.png) | Edit profil | Foto profil, informasi pribadi, lokasi, tambahan tanggal lahir/gender, simpan. | Belum ada screen/API edit profil. |
| 20 | [page_20.png](reference/mobile_mockup/page_20.png) | Lapor kejadian | Jenis, lokasi, deskripsi, tambah foto/video, kirim. | Belum ada screen mobile/API media laporan. |
| 21 | [page_21.png](reference/mobile_mockup/page_21.png) | Mengirim SOS | Overlay/progress permintaan, animasi lingkar SOS. | Belum ada alur SOS. Kegagalan jaringan harus tetap berstatus belum terkonfirmasi. |
| 22 | [page_22.png](reference/mobile_mockup/page_22.png) | Status SOS: bantuan tiba | Status selesai/tiba, timeline hijau dan pesan keselamatan. | Belum ada alur SOS; BRD meminta waktu dan pelaku status yang akurat. |
| 23 | [page_23.png](reference/mobile_mockup/page_23.png) | Kontak darurat | Daftar nomor/instansi dan tombol telepon. | Belum ada screen/API kontak. Nomor harus diverifikasi sebelum rilis. |
| 24 | [page_24.png](reference/mobile_mockup/page_24.png) | Berita & Edukasi | Tab Semua/Berita/Edukasi, artikel utama, artikel lainnya. | Belum ada screen/API berita dan edukasi. |
| 25 | [page_25.png](reference/mobile_mockup/page_25.png) | SOS berhasil dikirim | Konfirmasi dan ringkasan ID, jenis, lokasi, waktu; tombol kembali. | Belum ada alur SOS. Hindari klaim tim sudah menerima kecuali ada konfirmasi yang nyata. |
| 26 | [page_26.png](reference/mobile_mockup/page_26.png) | Tentang SIGAP Bencana | Visi, misi, dan logo mitra. | Belum ada screen mobile Tentang. Mitra/logo harus mendapat izin. |

## Mockup website desktop

Sumber: `Mockup Sigap Bencana (4).pdf`. Total 33 halaman.

| Hal. | PNG | Layar / aset | Detail yang terlihat | Padanan kode saat ini |
|---:|---|---|---|---|
| 01 | [page_01.png](reference/desktop_mockup/page_01.png) | Landing publik | Navbar logo dan Beranda/Tentang/Bantuan; hero foto relawan, CTA Masuk/Daftar. | Route `/` memakai `home.blade.php`, yang lebih dekat ke page 05; `welcome.blade.php` adalah view lain yang tidak terhubung route. PNG resolusi penuh diperiksa. |
| 02 | [page_02.png](reference/desktop_mockup/page_02.png) | Buat akun | Form kiri dengan syarat/privasi; foto dan tagline di panel kanan. | `/register`, `auth/register.blade.php`; PNG resolusi penuh diperiksa. |
| 03 | [page_03.png](reference/desktop_mockup/page_03.png) | Verifikasi akun | Kartu putih tengah, kode enam digit dan resend 00:30. | Belum ada route/view verifikasi. PNG resolusi penuh diperiksa. |
| 04 | [page_04.png](reference/desktop_mockup/page_04.png) | Masuk | Kartu tengah, email/telepon, ingat saya, lupa sandi, Google, daftar. | `/login`, `auth/login.blade.php`; PNG resolusi penuh diperiksa. |
| 05 | [page_05.png](reference/desktop_mockup/page_05.png) | Home masyarakat | Header horizontal Home/Map/News/Menu, notifikasi/profil, hero foto bencana dan empat shortcut. | Route `/` dan `home.blade.php` menampilkan struktur serupa; PNG resolusi penuh diperiksa. Route belum dilindungi auth. |
| 06 | [page_06.png](reference/desktop_mockup/page_06.png) | Peringatan Bencana | Sidebar masyarakat, header akun, search/filter jenis/wilayah dan kartu peringatan. | Belum ada route/view. |
| 07 | [page_07.png](reference/desktop_mockup/page_07.png) | Peta Risiko | Sidebar, judul peta, peta besar dan legenda tingkat tinggi/sedang/rendah. | Belum ada route/view/GIS. |
| 08 | [page_08.png](reference/desktop_mockup/page_08.png) | Lapor Kejadian | Form dan panel informasi penting/112, lokasi, lampiran. | Belum ada route/view web. |
| 09 | [page_09.png](reference/desktop_mockup/page_09.png) | Tempat Evakuasi | Daftar lokasi di kiri dan peta marker di kanan. | Belum ada route/view web. |
| 10 | [page_10.png](reference/desktop_mockup/page_10.png) | SOS: pilih keadaan darurat | Stepper 1/2, daftar jenis, batal dan lanjut. | Belum ada route/view SOS. |
| 11 | [page_11.png](reference/desktop_mockup/page_11.png) | Notifikasi | Filter kategori dan kartu aktivitas/peringatan dengan badge. | Belum ada route/view. |
| 12 | [page_12.png](reference/desktop_mockup/page_12.png) | Ikon navigasi: Home | Ekspor aset ikon, bukan layar. | Gunakan sebagai referensi aset/sidebar jika resolusi/sumber aset sesuai. |
| 13 | [page_13.png](reference/desktop_mockup/page_13.png) | Ikon navigasi: Peringatan Bencana | Ekspor aset ikon. | Referensi ikon sidebar. |
| 14 | [page_14.png](reference/desktop_mockup/page_14.png) | Ikon navigasi: Peta Risiko | Ekspor aset ikon. | Referensi ikon sidebar. |
| 15 | [page_15.png](reference/desktop_mockup/page_15.png) | Ikon navigasi: Lapor Kejadian | Ekspor aset ikon. | Referensi ikon sidebar. |
| 16 | [page_16.png](reference/desktop_mockup/page_16.png) | Ikon navigasi: Tempat Evakuasi | Ekspor aset ikon. | Referensi ikon sidebar. |
| 17 | [page_17.png](reference/desktop_mockup/page_17.png) | Ikon navigasi: Berita & Edukasi | Ekspor aset ikon. | Referensi ikon sidebar. |
| 18 | [page_18.png](reference/desktop_mockup/page_18.png) | Ikon navigasi: Kontak Darurat | Ekspor aset ikon. | Referensi ikon sidebar. |
| 19 | [page_19.png](reference/desktop_mockup/page_19.png) | Ikon navigasi: Tentang | Ekspor aset ikon. | Referensi ikon sidebar. |
| 20 | [page_20.png](reference/desktop_mockup/page_20.png) | Ikon navigasi: Profil | Ekspor aset ikon. | Referensi ikon sidebar. |
| 21 | [page_21.png](reference/desktop_mockup/page_21.png) | Ikon navigasi: Keluar | Ekspor aset ikon. | Referensi ikon/sidebar. |
| 22 | [page_22.png](reference/desktop_mockup/page_22.png) | Berita & Edukasi | Grid artikel dan filter Semua/Berita/Edukasi/Tips Keselamatan/Infografis. | Belum ada route/view. |
| 23 | [page_23.png](reference/desktop_mockup/page_23.png) | Kontak Darurat | Grid instansi/nomor, tips panggilan, layanan lain dan kontak daerah. | Belum ada route/view. |
| 24 | [page_24.png](reference/desktop_mockup/page_24.png) | Tentang | Visi, misi, mitra. | Belum ada route/view. |
| 25 | [page_25.png](reference/desktop_mockup/page_25.png) | Profil | Ringkasan akun, informasi kontak, alamat, data tambahan dan edit. | Belum ada route/view profil. |
| 26 | [page_26.png](reference/desktop_mockup/page_26.png) | Edit Profil | Form profil, foto, format maks 2 MB, hapus akun, batal/simpan. | Belum ada route/view profil. |
| 27 | [page_27.png](reference/desktop_mockup/page_27.png) | Dialog keluar | Konfirmasi batal/keluar. | Logout POST tersedia, tetapi dialog/konfirmasi belum ada. |
| 28 | [page_28.png](reference/desktop_mockup/page_28.png) | SOS: konfirmasi lokasi | Stepper 2/2, jenis/lokasi peta, ubah jenis, perbarui, batal/kirim. | Belum ada route/view SOS. |
| 29 | [page_29.png](reference/desktop_mockup/page_29.png) | SOS: menunggu konfirmasi | ID, status waiting, timeline empat titik. | Belum ada route/view SOS. |
| 30 | [page_30.png](reference/desktop_mockup/page_30.png) | SOS: mengirim | Tampilan proses dengan lingkar SOS. | Belum ada route/view SOS. |
| 31 | [page_31.png](reference/desktop_mockup/page_31.png) | SOS: dikonfirmasi / petugas menuju | Kartu status dan timeline dengan estimasi tiba pada contoh. | Belum ada route/view SOS. Estimasi hanya bila sumber data valid. |
| 32 | [page_32.png](reference/desktop_mockup/page_32.png) | SOS: berhasil dikirim | Konfirmasi dan ringkasan permintaan, CTA kembali. | Belum ada route/view SOS. Copy harus membedakan server menerima dari pihak lapangan menerima. |
| 33 | [page_33.png](reference/desktop_mockup/page_33.png) | SOS: bantuan tiba | Timeline lengkap hijau dan pesan tim tiba. | Belum ada route/view SOS. Pastikan urutan timestamp sesuai BRD. |

## Shell dan navigasi

- Mobile memakai lima destinasi bawah: Home, Map, tombol SOS pusat, Notification, Profile. Flow SOS (09, 13–14, 17–18, 21–22, 25) adalah screen/transisi, bukan tab baru.
- Desktop page 05 memakai header horizontal. Page 06–33 memakai sidebar dan header akun; page 12–21 adalah ikon yang digunakan sidebar, bukan layar mandiri. Gunakan satu layout bersama untuk halaman dalam, tetapi pertahankan landing/auth publik terpisah.
- Dashboard operasional tidak ada dalam mockup; BRD menyatakan perlu desain terpisah dan keputusan operasional. Jangan menyamakan dashboard masyarakat dengan dashboard operator.
- Peta, berita, peringatan, kontak, dan posko dalam gambar adalah contoh visual. Jangan mengisi produk dengan data contoh seolah resmi.

## Status inspeksi gambar

- Semua 7 contact sheet dibuka dan ditinjau: `mobile_mockup/contact_sheet_01.jpg` sampai `_03.jpg`, serta `desktop_mockup/contact_sheet_01.jpg` sampai `_04.jpg`.
- PNG penuh yang dibuka: mobile `page_03.png`–`page_07.png`; desktop `page_01.png`–`page_05.png`. Ini adalah layar yang paling dekat dengan implementasi sekarang.
- PNG halaman lainnya dipetakan dari contact sheet saja. Sebelum mengimplementasikan screen tertentu, buka PNG individual terkait pada resolusi penuh untuk memeriksa teks, jarak, komponen, dan variasi state.
