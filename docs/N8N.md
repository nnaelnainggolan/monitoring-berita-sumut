# Integrasi n8n

Kode Laravel tidak mengubah workflow n8n yang sedang aktif. Panduan ini menjelaskan penyesuaian node HTTP Request pada workflow yang sudah dimiliki. Gunakan URL aplikasi yang dapat dijangkau n8n dan simpan token dalam credential n8n, bukan pada ekspor workflow yang dibagikan.

## 1. Kirim berita (kompatibel dengan workflow lama)

```http
POST https://domain-aplikasi.example/api/n8n/news
X-API-Key: <NEWS_INGEST_TOKEN>
Content-Type: application/json
Accept: application/json
```

Satu objek atau array maksimal 500 objek:

```json
{
  "title": "Contoh berita pelayanan publik",
  "link": "https://media.example/berita/contoh",
  "source": "Media contoh",
  "published_at": "2026-10-12T08:00:00+07:00",
  "summary": "Ringkasan hasil analisis berita.",
  "sentiment": "netral",
  "is_urgent": false,
  "analysis": "Alasan klasifikasi dari hasil analisis.",
  "category": "Pelayanan Publik",
  "region": "Medan",
  "agency": "Nama OPD sesuai master"
}
```

`title` dan `link` maksimal 255 karakter mengikuti skema awal. `link` wajib HTTP/HTTPS. `sentiment` wajib `positif`, `netral`, atau `negatif`. Boolean harus berupa `true/false` atau `0/1`, bukan teks “Ya/Tidak”. `summary` maksimal 10.000, `analysis` 20.000, dan nama klasifikasi 150 karakter. `source` maksimal 255 karakter. Ringkasan/tanggal/sumber/analisis/klasifikasi boleh tidak diisi.

Nama kategori/wilayah/OPD dicocokkan dengan master yang aktif. Nama tidak dikenal tidak otomatis membuat master baru. Bila tidak dikirim, klasifikasi lama tidak dihapus.

Kirim `published_at` dalam ISO 8601 dengan zona waktu (`Z` atau `+07:00`). Laravel mengonversinya ke `APP_TIMEZONE` sebelum penyimpanan agar pengelompokan laporan mengikuti tanggal WIB; payload AI asli tetap disimpan. Tanggal tanpa zona dianggap menggunakan zona aplikasi. Timestamp lama tidak dikonversi ulang oleh migration.

Seluruh batch divalidasi sebelum penyimpanan dan diproses dalam satu transaksi. Tautan unik menjadi identitas upsert. Respons sukses `201 {"message":"ok","saved":1}`; `saved` menghitung kiriman yang diproses, termasuk pembaruan.

- Kiriman ulang tidak membatalkan keputusan arsip operator.
- Berita `verified` mempertahankan hasil pemeriksaan analis; kiriman terbaru tersedia dalam `ai_payload` sebagai pembanding.
- `verification_status`, `verified_by`, dan `issue_id` tidak dapat diatur oleh payload ingest.
- Aktifkan Retry On Fail untuk gangguan jaringan/server. Untuk 401, 409, dan 422, perbaiki token/status/data terlebih dahulu.

## 2. Pemicu dari tombol dashboard

Laravel mengirim `POST` ke `N8N_TRIGGER_WEBHOOK_URL` dengan header `X-Trigger-Key: <N8N_TRIGGER_TOKEN>` dan JSON:

```json
{
  "run_id": "uuid-eksekusi-dari-laravel",
  "callback_url": "https://domain-aplikasi.example/api/n8n/runs/uuid-eksekusi-dari-laravel"
}
```

Node Webhook n8n perlu memvalidasi `X-Trigger-Key` dan merespons segera, tanpa menunggu pengumpulan/analisis selesai. Batas waktu koneksi Laravel adalah 10 detik. Simpan `run_id` pada alur workflow agar tetap tersedia pada setiap batch dan cabang error. Jangan mencampurkan item dengan `run_id` berbeda.

HTTP 2xx hanya membuat status **Webhook diterima**, bukan **Selesai**. Jika respons koneksi tidak jelas, status menjadi **Belum ada konfirmasi**; periksa riwayat n8n sebelum memicu ulang.

## 3. Callback status

Pada awal proses, panggil:

```http
PATCH https://domain-aplikasi.example/api/n8n/runs/<run_id>
X-API-Key: <NEWS_INGEST_TOKEN>
Content-Type: application/json
```

```json
{"status":"running","execution_id":"id-eksekusi-n8n"}
```

Pada setiap pengiriman artikel, tambahkan header:

```http
X-Run-ID: <run_id>
```

Header tersebut menghubungkan batch dengan riwayat eksekusi. `received_count` bertambah sebanyak kiriman yang berhasil diproses, termasuk upsert/retry. Ini bukan jumlah artikel baru unik.

Setelah **semua** batch selesai diterima Laravel, kirim callback:

```json
{"status":"succeeded","execution_id":"id-eksekusi-n8n","message":"Semua batch berita selesai dikirim."}
```

Jika gagal, kirim `status: "failed"` dengan pesan singkat yang sudah dibersihkan dari token, kata sandi, dan payload sensitif. Gunakan Error Workflow/cabang penanganan error n8n untuk callback kegagalan. Jika proses berlangsung lama tanpa batch, kirim kembali `running` sebagai heartbeat sebelum 15 menit.

Callback terminal dengan status sama boleh diulang. Mengubah eksekusi yang sudah `succeeded` menjadi `failed/running` (atau sebaliknya) mendapat 409. Jangan mengirim batch tambahan setelah callback terminal; buat eksekusi baru jika perlu mengulangi keseluruhan proses.

Tanpa callback selama 15 menit, scheduler Laravel menandai proses **Belum ada konfirmasi**, tanpa menganggap pengumpulan berhasil atau gagal. Callback yang terlambat masih dapat menyelesaikan proses yang belum terminal.

## 4. Pengumpulan dari jadwal n8n

Untuk workflow yang dimulai oleh Schedule Trigger, buat catatan terlebih dahulu:

```http
POST https://domain-aplikasi.example/api/n8n/runs
X-API-Key: <NEWS_INGEST_TOKEN>
Content-Type: application/json
```

```json
{"execution_id":"id-eksekusi-n8n"}
```

Respons 201 menyediakan `run_id` dan `callback_url`. Lanjutkan dengan pengiriman batch berheader `X-Run-ID` dan callback pada langkah 3. Simpan ID yang diterima, sehingga node berikutnya tidak membuat catatan baru untuk setiap artikel.

## 5. Memeriksa integrasi

1. Kosongkan token pada lingkungan uji: endpoint ingest harus memberi 401.
2. Dengan token benar, kirim satu artikel uji: respons 201 dan artikel muncul sebagai perlu diperiksa.
3. Verifikasi artikel melalui dashboard, kirim ulang dengan ringkasan berbeda: hasil yang sudah diverifikasi tetap dipertahankan.
4. Picu n8n melalui dashboard, lalu periksa menu Sinkronisasi: status bergerak dari diterima/berjalan hingga selesai setelah callback.
5. Simulasikan kegagalan pada workflow uji: pastikan callback gagal atau status belum terkonfirmasi tercatat.

Jangan menyalin token ke screenshot, commit, atau laporan. Pengujian otomatis repositori memakai HTTP palsu (`Http::fake`); koneksi ke workflow n8n milik pengguna tetap perlu diuji setelah konfigurasi dilakukan.
