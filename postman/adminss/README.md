# Pengujian Postman — API Admin (adminss)

Folder ini berisi koleksi pengujian lengkap (Postman Collection & Environment) untuk semua endpoint API Admin **Explore Majaku (Perawat_Periwit)**.

## 📁 File yang Tersedia
1. `Perawat_Periwit_ADMIN.postman_collection.json`  
   Koleksi Postman berisi **62 request pengujian** lengkap dengan assertion test script (`pm.test`), mencakup:
   - **Autentikasi & Sesi Admin**: Login JWT, Logout, Proteksi Middleware RBAC `adminOnly()`.
   - **Lupa Password Admin**: Validasi format email, verifikasi role admin (`role = 1`), penolakan user biasa (`403`), hashing password baru, dan penanganan email tidak ditemukan (`404`).
   - **Dashboard & Ringkasan**: Endpoint statistik `GET /api/admin/dashboard/summary`.
   - **Master Fasilitas (CRUD)**: `GET`, `GET /:id`, `POST` (tambah), `PUT` (edit), `DELETE` (hapus), dan validasi duplikasi (`422`).
   - **Moderasi Ulasan**: `GET /api/admin/reviews`, detail ulasan, dan `DELETE` ulasan beserta recalculate rating destinasi.
   - **Pengelolaan Pengguna**: `GET /api/admin/users` (filter role = 0, search, pagination).
   - **Profil & Password Admin**: `GET /api/admin/profile`, `PUT /api/admin/profile`, dan `PUT /api/admin/change-password`.
   - **Kategori & Destinasi**: CRUD kategori dan CRUD destinasi wisata.
2. `Perawat_Periwit_LOCAL.postman_environment.json`  
   Variabel environment lokal (`baseUrl`, `adminUsername`, `adminPassword`, `adminEmail`, `adminJwtToken`, dll.).
3. `run_admin_tests.php`  
   Script CLI runner untuk menjalankan pengujian secara otomatis tanpa membuka Postman UI.

## 🚀 Cara Menjalankan di Postman
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di kiri atas.
3. Pilih file `Perawat_Periwit_ADMIN.postman_collection.json` dan `Perawat_Periwit_LOCAL.postman_environment.json`.
4. Pilih environment **"Perawat_Periwit ADMIN Localhost"** di dropdown kanan atas.
5. Jalankan collection menggunakan **Postman Collection Runner** (klik kanan koleksi > *Run Collection*).

## 💻 Cara Menjalankan via Terminal (CLI)
Jalankan runner PHP:
```powershell
c:\xampp\php\php.exe postman\adminss\run_admin_tests.php
```
Atau menggunakan Newman:
```powershell
npx newman run postman/adminss/Perawat_Periwit_ADMIN.postman_collection.json -e postman/adminss/Perawat_Periwit_LOCAL.postman_environment.json
```
