# Panduan migrasi login, Ingat Saya, dan endpoint tombol

## 1. Backup

Sebelum mengganti project di XAMPP/hosting, backup folder project dan database terlebih dahulu.

## 2. Pasang project

1. Ekstrak `Perawat_Periwitt_FIXED.zip`.
2. Salin folder `Perawat_Periwitt` ke direktori web server (misalnya `htdocs`).
3. Pertahankan file konfigurasi lokal database dan Google OAuth milik instalasi kamu. Jika file lokal tidak ada, salin template `.example` yang tersedia lalu isi kredensial sendiri.

## 3. Jalankan migrasi database admin

1. Buka phpMyAdmin.
2. Pilih database yang digunakan project (default konfigurasi project: `eksplormajaku`).
3. Buka tab **SQL**.
4. Jalankan isi file `Backend/database/admin_sessions_migration.sql`.
5. Pastikan tabel `admin_sessions` berhasil dibuat.

Migrasi ini wajib sebelum login admin. Setelah migrasi, token JWT admin lama yang belum tercatat tidak lagi diterima; login admin kembali untuk membuat sesi baru.

## 4. Cara kerja setelah migrasi

- Ingat Saya user menyimpan email/nomor telepon terakhir di penyimpanan browser/perangkat. Password tidak disimpan.
- Ingat Saya admin menyimpan username terakhir. Token admin disimpan sesuai pilihan Ingat Saya (penyimpanan permanen browser atau sesi tab).
- Membuka kembali halaman login ketika sesi masih aktif mengakhiri sesi user atau mencabut token admin di server.
- Logout admin mencabut token di tabel `admin_sessions`, bukan hanya menghapus token di browser.
- Tombol menampilkan endpoint/metode API melalui tooltip saat diarahkan kursor atau difokuskan. Link navigasi tetap menampilkan URL tujuan melalui status bar browser.
- Tombol antarmuka seperti buka/tutup menu tidak memanggil API dan tooltip menyatakan hal tersebut.

## 5. Catatan isolasi perangkat

`localStorage` terpisah antar browser dan profil browser. Jika beberapa orang memakai profil Chrome yang sama pada satu komputer, penyimpanan Ingat Saya juga sama. Untuk memisahkan identitas antarorang pada komputer bersama, gunakan profil Chrome atau akun sistem operasi masing-masing.

## 6. Pemeriksaan

Sesudah migrasi:
1. Login sebagai admin dan buka dashboard.
2. Arahkan kursor ke tombol Logout; tooltip harus menampilkan `POST` ke `/api/admin/logout`.
3. Logout, lalu coba buka kembali dashboard menggunakan tombol Back; dashboard harus meminta login ulang.
4. Login sebagai user, lalu buka halaman login user; sesi sebelumnya harus berakhir.
5. Uji Ingat Saya dengan dua profil browser yang berbeda; identitas yang diingat tidak boleh saling terbawa.
