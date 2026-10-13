# Monitoring Berita Sumut

Aplikasi Laravel untuk menerima berita hasil analisis n8n, memverifikasi hasil AI, mengelola isu dan penugasan, serta membuat laporan harian. Antarmuka menggunakan Blade, Tailwind CSS, dan Chart.js. Database bawaan menggunakan SQLite; koneksi MySQL dapat diatur melalui `.env`.

## Fitur per tahap

| Tahap | Fitur |
|---|---|
| 1 — Fondasi editorial | Login, empat peran pengguna, kategori/wilayah/OPD, koreksi hasil AI, riwayat pemeriksaan, dan proteksi hasil terverifikasi dari kiriman ulang n8n |
| 2 — Tindak lanjut | Isu dari berita, penanggung jawab, prioritas, tenggat, perubahan status, komentar, notifikasi aplikasi/email, preferensi pemantauan, dan pengingat keterlambatan |
| 3 — Pelaporan dan pemantauan | Saran artikel serupa dengan konfirmasi analis, penautan/pemisahan artikel, laporan harian berisi sumber, PDF, dan pencatatan status eksekusi n8n |

Fitur lama tetap tersedia: dashboard sentimen, grafik 14 hari dan mingguan, statistik sumber, pencarian/filter, arsip, ekspor CSV, notifikasi browser, dan API mobile. Angka statistik/grafik dashboard menunjukkan seluruh data; filter berlaku pada daftar berita dan ekspor. Laporan harian menggunakan berita terverifikasi untuk sorotan dan statistik sentimen.

## Persyaratan

- PHP **8.4.1 atau lebih baru dalam seri 8.x**. Persyaratan ini mengikuti dependensi terkunci dalam `composer.lock`.
- Composer 2; ekstensi PHP `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, dan `pdo_sqlite` atau `pdo_mysql`. `gd` diperlukan bila laporan dikembangkan menggunakan gambar.
- Node.js 22.12 atau lebih baru dan npm untuk membangun aset Vite.
- Queue worker dan scheduler untuk notifikasi, pengingat, dan laporan otomatis.

## Memperbarui instalasi lama (Laragon/PowerShell)

Cadangkan database yang benar-benar dipakai dan `.env` terlebih dahulu. Jalankan dari folder proyek, dengan perubahan lokal sudah disimpan dalam commit atau stash milikmu.

```powershell
git fetch origin
git switch --track origin/codex/monitoring-berita-workflow
composer install
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --seed
php artisan app:create-admin
php artisan serve
```

Jika branch tersebut sudah ada di komputer, gunakan `git switch codex/monitoring-berita-workflow` lalu `git pull --ff-only`.

- Pertahankan `APP_KEY` dan pengaturan database pada `.env` lama. Jangan menjalankan `migrate:fresh` pada database yang berisi data.
- Migration menambahkan tabel/kolom, mempertahankan berita lama, dan memberi status `pending` untuk pemeriksaan awal.
- Pengguna lama mendapat peran `leader` (baca laporan). Buat akun admin dengan email baru melalui `app:create-admin`, lalu kelola pengguna lama dari menu **Pengguna**.
- Tidak ada kata sandi admin bawaan dan tidak ada pendaftaran publik. Perintah admin meminta kata sandi secara tersembunyi.
- API mobile sekarang membutuhkan Bearer token; lihat [panduan API](docs/API.md).
- File `monitoring_berita_sumut` yang sudah ada di histori repositori adalah SQLite. Pilih database aktif berdasarkan `.env`; file itu tidak otomatis dipakai oleh instalasi baru.

Periksa atau tambahkan konfigurasi berikut pada `.env`:

```dotenv
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_URL=http://localhost:8000
QUEUE_CONNECTION=database
NEWS_INGEST_TOKEN=
N8N_TRIGGER_WEBHOOK_URL=
N8N_TRIGGER_TOKEN=
```

Isi kedua token n8n dengan nilai rahasia yang berbeda dan cocok dengan konfigurasi workflow. Token ingest kosong selalu ditolak. Pada server yang diakses dari n8n, `APP_URL` harus berisi URL HTTPS yang dapat dijangkau n8n.

## Instalasi baru

```powershell
git clone --branch codex/monitoring-berita-workflow https://github.com/nnaelnainggolan/monitoring-berita-sumut.git
cd monitoring-berita-sumut
composer install
npm ci
npm run build
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite
php artisan migrate --seed
php artisan app:create-admin
php artisan serve
```

Untuk Bash, gunakan `cp .env.example .env` dan `touch database/database.sqlite`. Untuk MySQL, buat database kosong dan ubah `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sebelum migration.

Buka `http://localhost:8000`, masuk dengan admin yang baru dibuat, lalu tambahkan pengguna, wilayah, dan OPD. Seeder hanya membuat kategori awal; nama wilayah/OPD diisi sesuai kebutuhan instansi.

## Menjalankan pekerjaan latar belakang

Jalankan di terminal terpisah dari server web:

```powershell
php artisan queue:work --tries=3
```

Terminal lain untuk pengembangan lokal:

```powershell
php artisan schedule:work
```

Pada server Linux, jalankan queue worker melalui process manager dan cron setiap menit:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Jadwal menggunakan `APP_TIMEZONE`:

| Pekerjaan | Jadwal | Perilaku |
|---|---|---|
| `issues:remind` | Setiap jam, 08.00–18.00 | Pengingat isu terlambat, maksimal sekali per isu per hari |
| `reports:daily` | 07.30 | Snapshot laporan untuk tanggal kemarin |
| `workflow:mark-stale` | Setiap 5 menit | Menandai eksekusi yang tidak memberi kabar selama 15 menit sebagai belum terkonfirmasi |

Notifikasi aplikasi dan email menggunakan queue. Email bersifat opt-in dan membutuhkan konfigurasi SMTP; `MAIL_MAILER=log` bawaan hanya mencatat pesan di log. Setelah deploy, jalankan `php artisan queue:restart`.

## Penggunaan

1. n8n mengirim berita; operator memantau halaman Berita.
2. Analis membuka detail → **Periksa analisis**, memperbaiki ringkasan/sentimen/urgensi/klasifikasi, lalu menulis catatan dan memverifikasi.
3. Pilih **Buat isu dari berita**, tentukan prioritas, penanggung jawab, dan tenggat.
4. Analis memverifikasi isu. Petugas yang ditugaskan mengubah status ke **Ditindaklanjuti**, kemudian **Selesai**, dengan catatan penanganan.
5. Periksa saran artikel terkait di halaman isu. Penautan memerlukan konfirmasi; artikel dan tautan sumber tidak dihapus.
6. Buka **Laporan**, pilih tanggal, lalu unduh PDF. Pimpinan dapat membaca dan mengunduh laporan yang sudah dibuat.

| Hak akses | Admin | Analis | Petugas | Pimpinan |
|---|---|---|---|---|
| Membaca berita, isu, laporan | Ya | Ya | Ya | Ya |
| Mengarsipkan artikel | Ya | Ya | Ya | — |
| Memverifikasi berita, membuat/menugaskan isu | Ya | Ya | — | — |
| Memperbarui penanganan dan komentar | Semua isu | Semua isu | Isu yang ditugaskan | — |
| Menghubungkan artikel, membuka kembali isu selesai | Ya | Ya | — | — |
| Memicu n8n, melihat sinkronisasi, membuat laporan | Ya | Ya | — | — |
| Mengelola akun dan klasifikasi | Ya | — | — | — |

## Batasan yang perlu diketahui

- Workflow n8n dan aplikasi Android tidak termasuk repositori ini. Pengiriman berita lama tetap kompatibel; status eksekusi memerlukan callback tambahan sesuai [panduan n8n](docs/N8N.md).
- Saran artikel memakai kemiripan kata judul, rentang 7 hari, dan wilayah bila tersedia. Ini bukan penilaian kebenaran atau model AI semantik. Maksimal 250 kandidat terbaru diperiksa dan 10 saran ditampilkan.
- Satu artikel dapat ditautkan ke satu isu. Lepaskan tautan lama sebelum memindahkannya.
- Laporan merupakan snapshot pertama untuk suatu tanggal; membuka atau menjalankan ulang scheduler tidak menggantinya. Selesaikan verifikasi sebelum membuat laporan manual. Berita tanpa tanggal terbit tidak masuk laporan harian.
- Status isu pada laporan adalah keadaan saat laporan dibuat, termasuk isu terbuka lintas tanggal. Laporan tidak merekonstruksi status historis suatu isu.
- Notifikasi browser lama memerlukan tab dashboard terbuka. Notifikasi aplikasi tersimpan, sedangkan email dapat diterima saat tab ditutup.
- CSS Tailwind, Chart.js, dan font dilayani dari build lokal. Jalankan `npm ci && npm run build` pada pemasangan atau setelah perubahan frontend; folder `public/build` diperlukan pada server. Pengguna tidak perlu mengakses CDN untuk membuka dashboard.

## Pengujian dan catatan pengembangan

```powershell
php artisan test
composer audit
npm run build
npm audit
php artisan route:list
```

Tes memakai database terisolasi sesuai `phpunit.xml`. Jangan mengarahkan konfigurasi pengujian ke database operasional. Lihat [progres dan verifikasi](docs/PROGRESS.md) untuk cakupan dan [panduan n8n](docs/N8N.md) untuk contoh payload.
