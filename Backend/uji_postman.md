# Bukti Uji API dengan Postman

Tanggal uji: 20 Oktober 2026  
Alat: Postman CLI 1.77.0  
Koleksi: `Backend/api/postman/Explore MajaKu - Backend API`  
Base URL: `http://localhost/Perawat_Periwit`
[!(bukti_uji_postman.png)]

cakupan meliputi akses profil tanpa login, daftar kategori dan wisata, wisata per kategori, validasi id kategori, detail lokasi destinasi, login dengan kredensial kosong, dan penolakan endpoint terlindungi tanpa session. Status yang diharapkan (`401`, `422`, `400`, dan `404`) dinilai lulus oleh assertion koleksi.

## Menjalankan ulang
postman collection run "Backend/api/postman/Explore MajaKu - Backend API"

