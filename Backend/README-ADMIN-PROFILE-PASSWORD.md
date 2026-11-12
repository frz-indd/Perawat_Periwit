# ADMIN-08-BE — Admin Profile & Password Hashing

Semua endpoint pada task ini dilindungi middleware `adminOnly()` dan wajib memakai JWT admin aktif.

Header umum:

```http
Authorization: Bearer <adminJwtToken>
Accept: application/json
Content-Type: application/json
```

## 1. GET `/api/admin/profile`

Mengambil profil admin berdasarkan ID pada JWT, bukan ID dari query/body.

Contoh response `200`:

```json
{
  "status": "success",
  "message": "Profil admin berhasil diambil.",
  "data": {
    "id": 1,
    "name": "Administrator",
    "username": "admin",
    "image_url": "https://example.com/admin.jpg"
  }
}
```

## 2. PUT `/api/admin/profile`

Body JSON:

```json
{
  "name": "Administrator Baru",
  "image_url": "https://example.com/admin-baru.jpg"
}
```

`image_url` boleh `null` atau string URL HTTP/HTTPS. Endpoint menyimpan URL saja; upload file gambar bukan bagian dari endpoint ini.

## 3. PUT `/api/admin/change-password`

Body JSON:

```json
{
  "current_password": "PasswordLama1!",
  "new_password": "PasswordBaru2@"
}
```

Validasi password baru:

- Minimal 8 karakter.
- Mengandung huruf besar, huruf kecil, angka, dan karakter khusus.
- Maksimal 72 byte agar tidak terpotong oleh bcrypt.
- Harus berbeda dari password saat ini.

Password lama diverifikasi menggunakan `password_verify()` (padanan PHP untuk bcrypt compare). Jika salah, endpoint mengembalikan HTTP `400` dengan response persis:

```json
{"message":"Kata sandi saat ini yang Anda masukkan salah."}
```

Password baru disimpan menggunakan `password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10])`. Hash tidak pernah dikirim dalam response.

## 4. Database migration

Jalankan satu kali file `Backend/database/admin_profile_migration.sql` pada database yang digunakan aplikasi. Migration menambahkan `Foto_admin VARCHAR(2048)` ke tabel `admin`. Tabel `admin` proyek saat ini memiliki `Id_admin`, `Nama_admin`, `Username`, dan `Password`; migration menambahkan kolom URL foto tanpa mengubah data login yang sudah ada.

## 5. Routing

Rewrite Apache ditambahkan di root `.htaccess`:

- `/api/admin/profile` → `Backend/api/admin_profile.php`
- `/api/admin/change-password` → `Backend/api/admin_change_password.php`

Jika menggunakan URL file langsung, endpoint juga tersedia melalui `/Backend/api/admin_profile.php` dan `/Backend/api/admin_change_password.php`.

## 6. Postman

Import atau refresh `Backend/api/postman/Perawat_Periwit.postman_collection.json`. Isi collection variable `adminJwtToken` dari hasil login admin. Folder `ADMIN-08 Admin Profile & Password` berisi GET profile, PUT profile, ganti password, dan skenario password lama salah.

## 7. Status utama

| HTTP | Kondisi |
|---|---|
| 200 | Profil berhasil dibaca/diubah atau password berhasil diganti |
| 400 | Password lama salah (pesan sesuai kontrak) atau JSON tidak valid |
| 403 | JWT tidak valid, bukan admin, atau sesi admin dicabut/kedaluwarsa |
| 404 | Admin tidak ditemukan |
| 405 | Method tidak diizinkan |
| 409 | Migration foto profil belum dijalankan |
| 415 | Content-Type bukan application/json |
| 422 | Field atau kriteria password tidak valid |
| 500 | Kesalahan server; detail teknis hanya masuk log |

## 8. Catatan deployment

Deploy kedua file PHP, `.htaccess`, OpenAPI, dan Postman Collection. Jalankan migration sebelum memakai PUT profile. Verifikasi melalui Postman dan staging; status deployment/performa staging tidak dapat diklaim sebelum akses staging tersedia.


**Keamanan sesi:** setelah password berhasil diganti, semua sesi JWT admin dicabut. Admin wajib login kembali menggunakan password baru.
