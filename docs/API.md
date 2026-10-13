# API mobile

Semua endpoint baca dan perubahan arsip membutuhkan akun aktif. Token hanya diperlihatkan sekali pada saat dibuat dan berlaku 7 hari.

## Login

`POST /api/auth/token` dengan JSON:

```json
{
  "email": "pengguna@example.com",
  "password": "kata-sandi-pengguna",
  "device_name": "Android pribadi"
}
```

Respons berisi `token`, `expires_in_days`, dan identitas ringkas pengguna. Untuk permintaan selanjutnya:

```http
Authorization: Bearer <token-yang-diterima>
Accept: application/json
```

`DELETE /api/auth/token` mencabut token yang sedang digunakan. Admin yang menonaktifkan akun atau mengganti kata sandi juga mencabut seluruh token akun tersebut. Tidak ada endpoint pendaftaran publik.

## Endpoint

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/news` | 20 artikel per halaman, `data` dan `meta` |
| GET | `/api/news/{id}` | Detail artikel dalam `data` |
| POST | `/api/news/{id}/toggle-archive` | Admin/analis/petugas; pimpinan mendapat 403 |
| GET | `/api/stats` | Statistik keseluruhan |
| GET | `/api/sources` | Statistik sumber |
| GET | `/api/trend` | Tren 14 hari |
| GET | `/api/weekly` | Perbandingan minggu ini dan minggu lalu |

Filter `/api/news`: `q`, `source`, `sentiment` (`positif/netral/negatif`), `urgent_only` (`0/1`), `archived` (`0/1`), `from`, `to` (`YYYY-MM-DD`), `category_id`, `region_id`, `agency_id`, `verification_status` (`pending/verified`), `sort` (`newest/oldest`), dan `page`.

Data artikel mencakup `verification_status`, nama `category`, `region`, `agency`, serta field lama. ID klasifikasi dapat dilihat/dikelola admin melalui aplikasi web.

Status umum: 401 belum login/token kedaluwarsa, 403 akun nonaktif/tidak berwenang, 404 data tidak ditemukan, 422 validasi gagal, 429 batas percobaan login. API ingest n8n memakai mekanisme token terpisah; lihat `N8N.md`.
