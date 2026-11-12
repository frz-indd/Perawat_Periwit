# ADMIN-04-BE — Endpoints Aggregation & Summary Dashboard

## Endpoint

Semua endpoint di bawah menggunakan JWT admin pada header:
`Authorization: Bearer <admin_jwt_token>`.

### Ringkasan dashboard
`GET /api/admin/dashboard/summary`

Response 200:
```json
{
  "status": "success",
  "message": "Ringkasan dashboard berhasil diambil.",
  "data": {
    "total_wisata": 12,
    "total_kategori": 4,
    "total_pengguna": 25,
    "total_ulasan": 31
  }
}
```

Statistik dihitung dari tabel `wisata`, `kategori`, `user`, dan `ulasan`. Query menggunakan subquery `COUNT(*)` sehingga setiap tabel dihitung di database tanpa mengambil seluruh baris ke PHP.

### Daftar ulasan
`GET /api/admin/reviews`

### Detail ulasan
`GET /api/admin/reviews/{id}`

### Hapus ulasan
`DELETE /api/admin/reviews/{id}`

Setelah ulasan dihapus, `average_rating` pada wisata terkait dihitung ulang agar ringkasan rating tetap konsisten.

## Instalasi

1. Pastikan tabel `wisata`, `kategori`, `user`, dan `ulasan` tersedia.
2. Pastikan konfigurasi database di `Backend/database/database.local.php` atau environment `DB_*` sudah benar.
3. Pastikan rewrite Apache aktif (`mod_rewrite`) dan `.htaccess` di root project terbaca.
4. Login melalui `POST /api/admin/login`, lalu kirim JWT sebagai Bearer token.

Tidak ada migration tambahan untuk task ini.

## Kode status

- `200`: berhasil.
- `400`: ID ulasan tidak valid atau tidak disertakan.
- `403`: token admin tidak ada/tidak valid.
- `404`: ulasan tidak ditemukan.
- `405`: metode HTTP tidak didukung.
- `500`: kesalahan server/database.
