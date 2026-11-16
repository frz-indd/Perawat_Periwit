# Changelog

## 2026-11-04

### Diperbaiki

- Login Google di XAMPP gagal dengan `{"status":"error","message":"login Google gagal"}` karena callback memakai URL lama `eksplorMajaKu` dan verifikasi ID token mewajibkan folder Composer `Backend/vendor` yang tidak terpasang.
- `redirect_uri` OAuth sekarang memakai URL aplikasi yang sedang diakses saat host-nya `localhost`, sehingga login dari `Perawat_Periwit` kembali ke callback proyek yang sama.
- Pada localhost, kredensial diambil dari `Backend/google_oauth.local.php` terlebih dahulu agar environment variable klien OAuth proyek lama tidak menimpa konfigurasi.
- Verifikasi ID token Google memakai tanda tangan JWT RS256 dari JWKS Google, tanpa bergantung pada `google/apiclient`.
- Callback menolak proses bila koneksi database tidak tersedia, alih-alih memicu error `begin_transaction()` pada `null`.

### Catatan

- Daftarkan `http://localhost/Perawat_Periwit/Backend/api/google_callback.php` sebagai Authorized redirect URI pada klien OAuth Google Cloud yang dipakai aplikasi ini, di samping URL callback hosting.

## 2026-10-28

### Ditambahkan

- Endpoint terproteksi `PUT /api/user/change-password` untuk pengguna yang sudah login.
- Verifikasi password lama dengan `password_verify` dan penyimpanan password baru menggunakan hash bcrypt.
- Validasi password server-side untuk registrasi dan ubah password: minimal 8 karakter, huruf besar, huruf kecil, angka, dan karakter khusus.
- Validasi batas upload foto profil 10 MB pada edit profil. Upload yang melampaui batas ditolak dengan pesan `Ukuran foto profil melebihi batas maksimal 10 MB`.
- Dokumentasi endpoint dan kriteria validasi di `Backend/openapi.yaml`.

### Catatan

- Backend belum menyimpan foto profil. Validasi upload saat ini menolak berkas yang terlalu besar; penyimpanan berkas berukuran valid belum diimplementasikan.
- Lint PHP belum dijalankan karena PHP CLI tidak ditemukan di `PATH` pada lingkungan kerja.

### Pencarian destinasi

- `GET /api/destinations/search` mencocokkan kata kunci memakai `LIKE %keyword%` pada nama wisata dan deskripsi.
- Ditambahkan migration `Backend/database/destination_search_indexes.sql` untuk membuat index pencarian pada kolom nama dan deskripsi wisata.
- Catatan performa: index B-tree tidak dapat digunakan MySQL untuk mempersempit pencarian dengan wildcard di awal (`%keyword%`); pola tersebut dipertahankan sesuai kebutuhan pencarian substring.

### Favorit destinasi

- Ditambahkan endpoint terproteksi `POST /api/favorites` untuk menambah (`action: add`) atau menghapus (`action: remove`) relasi favorit destinasi.
- Endpoint mengambil `user_id` dari session login, memvalidasi token CSRF, dan memakai tabel `favorit` dengan kolom `Id_user` dan `Id_wisata` yang sesuai dengan skema proyek.
- Kontrak OpenAPI dan `api_kontrak.txt` diperbarui.
