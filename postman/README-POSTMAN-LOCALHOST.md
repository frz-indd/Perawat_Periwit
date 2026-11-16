# Paket Pengujian Postman Perawat_Periwit (Localhost)

## Pemisahan folder
- `adminss/`: koleksi dan environment API Admin.
- `userss/`: koleksi dan environment API User/publik.

## Base URL
`http://localhost/Perawat_Periwit`

## Cara menjalankan
1. Ekstrak proyek ke `C:\xampp\htdocs\Perawat_Periwit`.
2. Jalankan Apache dan MySQL di XAMPP, lalu import database proyek sesuai dokumentasi.
3. Pastikan `Backend/database/database.local.php` berisi konfigurasi DB lokal (buat dari `database.local.example.php`).
4. Buka Postman → Import → pilih collection JSON dan environment JSON dari folder Admin atau User.
5. Pilih environment `Perawat_Periwit Localhost`; isi password/akun uji sendiri.
6. Jalankan request satu per satu atau Collection Runner. Request CRUD dapat mengubah data lokal; gunakan database testing/cadangan.
7. Setelah respons asli tampil, ambil screenshot Postman yang memperlihatkan method+URL, status HTTP, dan response body. Simpan di folder `adminss/` atau `userss/` sesuai peran.

## Catatan penting
- Folder screenshot sengaja belum berisi gambar hasil palsu. Lingkungan ini tidak dapat mengakses `localhost` pada komputer Anda, sehingga respons nyata hanya dapat direkam saat koleksi dijalankan di XAMPP Anda.
- Google OAuth memerlukan Client ID/Secret dan callback URL yang valid. Tanpa konfigurasi OAuth, endpoint tersebut hanya dapat diuji untuk respons konfigurasi/error.
- Endpoint upload/foto, CRUD, dan ulasan memerlukan ID serta data uji yang valid.
- Koleksi dibuat dari kontrak `Backend/openapi.yaml`, koleksi Postman proyek yang sudah ada, serta endpoint PHP admin langsung. Periksa request body contoh sebelum menjalankan operasi yang mengubah/menghapus data.
- Assertion default hanya memastikan server tidak mengembalikan 5xx; validasi status dan isi response perlu disesuaikan dengan data/akun lokal.
