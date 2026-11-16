# Perbaikan Perawat_Periwitt / Explore MajaKu

## Perbaikan dalam paket ini

1. **Dashboard admin blank putih**
   - Objek JavaScript `config` sekarang diisi dari `$pageConfig` sebelum pemeriksaan token dan pemanggilan API.
   - URL login, verifikasi dashboard, dan ringkasan statistik tetap mengikuti base path instalasi aplikasi.
2. **Ingat Saya untuk login pengguna**
   - Saat checkbox Ingat Saya aktif dan login berhasil, email/nomor telepon disimpan pada `localStorage` browser.
   - Saat checkbox dinonaktifkan, identitas tersimpan dihapus. Password tidak pernah disimpan.
   - Data terpisah per browser profile/perangkat dan tidak ditulis ke database. Jika beberapa orang memakai profil browser yang sama, mereka tetap dapat melihat identitas terakhir; gunakan profil browser/OS terpisah untuk privasi antarorang.
3. **Ingat Saya untuk login admin**
   - Username admin terakhir juga diingat secara lokal jika checkbox aktif. Token login tetap menggunakan mekanisme penyimpanan yang sudah ada.
4. **Tooltip endpoint API pada tombol**
   - Tooltip endpoint API ditampilkan saat tombol diarahkan dengan mouse atau mendapat fokus keyboard. Tooltip menampilkan metode HTTP dan URL endpoint jika tersedia.
5. **Perbaikan sintaks PHP**
   - Menghapus blok hasil duplikasi yang menyebabkan `Backend/api/admin_destinations.php` gagal diparse oleh PHP.

## Database dan migrasi

Paket ini mengikuti database `eksplormajaku` yang diberikan. Untuk tiga perbaikan utama di atas **tidak diperlukan perubahan struktur database**, sehingga tidak ada SQL ALTER/CREATE yang perlu dijalankan. Data admin, user, wisata, ulasan, favorit, kategori, dan OAuth tetap dipertahankan.

### Langkah pemasangan XAMPP

1. Cadangkan folder proyek lama dan ekspor database dari phpMyAdmin.
2. Ekstrak folder `Perawat_Periwitt` dari ZIP ke `C:\xampp\htdocs\`.
3. Pastikan database bernama `eksplormajaku` sudah diimpor dari dump SQL milik Anda. Jangan mengimpor ulang jika database aktif sudah berisi data terbaru.
4. Buka `Backend/database/database.local.example.php` sebagai acuan. Jika konfigurasi default XAMPP tidak cocok, buat file `Backend/database/database.local.php` dengan isi berikut, lalu sesuaikan kredensial lokal:

```php
<?php
return [
    "host" => "localhost",
    "port" => 3306,
    "database" => "eksplormajaku",
    "user" => "root",
    "password" => "",
];
```

5. Buka `http://localhost/Perawat_Periwitt/`.
6. Login admin melalui `FrontEnd/auth/login-admin.php`, lalu buka dashboard.

### Pemeriksaan setelah pemasangan

- Pastikan Apache dan MySQL aktif.
- Pastikan `mod_rewrite` Apache aktif karena endpoint `/api/admin/...` menggunakan aturan `.htaccess` di root proyek.
- Jika dashboard masih gagal memverifikasi admin, periksa Console dan Network browser, lalu cek log PHP/Apache. Pastikan `JWT_SECRET` tetap sama untuk proses login dan verifikasi.
- Jika aplikasi dijalankan pada subfolder dengan nama berbeda, gunakan URL subfolder tersebut saat membuka aplikasi.

## Validasi paket

- Seluruh 47 file PHP yang terdeteksi telah diperiksa menggunakan `php -l`.
- File JavaScript login dan tooltip API telah diperiksa sintaksnya menggunakan `node --check`.
- Pengujian runtime terhadap instalasi XAMPP/database Anda belum dapat dilakukan dari lingkungan ini; lakukan langkah pemeriksaan di atas setelah menyalin ZIP.

## Migrasi sesi admin dan pembatalan token

Jalankan file `Backend/database/admin_sessions_migration.sql` satu kali pada database yang dipakai aplikasi (phpMyAdmin: pilih database → tab SQL → tempel/jalankan isi file). Login admin kini menyimpan hash JWT di tabel `admin_sessions`; middleware hanya menerima token yang masih aktif. Endpoint `POST /api/admin/logout` mencabut token di server. Jika tabel migrasi belum dibuat, login admin akan gagal sampai migrasi diterapkan.

## Perilaku Ingat Saya dan halaman login

- Identitas user/admin yang diingat disimpan di `localStorage` browser/perangkat, bukan di database dan bukan password.
- Menghapus centang Ingat Saya menghapus identitas yang tersimpan.
- Membuka halaman login user saat sesi user aktif akan mengakhiri sesi PHP tersebut.
- Membuka halaman login admin saat token masih tersimpan akan meminta server mencabut token, lalu menghapus token dari penyimpanan browser.
- Tombol yang memanggil endpoint menampilkan metode dan URL API saat diarahkan/disorot. Tombol antarmuka yang tidak mengirim request diberi keterangan bahwa tombol tersebut tidak memanggil API. Link navigasi tetap memakai URL tujuan asli sehingga browser menampilkannya di status bar.

Catatan: data Ingat Saya terisolasi per profil browser/perangkat. Jika beberapa orang memakai profil browser yang sama, mereka berbagi penyimpanan browser; gunakan profil browser/akun OS terpisah untuk isolasi antarorang pada satu komputer.
