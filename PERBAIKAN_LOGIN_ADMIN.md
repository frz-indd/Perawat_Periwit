# Perbaikan login admin

Paket ini memperbaiki dua penyebab umum HTTP 500 pada login admin:

1. Endpoint login sekarang membuat tabel `admin_login_attempts` dan `admin_sessions` secara otomatis jika belum tersedia.
2. Ditambahkan `Backend/jwt.local.php` dengan secret JWT lokal acak (64 karakter heksadesimal), sehingga proses penerbitan token tidak gagal karena `JWT_SECRET` belum dikonfigurasi.

## Cara memasang di XAMPP

1. Cadangkan folder proyek `C:\xampp\htdocs\Perawat_Periwit` terlebih dahulu.
2. Ekstrak ZIP ini.
3. Salin isi folder `Perawat_Periwit` hasil ekstrak ke `C:\xampp\htdocs\Perawat_Periwit`, lalu izinkan Windows mengganti file yang sama.
4. Pastikan file `Backend/jwt.local.php` ikut tersalin dan `Backend/database/database.local.php` tetap berisi konfigurasi database lokal milik Anda.
5. Restart Apache melalui XAMPP Control Panel.
6. Buka ulang `http://localhost/Perawat_Periwit/FrontEnd/auth/login-admin.php` dengan Ctrl+F5.

Catatan:
- Tidak perlu import ulang database atau menghapus tabel.
- Username/password admin yang sudah ada tidak diubah oleh perbaikan ini.
- Jika akun `wowok` memang belum ada di tabel `admin`, endpoint akan mengembalikan pesan username/password salah (401), bukan HTTP 500. Periksa isi tabel `admin` di phpMyAdmin.
- Secret JWT dalam paket ini khusus untuk instalasi lokal ini. Jangan unggah `Backend/jwt.local.php` ke repository publik.

## Pemeriksaan

Seluruh 57 file PHP di paket berhasil melewati pemeriksaan sintaks `php -l`.
