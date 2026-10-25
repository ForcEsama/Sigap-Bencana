# Audit Integrasi SIGAP BENCANA

Tanggal audit: 26 Oktober 2026. Base project yang dipertahankan: workspace Dirga di `C:\laragon\www\sigap-bencana`.

## Metode dan batas pemeriksaan

- Source Dirga dan Duta diekstrak ke folder audit yang terpisah. `.env` dari arsip Duta, `.git`, vendor, dan `node_modules` tidak diekstrak. Arsip sumber tidak diubah.
- Kedua ZIP database hanya berisi file tablespace `.ibd` (22 file Dirga, 11 file Duta). Metadata tabel dibaca menggunakan `ibd2sdi` MySQL 8.4.3. Pembacaan ini tidak mengekstrak atau membandingkan isi baris dan tidak membuktikan row count arsip.
- Database aktif diperiksa read-only memakai Laravel Boost. Tidak ada migration, import, atau query tulis pada audit ini.
- Bukti runtime workspace: Laravel feature suite, Flutter analyzer, route list, status migration, dan schema aktif. Tes suite Duta tidak dijalankan di project terpisah karena arsip dependensi/runtime bukan lingkungan yang dipasang untuk menjalankan project itu.

## Identitas dan teknologi

| Aspek | Dirga (base) | Duta (pembanding) | Keputusan |
|---|---|---|---|
| Git source | `dirga-21/10`, `92379ba8be2de01c1acad77c3ef3975e929b95d3` | Branch `main`; arsip sebelumnya teridentifikasi pada `06d450839f0a19ea40e851935c39ea17cc31c116` | Dirga tetap base |
| Backend | Laravel constraint `^13.17`, PHP `^8.3`, Sanctum `^4.0` | Sama; lock memuat Laravel 13.38.0 dan Sanctum 4.3.3 | Tidak perlu dependency baru |
| Flutter | 39 file Dart, feature UI dan API untuk berita, notifikasi, profil, laporan, peta, SOS, warning | 7 file Dart, terutama konfigurasi dan service; hampir tidak ada halaman fitur | Pertahankan mobile Dirga |
| Blade | 9 view pada arsip base | 27 view | Adopsi layar web Duta secara selektif |
| Controller | 14 controller pada Dirga | 15 controller pada Duta | API/controller utama Dirga dipertahankan; controller web tambahan Duta dipakai/adaptasi |
| Service | Tidak memiliki folder service khusus pada snapshot Dirga | 8 service termasuk feed, cache, notifikasi dan validator evakuasi | Adopsi layanan web yang teruji, tanpa mengganti API Dirga |
| Tes Laravel | 13 file tes | 13 file tes | Tes terintegrasi dijalankan pada workspace |
| Frontend dependencies | Vite 8/Tailwind 4; Flutter memakai `flutter_localizations` | Vite/Tailwind sama, tetapi `pubspec.yaml` Duta tidak mencantumkan `flutter_localizations` | Tidak menambah paket; pertahankan dukungan lokalisasi Flutter Dirga |

## Evaluasi fitur

| Fitur | Dirga | Duta | Pilihan dan alasan |
|---|---|---|---|
| Autentikasi dan API mobile | Sanctum, API versi `/api/v1`, token, API profil/laporan/SOS/berita/peringatan/notifikasi | API lebih tipis: auth dan laporan; sebagian kontrak tidak tersedia | Pertahankan Dirga; konsumen Flutter sudah menggunakan kontrak ini |
| Mobile | Alur UI dan service lebih lengkap, termasuk sesi, peta, laporan, warning dan SOS | Source mobile hanya 7 file dan tidak membawa halaman web sebagai mobile screen | Pertahankan implementasi Dirga |
| Website | Home, auth, profile, laporan, peta risiko | Tambahan halaman web notifikasi, berita/edukasi, peringatan, SOS, kontak, evakuasi, bantuan dan tentang | Adopsi web Duta ke route Laravel Dirga; route privat memakai `auth` |
| Berita dan edukasi | API dan Flutter membedakan berita/edukasi dan memfilter publikasi | Feed web BMKG/BNPB, cache dan detail artikel | Adaptasi service web Duta; berita, edukasi dan peringatan tetap dipisah |
| Peringatan resmi | Early warning API memakai metadata sumber dan masa berlaku | Web feed CAP BMKG, tetapi controller mentah Duta mencampur beberapa tipe informasi | Pertahankan aturan pemilihan aktif Dirga dan hanya pakai peringatan CAP yang tervalidasi di halaman peringatan |
| SOS | API/mobile memakai idempotency key, timeline, ownership dan status transition | Menambahkan halaman alur SOS web | Integrasikan layar web dengan model dan status Dirga; tidak mengubah aturan API |
| Profil | Name/email/phone tersimpan dan dapat dibaca mobile | Form web menambah alamat domisili, wilayah administratif, tanggal lahir, gender | Field tambahan diadopsi secara nullable; API dan Flutter kini mengirim field yang sama. Aktivasi DB menunggu perbaikan MySQL |
| Laporan warga | API dan web, verifikasi dan status penanganan | Web flow tambahan | Gunakan tabel, status, ownership, dan notifikasi Dirga |
| Tempat evakuasi | Tabel ada, belum terisi | Halaman, foto, dan CSV kandidat perencanaan lama | Halaman dipakai; CSV tidak dianggap data operasional karena koordinat/status belum terverifikasi |
| Kontak darurat | Tabel ada, sebelumnya kosong | Halaman dan seeder kontak | Seeder idempotent diintegrasikan; enam record sudah ada di DB aktif |
| Peta risiko | Mobile/web memakai InaRISK/ArcGIS; tabel zona lokal ada di model tapi schema aktif tak lengkap | View web dengan zona lokal dan layer InaRISK | Pertahankan sumber peta InaRISK; zona DB lokal tidak ditampilkan sebagai data sampai schema dan publikasinya tervalidasi |

## Kontradiksi, risiko, dan status

- Dua dump schema sama pada seluruh tabel yang sama kecuali `users`; Duta menambah delapan kolom profil nullable. Detail ada di [`DATABASE_RECONCILIATION.md`](DATABASE_RECONCILIATION.md).
- Database aktif memiliki tabel yang lebih luas daripada dump Duta, tetapi `risk_zones` hanya mempunyai `id`, `created_at`, `updated_at`; delapan field profil tambahan juga belum ada.
- Laravel migration status aktif memuat empat migration pending: repair kolom peta risiko, tabel metadata warning, idempotency SOS, serta repair field profil. Dua tabel terakhir untuk warning metadata dan SOS idempotency sudah ada di MySQL, sehingga migration mereka perlu idempotent.
- Percobaan DDL pada tugas sebelumnya menghasilkan MySQL 8.4.3 error 3958 `Failed to delete sdi ... due to missing record` pada `risk_zones` dan `users`. Tabel dicek ulang dan tidak berubah. Jangan mencoba `DROP`, import `.ibd`, atau recreate tabel untuk mengakali masalah SDI.
- `api/user` sebelumnya hanya memperbarui field dasar. Perubahan saat ini menambahkan field opsional; klien lama tetap mengirim body lama. Server mengembalikan 503 yang jelas bila klien meminta field tambahan sebelum migration tersedia.
- Endpoint `GET /api/reports` membatasi query ke laporan pemilik yang sedang login, tetapi mengembalikan paginator model Eloquent secara langsung. Ini bukan kebocoran lintas-pengguna; kontrak sebaiknya kelak memakai Resource eksplisit agar kolom privat tidak ikut terbuka saat model berkembang.
- Beberapa halaman bergantung pada feed eksternal BMKG/BNPB, CDN dan tiles peta. Tes memakai HTTP fakes atau fixture; jaringan eksternal dan tampilan browser belum menjadi bukti dalam suite.
- Dump `.ibd` bukan backup SQL yang dapat digabungkan dengan menyalin file. Isi records arsip tidak diperiksa atau diimpor.

## Keputusan

1. Pertahankan Laravel API, auth, database dasar, dan mobile Dirga sebagai acuan.
2. Pertahankan fitur web Duta yang sudah diadaptasi dan diuji; jangan menyalin API Duta yang lebih tipis.
3. Selaraskan profil web/mobile lewat delapan field tambahan yang nullable dan kontrak PATCH API yang additive.
4. Jangan menerapkan migration ke database aktif sampai file cadangan tervalidasi dan error SDI MySQL diperiksa operator.
5. Jangan impor `.ibd` dari salah satu arsip; bila data rows perlu rekonsiliasi, minta ekspor SQL resmi dari masing-masing pemilik database.
6. Pemetaan langkah berikut ada di [`INTEGRATION_PLAN.md`](INTEGRATION_PLAN.md); endpoint aktual didokumentasikan di [`API_CONTRACT.md`](API_CONTRACT.md).
