# Menyiapkan Backend di Hosting

Upload isi folder `Backend` ke folder `Backend` di document root hosting. Struktur yang digunakan aplikasi:

```text
Backend/
  api/
  database/
  vendor/                 # dibuat oleh Composer untuk Google OAuth
```

## Database

1. Buat database MySQL/MariaDB dan user database melalui panel hosting, lalu berikan user tersebut akses ke database.
2. Impor `database/eksplormajaku_export.sql` untuk instalasi baru. Untuk database yang sudah berisi data, jangan impor ulang dump ini karena dump membuat ulang tabel.
3. Untuk database lama, jalankan migration yang diperlukan satu kali: `database/phone_login_migration.sql` bila kolom `No_telepon` belum ada; `database/phone_only_registration_migration.sql` bila kolom itu sudah ada tetapi `Email` masih `NOT NULL`.
4. Salin `database/database.local.example.php` menjadi `database/database.local.php`, lalu isi host, port, nama database, user, dan password dari panel hosting.
5. Upload `database/database.local.php` secara terpisah. File ini diabaikan Git dan akses web langsung ke file konfigurasi maupun file SQL diblokir oleh `.htaccess`.

Jika hosting mendukung environment variables PHP, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASS` dapat dipakai sebagai pengganti nilai dari file konfigurasi. Environment variables akan lebih diprioritaskan.

## Google OAuth

Login Google memerlukan dependensi Composer di `Backend/vendor`. Jalankan `composer install --no-dev --optimize-autoloader` dari folder `Backend`, atau upload folder `vendor` yang dibuat secara lokal. Kredensial OAuth harus disediakan terpisah melalui environment variables `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, atau file `Backend/google_oauth.local.php`.

Pastikan URL callback yang didaftarkan pada Google Cloud sama persis dengan URL callback di konfigurasi hosting. Jangan unggah konfigurasi OAuth atau database ke repositori.

## Setelah upload

- Pastikan PHP hosting menyediakan ekstensi `mysqli`, `curl`, dan `openssl`.
- Buka halaman utama serta uji registrasi, login, dan endpoint API.
- Jika koneksi database gagal, periksa error log PHP hosting; pesan koneksi tidak ditampilkan kepada pengunjung.
