# Admin User Management API

## Endpoint

`GET /api/admin/users`

Endpoint hanya dapat dipanggil oleh admin yang memiliki JWT aktif. Kirim token melalui header `Authorization: Bearer <token>`.

### Query parameters

| Parameter | Wajib | Default | Keterangan |
|---|---|---:|---|
| `search` | Tidak | kosong | Mencari nama atau email (maksimal 100 karakter). |
| `page` | Tidak | `1` | Nomor halaman, minimal 1. |
| `limit` | Tidak | `10` | Jumlah data per halaman, maksimal 100. |

Contoh: `GET /api/admin/users?search=faris&page=1&limit=10`

### Contoh response 200

```json
{
  "status": "success",
  "message": "Daftar pengguna berhasil diambil.",
  "data": [
    {
      "id": 12,
      "nama": "Faris Rachman",
      "email": "faris@example.com",
      "no_telepon": "081234567890",
      "role": "user",
      "created_at": "12 Apr 2025"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_data": 25,
    "total_pages": 3,
    "has_previous_page": false,
    "has_next_page": true
  }
}
```

`created_at` bernilai `null` untuk akun lama yang dibuat sebelum migration dan tidak memiliki riwayat tanggal pendaftaran. Jalankan `database/admin_users_migration.sql` satu kali agar akun baru otomatis mencatat waktu pendaftaran. Kolom password tidak pernah disertakan dalam response.

### Status code

- `200`: data berhasil diambil.
- `403`: token tidak ada, tidak valid, bukan admin, atau sesi admin telah dicabut/kedaluwarsa.
- `405`: metode selain GET.
- `503`: database tidak tersedia.
- `500`: kesalahan server atau struktur tabel tidak sesuai.

### Persiapan database

Jalankan migration satu kali dari folder `Backend/database`: `admin_users_migration.sql`. Migration menambahkan `created_at` tanpa mengarang tanggal untuk akun lama; akun yang dibuat setelah migration akan otomatis mendapat timestamp.

### Kompatibilitas database

Aplikasi saat ini mendaftarkan pengguna ke tabel `user` dengan kolom `Id_user`, `Nama_user`, `Email`, dan `No_telepon`. Endpoint menggunakan tabel tersebut terlebih dahulu dan menganggap seluruh isinya sebagai akun pengguna (karena skema lama tidak memiliki kolom `role`). Jika tabel `user` tidak ada, endpoint menggunakan tabel `users` dan mewajibkan filter `role = 'user'`. Kolom `created_at` akan diformat menjadi `d M Y` dengan nama bulan Indonesia jika tersedia.

### Pengujian Postman

Koleksi `Backend/api/postman/Perawat_Periwit.postman_collection.json` sudah ditambahkan request **GET /api/admin/users - search & pagination**. Jalankan request login admin terlebih dahulu agar variabel `adminJwtToken` terisi. Variabel `userSearch`, `userPage`, dan `userLimit` dapat disesuaikan.
