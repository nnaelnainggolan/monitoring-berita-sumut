# Progres implementasi

Branch kerja: `codex/monitoring-berita-workflow`, berdasarkan `main` pada commit `f855b9c`.

## Tahap yang selesai

1. **Fondasi editorial**: login dan token mobile, peran admin/analis/petugas/pimpinan, akun aktif/nonaktif, master kategori/wilayah/OPD, filter klasifikasi, verifikasi hasil AI dengan catatan audit dan deteksi perubahan bersamaan. Pengiriman ulang n8n mempertahankan hasil yang sudah diverifikasi.
2. **Tindak lanjut isu**: membuat isu dari berita, penanggung jawab, prioritas dan tenggat, urutan status yang tervalidasi, komentar, riwayat perubahan, notifikasi aplikasi/email, preferensi pemantauan, dan pengingat terlambat sekali sehari.
3. **Pengelompokan, pelaporan, dan monitoring**: saran kemiripan artikel dengan konfirmasi analis, taut/lepas artikel, snapshot laporan harian, unduhan PDF bersumber, scheduler, dan status eksekusi n8n berdasarkan callback.

## Verifikasi pada 12 Oktober 2026

Pengujian backend menggunakan PHP 8.5.11 dan SQLite terisolasi. Dependensi dikunci untuk minimum PHP 8.4.1. MySQL dan instalasi Laragon pengguna belum dijalankan di lingkungan ini.

| Pemeriksaan | Hasil |
|---|---|
| PHPUnit | 27 tes lulus, 162 assertion |
| Autentikasi dan otorisasi | Akses tamu, akun nonaktif, pembatasan login, pencabutan token, empat peran, dan perlindungan admin terakhir diuji |
| Ingest dan review | Token kosong ditolak, validasi batch atomik, upsert, payload tidak dapat menetapkan hak editorial, audit, konflik versi, dan perlindungan hasil verifikasi diuji |
| Tanggal dan filter | Filter tanggal akhir tanpa tanggal awal, input filter tidak valid, dan konversi UTC ke tanggal WIB diuji |
| Isu dan notifikasi | Transisi status, petugas yang ditugaskan, pembatasan akses, preferensi wilayah/prioritas, pengingat berulang, dan pengiriman setelah commit diuji |
| Pengelompokan dan laporan | Saran tidak menautkan otomatis, pelepasan mempertahankan artikel, snapshot tetap, hanya berita terverifikasi menjadi sorotan, dan respons PDF diuji |
| Monitoring n8n | HTTP 2xx belum berarti selesai, callback awal tidak tertimpa, callback terminal idempoten, token salah ditolak, dan proses tanpa kabar menjadi belum terkonfirmasi |
| Upgrade database lama | Migration dijalankan pada salinan SQLite repositori; 95 artikel dan seluruh kolom asli tetap sama, seluruh artikel mendapat status awal `pending` |
| Migration instalasi baru dan rollback | Berhasil pada SQLite; rollback dijalankan dalam pengujian notifikasi |
| Route dan Blade | 46 route aplikasi terdaftar; seluruh template berhasil dikompilasi |
| Composer | Validasi manifest/lock lulus; audit dependensi terkunci tidak menemukan advisory pada saat pemeriksaan |
| PDF visual | PDF contoh dirender menjadi gambar dan diperiksa: ringkasan, sumber berita, tabel isu, dan nomor halaman terbaca |
| Browser desktop dan mobile | Login, verifikasi berita, penugasan, penautan artikel, laporan/PDF, halaman admin, notifikasi, serta petugas menyelesaikan isu berhasil diuji pada Chromium dengan database contoh |
| Aset frontend | CSS, Chart.js, dan font dibangun lokal dengan Vite; pengujian browser memblokir permintaan ke situs luar dan memeriksa tidak ada error JavaScript |
| Audit npm | Tidak ada kerentanan yang dilaporkan setelah pembaruan dependensi build |

Tampilan dashboard desktop serta halaman isu mobile diperiksa melalui screenshot. Halaman laporan dan isu diuji pada lebar 390 piksel tanpa overflow horizontal. Build produksi frontend berhasil.

## Penyegaran dashboard — 13 Oktober 2026

Navigasi dipindahkan ke sidebar dengan menu drawer pada ponsel. Palet marun, biru gelap, dan latar netral diterapkan bersama ukuran teks, ikon, jarak, serta kartu yang konsisten. Banner dipadatkan; ringkasan statistik dilengkapi persentase; grafik memakai tinggi tetap agar mudah dibandingkan. Filter lanjutan dapat dibuka sesuai kebutuhan, tabel berubah menjadi kartu pada ponsel, dan pagination berbahasa Indonesia mempertahankan filter aktif.

Fungsi filter AJAX, tombol kembali/maju browser, arsip, ekspor CSV, dan pemulihan tombol pencarian ketika server menolak permintaan diuji di Chromium. Menu ponsel diuji dengan keyboard, Escape, klik backdrop, navigasi, dan pembatasan peran. Sorotan berita bisa dijeda dan menghormati preferensi pengurangan animasi.

- PHPUnit: 27 tes, 162 assertion lulus; build Vite produksi berhasil.
- Tampilan diperiksa pada lebar 320, 390, 768, 1024, 1366, dan 1440 piksel tanpa overflow halaman. Form lanjutan juga diperiksa pada layar sempit.
- Grafik, halaman bersama, dan aset lokal berjalan tanpa error JavaScript dalam pengujian browser dengan data contoh terisolasi.
- Pembaruan ini tidak menambahkan migration atau dependensi. Untuk instalasi yang sudah mengikuti branch kerja: `git pull --ff-only`, `npm run build`, lalu `php artisan optimize:clear`; muat ulang browser dengan Ctrl+F5.

## Yang perlu dihubungkan saat pemasangan

- Sesuaikan `.env`, jalankan migration, lalu buat admin melalui perintah interaktif. Ikuti README untuk instalasi atau upgrade yang mempertahankan database lama.
- Atur token terpisah untuk ingest dan pemicu n8n. Tambahkan callback status dan `X-Run-ID` sesuai [N8N.md](N8N.md). Tes otomatis menggunakan HTTP palsu; workflow n8n yang sebenarnya belum diuji.
- Jalankan queue worker dan scheduler. Konfigurasikan SMTP bila memakai email; pengiriman ke alamat nyata belum diuji.
- Sesuaikan klien Android agar login dan mengirim Bearer token sesuai [API.md](API.md).
- Daftarkan wilayah dan OPD sesuai kebutuhan; seeder hanya mengisi kategori awal.

Perubahan ini disiapkan untuk tinjauan di branch terpisah. Pemasangan pada server operasional dilakukan setelah konfigurasi integrasi selesai.
