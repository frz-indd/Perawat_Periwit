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

## 5. Integrasi halaman Admin

Halaman `FrontEnd/admin/admin-resource.php?section=fasilitas` terhubung ke endpoint di atas. Halaman menggunakan token `admin_token` yang disimpan saat login admin dan mengirimkannya melalui header `Authorization: Bearer ...`.

Fitur yang tersedia di halaman admin:
- Memuat daftar fasilitas (`GET`).
- Melihat data detail saat memilih edit (`GET /{id}`).
- Menambah fasilitas (`POST`).
- Mengubah fasilitas (`PUT /{id}`).
- Menghapus fasilitas (`DELETE /{id}`).

Jika daftar fasilitas tidak dapat dimuat dan API melaporkan tabel tidak tersedia, jalankan migration pada langkah 1. Pastikan rewrite `/api/admin/facilities` aktif melalui `.htaccess` root, Apache `mod_rewrite` aktif, dan konfigurasi database mengarah ke database project yang benar.

## 6. Troubleshooting HTTP 500 / tabel tidak ditemukan

1. Buka phpMyAdmin dan klik database project yang dipakai oleh `Backend/database/koneksi.php` (default: `eksplormajaku`).
2. Pastikan nama database yang aktif sama dengan nilai `DB_NAME` atau `database` pada `Backend/database/database.local.php`.
3. Dalam database tersebut, buka tab **Import**, pilih file `Backend/database/facilities_migration.sql`, lalu klik **Go**.
4. Setelah berhasil, buka tab **Structure** dan pastikan tabel `facilities` muncul dengan kolom `id`, `name`, `icon_code`, `description`, dan `created_at`.
5. Login ulang sebagai admin lalu muat ulang halaman Fasilitas.

File migration ini tidak memilih database secara otomatis. Database harus dipilih terlebih dahulu di phpMyAdmin agar tabel dibuat pada database project yang benar. Endpoint akan menampilkan pesan khusus jika tabel atau kolom migration belum tersedia.
