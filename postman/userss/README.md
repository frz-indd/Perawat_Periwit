# Pengujian Postman — API User & Publik (userss)

Folder ini berisi koleksi pengujian lengkap (Postman Collection & Environment) untuk endpoint User dan Publik **Explore Majaku (Perawat_Periwit)**.

## 📁 File yang Tersedia
1. `Perawat_Periwit_USER.postman_collection.json`  
   Koleksi Postman berisi **31 request pengujian** lengkap dengan assertion test script (`pm.test`), mencakup:
   - **Autentikasi Pengguna**: Registrasi akun baru, validasi format input, login pengguna, dan logout sesi.
   - **Profil Pengguna**: Proteksi unauthenticated (`401`), `GET /users/me`, `PUT /users/me`, dan ganti password pengguna.
   - **Kategori & Wisata**: `GET /categories`, `GET /categories/{id}/wisata`, validasi ID kategori tidak valid (`400`/`404`), dan daftar destinasi wisata.
   - **Pencarian & Lokasi**: `GET /api/destinations/search`, detail koordinat lokasi destinasi.
   - **Ulasan & Favorit**: Tambah ulasan destinasi, validasi rating ulasan (1-5), dan toggle destinasi favorit.
2. `Perawat_Periwit_LOCAL.postman_environment.json`  
   Variabel environment lokal (`baseUrl`, `userEmail`, `userPassword`, `destinationId`, `categoryId`).
3. `run_user_tests.php`  
   Script CLI runner untuk menjalankan pengujian secara otomatis tanpa membuka Postman UI.

## 🚀 Cara Menjalankan di Postman
1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di kiri atas.
3. Pilih file `Perawat_Periwit_USER.postman_collection.json` dan `Perawat_Periwit_LOCAL.postman_environment.json`.
4. Pilih environment **"Perawat_Periwit USER Localhost"** di dropdown kanan atas.
5. Jalankan collection menggunakan **Postman Collection Runner** (klik kanan koleksi > *Run Collection*).

## 💻 Cara Menjalankan via Terminal (CLI)
Jalankan runner PHP:
```powershell
c:\xampp\php\php.exe postman\userss\run_user_tests.php
```
Atau menggunakan Newman:
```powershell
npx newman run postman/userss/Perawat_Periwit_USER.postman_collection.json -e postman/userss/Perawat_Periwit_LOCAL.postman_environment.json
```
