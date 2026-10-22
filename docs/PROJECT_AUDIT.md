# Audit Proyek SIGAP BENCANA

Tanggal pemeriksaan: 23 Oktober 2026  
Ruang lingkup: kondisi Laravel/Flutter, autentikasi, route, skema yang sudah ada, dokumentasi kebutuhan, dan mockup mobile Home.

## Ringkasan kondisi saat ini

Proyek memiliki autentikasi web dan Sanctum yang berfungsi, tetapi belum memiliki verifikasi email atau kebijakan kedaluwarsa token aplikasi. Pada pekerjaan tahap pertama, halaman landing tetap publik di `/`, sedangkan Home masyarakat web berada di `/home` dan memerlukan sesi login. Registrasi tetap langsung membuat akun aktif; perilaku tersebut tidak diubah.

Flutter menyimpan token dengan `flutter_secure_storage`, memulihkan sesi saat aplikasi dimulai melalui `GET /api/user`, menghapus sesi lokal ketika server mengembalikan 401, dan mempertahankan token jika jaringan/server tidak tersedia. Logout mencoba menghapus token Sanctum lalu selalu menghapus sesi lokal. Jika server tidak dapat dihubungi, aplikasi menandai bahwa pencabutan token server belum terkonfirmasi.

Home mobile masih berupa layar statis/mockup dan tidak memiliki endpoint Home publik yang terdaftar. Tidak ada sumber data resmi atau aturan publikasi peringatan/berita yang cukup jelas untuk mengimplementasikan integrasi dengan aman. Karena itu pekerjaan ini menyiapkan rencana kontrak, bukan endpoint atau data contoh baru.

## Perubahan dan keputusan yang sudah diterapkan

- `/` menampilkan landing publik (`welcome`); `/home` menampilkan Home masyarakat (`home`) dengan middleware `auth`.
- Login web mengarahkan pengguna ke Home yang terlindungi, sambil tetap menghormati intended URL.
- Flutter memeriksa sesi saat startup, menawarkan retry atau lanjut tanpa login ketika koneksi pemulihan tidak tersedia, dan menyediakan logout dari menu profil.
- Token disimpan di secure storage. Respons 401 saat pemulihan menghapus token lokal; kegagalan jaringan tidak membuang sesi tersimpan. Logout lokal tetap selesai meski server tidak merespons, dengan pemberitahuan bahwa pencabutan server belum terkonfirmasi.
- Pengujian autentikasi mencakup landing publik, proteksi Home, registrasi web/API, login berhasil/gagal, logout web/API, serta akses profil dengan/tanpa token.
- Tidak ada migration dijalankan, database direset, atau aturan verifikasi email diaktifkan.

## Route autentikasi yang tersedia

### Web

| Method | Path | Nama/handler | Akses |
|---|---|---|---|
| GET | `/` | `landing` → `welcome` | Publik |
| GET | `/home` | `home` → `home` | Sesi web (`auth`) |
| GET/POST | `/register` | `register`, `register.store` | Publik |
| GET/POST | `/login` | `login`, `login.store` | Publik |
| POST | `/logout` | `logout` | Sesi web |

### API/mobile

| Method | Path | Akses | Perilaku penting |
|---|---|---|---|
| POST | `/api/register` | Publik | Membuat akun aktif; belum verifikasi email |
| POST | `/api/login` | Publik | Email dan password; mengembalikan token Sanctum |
| GET | `/api/user` | Sanctum | Profil untuk validasi/pemulihan token |
| POST | `/api/logout` | Sanctum | Menghapus token yang sedang dipakai |
| GET/POST | `/api/reports` | Sanctum | Laporan milik pengguna |
| GET | `/api/reports/{id}` | Sanctum | Laporan milik pengguna |
| PATCH | `/api/reports/{id}/verification` | Sanctum + `officer` | Petugas/admin |
| PATCH | `/api/reports/{id}/handling` | Sanctum + `officer` | Petugas/admin |

API login menerima email, sedangkan antarmuka login web/mobile juga menawarkan telepon. Flutter belum mengirim pilihan remember-me ke API. Token tidak menunjukkan kebijakan kedaluwarsa aplikasi selain dukungan kolom Sanctum; masa berlaku harus ditetapkan sebelum penggunaan produksi.

## Keputusan verifikasi email yang masih diperlukan

Registrasi saat ini tetap langsung mengaktifkan akun. Jangan ubah alur tersebut menjadi verifikasi parsial. Sebelum perubahan, pemilik produk perlu menyetujui:

1. Provider email, konfigurasi kredensial, lingkungan pengiriman, dan penanganan kegagalan delivery (BRD OD-07).
2. Format kode (BRD mengarah ke 6 digit), apakah kode disimpan dalam bentuk hash, dan masa berlaku.
3. Batas percobaan verifikasi, batas kirim ulang, cooldown, dan rate limit (BRD OD-13).
4. Dampak verifikasi terhadap `is_active`, `email_verified_at`, login web/API, dan akun lama yang sudah aktif.
5. Pengalaman pengguna untuk kode kedaluwarsa, salah, atau email tidak terkirim.

Setelah keputusan disetujui, implementasi harus mencakup delivery, penyimpanan aman, expiry, throttle, UI dan pengujian end-to-end secara bersamaan.

## Rencana integrasi Home mobile

### Acuan visual

`docs/reference/README.md`, contact sheet mobile, dan PNG penuh `mobile_mockup/page_07.png` telah ditinjau. Pemetaan layar ke Home ada di `docs/UI_REFERENCE.md`. Tampilan Home saat ini dipertahankan; teks contoh peringatan diberi label contoh dan belum boleh dianggap informasi resmi.

### Kondisi API dan data

Endpoint `GET /api/v1/home` di `docs/API_DESIGN.md` adalah rancangan, bukan route yang tersedia. Model `EarlyWarning` dan `News` serta tabel terkait ada, tetapi keberadaan tabel tidak membuktikan ada data yang diterbitkan atau layak untuk publik. Peringatan belum memiliki kontrak publikasi yang memadai (misalnya waktu terbit/sumber publik eksplisit); berita memiliki status/tanggal terbit tetapi belum memiliki route publik dan aturan editorial final.

BRD masih membuka sumber/provider dan kepemilikan verifikasi data (OD-02/03), cakupan wilayah dan aturan publikasi/staleness, serta apakah Home publik atau dipersonalisasi. Maka belum aman membuat endpoint atau menghubungkan UI ke record yang belum disahkan.

### Langkah kontrak yang diusulkan

Setelah pemilik data dan aturan publikasi ditetapkan, rancang satu resource Home yang dapat memuat:

- peringatan yang dipublikasikan oleh sumber/role berwenang, beserta sumber, waktu terbit, waktu mulai/kedaluwarsa, dan status kesegaran;
- berita yang berstatus published, beserta atribusi sumber dan waktu terbit;
- metadata shortcut layanan yang tidak menyiratkan fitur/API yang belum tersedia;
- lokasi pengguna hanya jika kebutuhan personalisasi disetujui dan izin lokasi diberikan.

Resource perlu mengembalikan daftar kosong secara sah. Flutter menyediakan loading, error/retry, dan empty state serta tidak mengubah mockup menjadi klaim bencana nyata. Jangan memasukkan contoh statis ke response API.

## Skema dan integritas

`php artisan migrate:status` menunjukkan 13 migration aplikasi berstatus Ran pada pemeriksaan ini. Tidak ada migration dijalankan untuk pekerjaan tahap pertama. `incident_histories` memiliki FK laporan dan SOS nullable; skema belum memastikan tepat satu objek terhubung. Tidak ada migration baru yang diperlukan untuk autentikasi atau untuk rencana Home saat ini.

Tabel peringatan, zona risiko, posko evakuasi, berita, panduan, kontak, notifikasi, dan SOS tersedia sebagian, tetapi API, kebijakan sumber/publikasi, atau workflow operasionalnya belum lengkap. Jangan mengekspos data tersebut hanya karena tabel sudah ada.

## Risiko dan batasan tersisa

- Email/telepon tidak konsisten antara form login dan kontrak API.
- Belum ada throttling login yang tampak pada route auth; kebijakan brute-force perlu ditambahkan sebelum produksi.
- Token Sanctum belum memiliki masa berlaku aplikasi yang disepakati.
- Jika logout Flutter dilakukan tanpa koneksi, token lokal terhapus tetapi token server mungkin tetap berlaku sampai dicabut/invalidasi lain. Aplikasi memberi tahu pengguna bahwa pencabutan belum terkonfirmasi.
- API Home, warnings, dan news publik belum ada; tidak tersedia aturan sumber, editorial, publikasi, atau staleness yang final.
- RBAC operasi, workflow SOS, upload media laporan, serta kebijakan privasi/lokasi masih membutuhkan keputusan BRD.

## Pemeriksaan dan pengujian tahap ini

| Pemeriksaan | Hasil aktual |
|---|---|
| `php -v` / `composer -V` | PHP 8.3.33 dan Composer 2.10.2 tersedia |
| `php artisan route:list --except-vendor` | 16 route ditampilkan setelah pemisahan landing dan Home terlindungi |
| `php artisan migrate:status` | 13 migration aplikasi berstatus Ran; tidak ada migration dijalankan |
| `php artisan test --compact` | Lulus: 13 tes, 44 assertions |
| `vendor/bin/pint --dirty --format agent` | Lulus; file PHP yang berubah diformat |
| `flutter analyze --no-pub` | Lulus: No issues found |
| `flutter test --no-pub` | Lulus: 1 widget test |

Tes memakai konfigurasi testing SQLite in-memory; tidak menghapus atau mereset database MySQL proyek. Tidak ada tes integrasi terhadap server API eksternal atau email provider.

## Langkah berikutnya

1. Sepakati provider email, format/kedaluwarsa kode, batas percobaan/resend, dan kebijakan aktivasi sebelum memulai verifikasi email.
2. Putuskan sumber serta otoritas publikasi peringatan/berita, wilayah, status fresh/stale, dan apakah Home perlu personalisasi lokasi.
3. Setelah keputusan tersebut jelas, finalisasi kontrak endpoint Home dan resource dengan fixture data non-produksi yang jelas dalam tes, lalu implementasikan loading/error/empty state Flutter.
4. Tetapkan kebijakan expiry token dan login email/telepon serta rate limit sebelum persiapan produksi.

Pekerjaan ini menyelesaikan pemeriksaan autentikasi, pengamanan akses Home web dan sesi Flutter, pengujian terkait, serta rencana integrasi Home. Modul lain belum diimplementasikan.
