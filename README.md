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
  "link": "={{ $json.link }}",
  "source": "={{ $json.source }}",
  "published_at": "={{ $json.publishedAt }}",
  "sentiment": "={{ $json.sentiment }}",
  "is_urgent": "={{ $json.isUrgent }}",
  "analysis": "={{ $json.reason }}"
}
```

Endpoint ini menerima **satu objek** atau **array berisi banyak objek** sekaligus (kalau kamu ingin kirim semua berita dalam satu batch, bukan satu per satu).

## 3. Struktur Data

Tabel `news_items`:
- `title`, `link` (unik, dipakai untuk upsert — berita yang sama akan ter-update, bukan dobel)
- `source` — RSS Pemprov Sumut / RSS Gubernur Sumut
- `sentiment` — positif / netral / negatif
- `is_urgent` — hasil node Filter Negatif/Mendesak
- `analysis` — ringkasan/alasan dari model AI
- `archived` — ditandai jika sudah lewat node Arsipkan Berita

## 4. Pengembangan Lanjutan
- Tambah autentikasi login untuk dashboard (Laravel Breeze) jika perlu multi-user.
- Tambah grafik tren sentimen (Chart.js) di dashboard.
- Endpoint bisa juga dipakai dua arah: Laravel bisa expose API untuk n8n *mengambil* data lama, bukan cuma menerima data baru.
