# Security hardening – Explore MajaKu

Paket ini menerapkan hardening source code, tetapi tetap perlu konfigurasi server dan pengujian di staging.

## Wajib sebelum deploy

1. Atur environment variable `JWT_SECRET` pada konfigurasi PHP-FPM/Apache hosting. Gunakan nilai acak minimal 32 byte, idealnya 64 byte. Contoh membuat secret di terminal: `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Jangan commit secret ke Git atau membagikannya di chat.
2. Jalankan `Backend/database/admin_login_security_migration.sql` satu kali. Pastikan `Backend/database/admin_sessions_migration.sql` juga sudah dijalankan.
3. Pastikan Apache mengizinkan `.htaccess` (`AllowOverride All`) dan modul `mod_rewrite` aktif. Aturan penolakan akses file sensitif ada di root, `Backend/.htaccess`, dan `Backend/database/.htaccess`.
4. Pastikan folder upload `FrontEnd/assets/images/wisata` tidak mengeksekusi PHP/script. `.htaccess` sudah ditambahkan; untuk Nginx, buat aturan `location` ekuivalen karena Nginx tidak membaca `.htaccess`.
5. Gunakan HTTPS penuh. Jangan expose phpMyAdmin, file backup, file konfigurasi lokal, atau dump SQL ke publik. Hapus dump database dari document root bila tidak diperlukan untuk instalasi.

## Perubahan yang diterapkan

- JWT fail-closed: tidak ada lagi fallback secret default; secret hilang/kurang dari 32 byte membuat autentikasi gagal aman. Token tanpa `exp` atau dengan `exp` tidak valid ditolak.
- Login admin: respons kegagalan diseragamkan; rate limit 5 kegagalan per kombinasi IP + username dalam 15 menit, lalu HTTP 429 + `Retry-After`. Data limiter disimpan sebagai hash. Akun plaintext lama hanya dapat login untuk migrasi satu kali ke `password_hash()`.
- Ganti password admin: semua sesi JWT admin dicabut setelah password berhasil diubah; admin harus login kembali.
- Profil admin: PUT tanpa field `image_url` mempertahankan foto lama; kirim `image_url: null` untuk menghapus foto.
- File SQL/config/log/backup diblokir dari akses HTTP melalui aturan Apache; direktori database diblokir seluruhnya.
- Direktori upload wisata diberi aturan untuk mencegah eksekusi file script.
- Header keamanan dasar ditambahkan tanpa CSP ketat yang berpotensi merusak inline script lama.

## Test case staging yang wajib dijalankan

| Area | Test | Hasil yang diharapkan |
|---|---|---|
| JWT | Unset `JWT_SECRET`, panggil login admin | Login gagal aman, tidak menerbitkan token |
| JWT | Secret kurang dari 32 byte | Autentikasi gagal aman |
| JWT | Token tanpa `exp`, expired, signature diubah | HTTP 403 / token ditolak |
| RBAC | Token role user dipakai ke endpoint admin | HTTP 403 |
| Rate limit | Salah password 5 kali dari IP + username sama | Percobaan berikutnya HTTP 429 |
| Rate limit | Tunggu lock 15 menit | Percobaan dapat dicoba kembali |
| Password | Ganti password dengan benar | Password tersimpan hash dan semua token lama dicabut |
| Password | Pakai token lama setelah ganti password | HTTP 403 |
| Profile | PUT hanya mengirim `name` | Foto profil tetap sama |
| Profile | PUT `image_url: null` | Foto profil dikosongkan |
| File exposure | GET `/Backend/database/eksplormajaku_export.sql` | HTTP 403/404 |
| File exposure | GET `/Backend/jwt.local.php` atau `database.local.php` | HTTP 403/404 |
| Upload | Coba upload file PHP sebagai gambar | Ditolak |
| Upload | Coba akses file `.php` di folder gambar | HTTP 403/404 |
| Headers | Periksa response HTML/API | Header keamanan dasar tersedia |

## Catatan batasan

- Rate limit login admin menggunakan tabel database baru; migration wajib dijalankan.
- `JWT_SECRET` harus diatur di environment hosting; jangan mengandalkan secret lokal. Jika hosting tidak menyediakan environment variable untuk PHP, atur melalui konfigurasi PHP-FPM/Apache atau secret manager hosting.
- Apache `.htaccess` tidak melindungi deployment Nginx murni. Terapkan aturan setara di konfigurasi Nginx.
- Audit ini bukan penetration test eksternal. Lakukan pengujian staging dan backup database sebelum migrasi.
