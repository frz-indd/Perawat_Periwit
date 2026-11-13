# Perbaikan Login Admin – Explore Majaku

## Yang diperbaiki
- Ikon password sekarang **mata tertutup** saat password masih disembunyikan (`type=password`). Saat tombol ditekan, password ditampilkan dan ikon berubah menjadi mata terbuka. Saat ditekan lagi, password disembunyikan kembali.
- Halaman login admin memuat Bootstrap **5.3.3** (CSS dan bundle JavaScript) serta Bootstrap Icons 1.11.3. CSS khusus proyek tetap dipakai untuk mempertahankan desain.
- Endpoint login membuat tabel `admin_login_attempts` dan `admin_sessions` otomatis dengan `CREATE TABLE IF NOT EXISTS`, sehingga tabel yang belum ada tidak langsung menyebabkan HTTP 500.
- Disediakan `Backend/jwt.local.php` dengan secret acak untuk instalasi lokal. File ini sudah dikecualikan dari Git oleh `.gitignore`.
- Akun admin yang sudah ada tidak diubah. Password plaintext lama tetap dimigrasikan menjadi hash setelah login berhasil.

## Cara menjalankan
1. Cadangkan folder proyek lama.
2. Ekstrak ZIP dan salin folder `Perawat_Periwit` ke `C:\xampp\htdocs\`.
3. Pastikan `Backend/database/database.local.php` sesuai dengan nama database, username, dan password MySQL lokal.
4. Pastikan database `eksplormajaku` dan tabel `admin` sudah ada. Kolom yang dibaca endpoint: `Id_admin`, `Nama_admin`, `Username`, `Password`. Kolom `Email` dan `role` bersifat opsional.
5. Jalankan Apache dan MySQL di XAMPP.
6. Buka `http://localhost/Perawat_Periwit/FrontEnd/auth/login-admin.php`, lalu lakukan hard refresh (Ctrl+F5).
7. Masuk dengan username dan password akun yang memang sudah tersimpan pada tabel `admin`.

## Catatan Bootstrap
Tidak semua halaman proyek menggunakan Bootstrap. Halaman login admin pada paket ini sudah memuat Bootstrap 5.3.3. Hasil audit sumber menunjukkan beberapa halaman lain juga memuat Bootstrap 5.3.3, sementara halaman lain memakai CSS khusus proyek/Bootstrap Icons saja. Jadi belum tepat menyebut seluruh halaman proyek dibangun sepenuhnya dengan Bootstrap 5.

## Keamanan
- Jangan commit atau membagikan `Backend/jwt.local.php` maupun `Backend/database/database.local.php`.
- Jangan menghapus atau mengganti akun admin yang sudah ada.
- Setelah login, gunakan fitur ubah password bila tersedia dan gunakan password yang kuat.

## Pemeriksaan paket
File PHP pada proyek diperiksa menggunakan `php -l` jika PHP CLI tersedia. Login langsung tetap perlu diuji pada XAMPP dengan database lokal milik pengguna karena kredensial dan isi database tidak ikut dibundel.
