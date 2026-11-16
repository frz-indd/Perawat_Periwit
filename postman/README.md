# Dokumentasi Screenshot Pengujian API

Bukti screenshot pengujian API dipisahkan berdasarkan role agar mudah ditemukan.

## Struktur folder

- `adminss/` — screenshot request dan response endpoint khusus Admin.
- `userss/` — screenshot request dan response endpoint khusus User.

## Saran penamaan file

Gunakan nama yang menjelaskan endpoint dan hasil pengujiannya, misalnya:

- `adminss/01-admin-login-berhasil.png`
- `adminss/02-admin-dashboard.png`
- `adminss/03-admin-tambah-data-wisata.png`
- `userss/01-user-register-berhasil.png`
- `userss/02-user-login-berhasil.png`
- `userss/03-user-profil.png`

Untuk setiap endpoint, usahakan screenshot memperlihatkan method, URL, request/payload (jika ada), status code, dan response. Jangan tampilkan password, token aktif, cookie, atau data pribadi; sensor bagian sensitif sebelum menyimpan screenshot.

Folder ini disediakan untuk bukti screenshot hasil pengujian aktual. Screenshot tidak dibuat-buat; masukkan hasil tangkapan layar dari Postman setelah endpoint dijalankan.
