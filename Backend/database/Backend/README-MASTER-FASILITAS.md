# CRUD Master Fasilitas Wisata

## 1. Jalankan migration

Pilih database project yang digunakan, lalu jalankan file `Backend/database/facilities_migration.sql` satu kali melalui phpMyAdmin atau MySQL client.

Migration membuat tabel `facilities` dengan kolom `id`, `name`, `icon_code`, `description`, dan `created_at`. Tabel lama `fasilitas` dan relasi `wisata_fasilitas` tidak diubah.

## 2. Endpoint

Semua endpoint membutuhkan header `Authorization: Bearer <token-admin-jwt>`. Token diperoleh dari `POST /api/admin/login` dengan JSON `username` dan `password`.

| Method | URL | Fungsi |
| --- | --- | --- |
| GET | `/api/admin/facilities` | Daftar fasilitas |
| GET | `/api/admin/facilities/{id}` | Detail fasilitas |
| POST | `/api/admin/facilities` | Tambah fasilitas |
| PUT | `/api/admin/facilities/{id}` | Ubah fasilitas |
| DELETE | `/api/admin/facilities/{id}` | Hapus fasilitas |

Contoh body POST:

```json
{
  "name": "Toilet",
  "icon_code": "fa-restroom",
  "description": "Fasilitas toilet umum"
}
```

PUT menerima satu atau lebih field dari `name`, `icon_code`, dan `description`.

Nama fasilitas unik (case-insensitive mengikuti collation database). Jika duplikat, API mengembalikan HTTP 422:

```json
{
  "message": "Nama fasilitas sudah ada di sistem."
}
```

## 3. Uji melalui Postman

Import `Backend/api/postman/Perawat_Periwit.postman_collection.json`. Atur `baseUrl`, `adminUsername`, dan `adminPassword` pada collection variables. Jalankan request `POST login admin JWT (untuk CRUD fasilitas)` terlebih dahulu; token disimpan otomatis ke `adminJwtToken`. Setelah itu jalankan request CRUD fasilitas.

Request duplikasi menguji respons 422. Jalankan request tambah fasilitas lebih dahulu agar data `facilityId` tersimpan untuk request detail, ubah, dan hapus.

## 4. Catatan URL

Rewrite URL `/api/admin/facilities` dan `/api/admin/facilities/{id}` sudah ditambahkan pada `.htaccess` root project. Pastikan Apache `mod_rewrite` aktif dan `AllowOverride All` diaktifkan untuk direktori project.
