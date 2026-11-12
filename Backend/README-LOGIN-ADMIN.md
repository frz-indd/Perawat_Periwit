# Perbaikan Login Admin

## Perbaikan
- Memperbaiki fatal PHP parse error pada `api/admin_login.php` yang sebelumnya membuat endpoint login tidak bisa dijalankan.
- Login mendukung skema tabel `admin` lama (`Id_admin`, `Nama_admin`, `Username`, `Password`) dari database proyek.
- Tetap mendukung akun admin yang disimpan pada tabel `users`.
- Kolom `Email` dan `role` pada tabel `admin` dibuat opsional saat membaca skema lama.
- Password hash bcrypt diverifikasi dengan `password_verify`; password plaintext lama akan diubah menjadi hash setelah login berhasil.
- Menghapus fallback hardcoded yang sebelumnya mengizinkan login `admin/admin123` ketika database mati. Login sekarang harus melalui database.
- Mengembalikan token JWT dengan role `admin` untuk dipakai halaman dashboard.

## Cara mengaktifkan
1. Pastikan database `eksplormajaku` sudah diimpor.
2. Atur koneksi pada `Backend/database/database.local.php` berdasarkan `database.local.example.php` (host, user, password, nama database, port).
3. Buka phpMyAdmin, pilih database `eksplormajaku`, lalu import `Backend/database/setup_admin_login.sql` satu kali.
4. Buka `FrontEnd/auth/login-admin.php`.
5. Login awal: **username `admin`**, **password `admin123`**.
6. Setelah berhasil masuk, segera ganti password admin.

> Jika kolom `Email` atau `role` sudah pernah ditambahkan secara manual, hapus baris `ALTER TABLE` yang menambahkan kolom tersebut sebelum menjalankan SQL, agar tidak terjadi error duplicate column.

## Endpoint
`POST /api/admin/login` dengan JSON:

```json
{"username":"admin","password":"admin123"}
```

Respons berhasil berisi `status`, `token`, dan `user`.
