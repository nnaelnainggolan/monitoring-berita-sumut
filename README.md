# Dashboard Monitoring Berita Pemprov Sumut (Laravel)

File-file ini adalah **kode siap pakai** untuk ditempel ke project Laravel baru — bukan project Laravel utuh (karena environment ini tidak punya akses ke Packagist/Composer untuk generate frameworknya).

## 1. Cara Pasang

```bash
composer create-project laravel/laravel monitoring-berita-sumut
cd monitoring-berita-sumut
```

Salin folder/file dari paket ini ke lokasi yang sama persis di project barumu:

```
database/migrations/2026_08_11_000000_create_news_items_table.php
app/Models/NewsItem.php
app/Http/Controllers/Api/NewsIngestController.php
app/Http/Controllers/DashboardController.php
routes/api.php         -> gabungkan isinya ke routes/api.php bawaan Laravel
routes/web.php          -> gabungkan isinya ke routes/web.php bawaan Laravel
resources/views/layouts/app.blade.php
resources/views/dashboard/index.blade.php
```

Atur `.env`:

```env
DB_DATABASE=monitoring_berita_sumut
DB_USERNAME=...
DB_PASSWORD=...

NEWS_INGEST_TOKEN=ganti-dengan-token-rahasia
```

Tambahkan di `config/services.php`:

```php
'n8n' => [
    'ingest_token' => env('NEWS_INGEST_TOKEN'),
],
```

Lalu jalankan:

```bash
php artisan migrate
php artisan serve
```

Dashboard bisa diakses di `http://localhost:8000`.

## 2. Menghubungkan n8n ke Laravel

Tambahkan **node HTTP Request** baru setelah node **"Gabung Analisis"** di workflow n8n kamu (jalan paralel dengan node Arsipkan Berita / Filter Negatif-Mendesak yang sudah ada).

Konfigurasi node:

| Setting | Nilai |
|---|---|
| Method | `POST` |
| URL | `https://domain-kamu.com/api/n8n/news` |
| Body Content Type | JSON |
| Header | `X-API-Key: <isi sama dengan NEWS_INGEST_TOKEN di .env>` |

Body JSON (mapping dari field hasil "Analisis Berita AI", sesuaikan nama field dengan output aktualmu):

```json
{
  "title": "={{ $json.title }}",
  "summary": "={{ $json.summary }}",
  "link": "={{ $json.link }}",
  "source": "={{ $json.source }}",
  "published_at": "={{ $json.publishedAt }}",
  "sentiment": "={{ $json.sentiment }}",
  "is_urgent": "={{ $json.isUrgent }}",
  "analysis": "={{ $json.reason }}"
}
```

`summary` diisi dari ringkasan singkat berita (2-3 kalimat) — kalau model AI-mu belum menghasilkan field ini, tambahkan instruksi di prompt node "Analisis Berita AI", misalnya: *"Buat juga ringkasan singkat berita dalam field `summary`, maksimal 3 kalimat."*

Endpoint ini menerima **satu objek** atau **array berisi banyak objek** sekaligus (kalau kamu ingin kirim semua berita dalam satu batch, bukan satu per satu).

### Retry otomatis kalau pengiriman gagal

Supaya data tidak hilang saat Laravel/koneksi sedang down, aktifkan retry bawaan n8n di node **"Kirim ke Endpoint (HTTP)"**:

1. Buka node tersebut, pindah ke tab **Settings** (di sebelah tab Parameters).
2. Aktifkan **"Retry On Fail"**.
3. Atur **Max Tries** (contoh: 3) dan **Wait Between Tries** (contoh: 5000 ms).

Dengan ini, kalau request gagal (misalnya Laravel sedang restart atau ngrok terputus), n8n akan otomatis mencoba ulang beberapa kali sebelum benar-benar dianggap gagal.

## 3. Struktur Data

Tabel `news_items`:
- `title`, `link` (unik, dipakai untuk upsert — berita yang sama akan ter-update, bukan dobel)
- `summary` — ringkasan singkat berita untuk tampilan cepat & halaman detail
- `source` — RSS Pemprov Sumut / RSS Gubernur Sumut
- `sentiment` — positif / netral / negatif
- `is_urgent` — hasil node Filter Negatif/Mendesak
- `analysis` — ringkasan/alasan dari model AI
- `archived` — ditandai jika sudah lewat node Arsipkan Berita

## 4. Fitur Tambahan
- **Halaman detail berita** — klik judul berita di tabel untuk membuka `/berita/{id}`, menampilkan ringkasan, analisis AI lengkap, dan tombol ke sumber asli.
- **Notifikasi browser** — tombol "Aktifkan notifikasi berita mendesak" di dashboard. Setelah diizinkan, browser akan menampilkan notifikasi setiap 30 detik jika ada berita mendesak baru (polling ke `/check-urgent`).
- **Perbandingan mingguan** — grafik batang membandingkan jumlah berita minggu ini vs minggu lalu, per hari.
- **Statistik per sumber** — tabel ringkas jumlah berita positif/netral/negatif per sumber RSS, diurutkan dari yang paling banyak berita negatifnya.

## 5. Pengembangan Lanjutan
- Tambah autentikasi login untuk dashboard (Laravel Breeze) jika perlu multi-user.
- Endpoint bisa juga dipakai dua arah: Laravel bisa expose API untuk n8n *mengambil* data lama, bukan cuma menerima data baru.
- Notifikasi browser hanya berfungsi selagi tab dashboard terbuka; untuk notifikasi walau tab tertutup, perlu Web Push (Service Worker) yang lebih kompleks.
