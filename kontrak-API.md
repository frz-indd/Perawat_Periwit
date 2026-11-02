# EXPLORE MAJAKU - KONTRAK API BACKEND

## Base URL

Base URL production:

```text
https://perawatperiwit.site.je
```

Untuk pengujian lokal, ganti domain dengan `http://localhost/Perawat_Periwit`. Clean URL yang saat ini diteruskan oleh `.htaccess` adalah `/users/me`, `/categories`, dan `/categories/{id}/wisata`. Endpoint data mengembalikan JSON; logout dan OAuth juga dapat mengarahkan browser ke halaman lain.

## Autentikasi dan session

- Login membuat session PHP dan cookie `PHPSESSID`.
- Browser harus menyimpan dan mengirim cookie session yang sama. Untuk request dari aplikasi gunakan same-origin.
- Login pengguna maupun admin mengembalikan token `csrf` dalam respons.
- Dari endpoint yang saat ini tersedia, logout meminta token CSRF melalui field `csrf`. Endpoint profil tidak memeriksa token CSRF.
- Login admin menyimpan role `admin` di session. Endpoint khusus admin belum tersedia.

## 1. Login pengguna atau admin

`POST https://perawatperiwit.site.je/Backend/api/fungsi_login.php`

Kirim data sebagai `application/x-www-form-urlencoded` atau `multipart/form-data`.

Login pengguna memakai field `email` (alamat email atau nomor telepon) dan `password` (kata sandi). Field `role` boleh tidak dikirim; nilai bawaannya `user`.

Login admin memakai `role=admin`, field `username`, dan `password` (kata sandi).

Contoh respons login pengguna `200 OK`:

```json
{
  "status": "success",
  "message": "Login berhasil.",
  "redirect": "../../index.php",
  "data": {
    "id_user": 7,
    "nama": "Nama",
    "email": "nama@example.com",
    "csrf": "<token>"
  }
}
```

Login admin mengembalikan `id_admin`, `nama`, `username`, `role` dengan nilai `admin`, dan `csrf` di dalam `data`.

Status yang dapat diterima:

- `200`: login berhasil dan session dibuat.
- `401`: password salah atau kredensial tidak cocok.
- `404`: akun pengguna tidak ditemukan.
- `405`: metode selain POST.
- `422`: input atau role tidak valid.
- `500`: terjadi kesalahan server.

## 2. Registrasi pengguna

`POST https://perawatperiwit.site.je/Backend/api/fungsi_register.php`

Kirim data sebagai `application/x-www-form-urlencoded` atau `multipart/form-data`.

Field `email`, `password`, dan `confirmPassword` wajib diisi. Field `nama` boleh kosong. Isi `password` dan `confirmPassword` dengan kata sandi yang sama.

Password harus memiliki 8 sampai 72 karakter, huruf besar dan kecil, angka, serta karakter khusus. Jika `nama` tidak diisi, backend menggunakan bagian email sebelum tanda `@`. Pendaftaran tidak langsung membuat session login.

Contoh respons `201 Created`:

```json
{
  "status": "success",
  "message": "Pendaftaran berhasil.",
  "data": {
    "id_user": 8,
    "nama": "Nama",
    "email": "nama@example.com"
  }
}
```

Status yang dapat diterima:

- `201`: akun berhasil dibuat.
- `405`: metode selain POST.
- `409`: email sudah digunakan.
- `422`: email, password, konfirmasi password, atau nama tidak valid.
- `500`: terjadi kesalahan server.

## 3. Login Google

Login Google memakai redirect browser dan cookie session. Frontend tidak mengirim token Google secara langsung.

Server harus menyediakan `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET`. `GOOGLE_REDIRECT_URI` dapat diisi atau dibiarkan kosong agar server membentuk alamatnya sendiri. Untuk instalasi lokal, callback yang didaftarkan di Google Cloud adalah:

`https://perawatperiwit.site.je/Backend/api/google_callback.php`

### Memulai OAuth

`GET https://perawatperiwit.site.je/Backend/api/google_login.php`

Untuk pendaftaran, gunakan URL berikut:

`GET https://perawatperiwit.site.je/Backend/api/google_login.php?intent=register`

Nilai `intent` yang tidak diisi atau bukan `register` akan diperlakukan sebagai login. Endpoint mengarahkan browser ke Google. Browser harus mempertahankan cookie session ketika kembali ke aplikasi.

Jika konfigurasi Google belum tersedia, browser diarahkan ke halaman login atau pendaftaran dengan `google=not_configured`. Metode selain GET menghasilkan `405`.

### Callback OAuth

`GET https://perawatperiwit.site.je/Backend/api/google_callback.php`

Google mengarahkan browser ke callback dengan kode otorisasi dan state. State harus sesuai dengan session dari proses login dan hanya dapat dipakai sekali.

Jika berhasil, backend membuat atau mencocokkan akun berdasarkan email, membuat session login, lalu mengarahkan browser ke halaman utama. Jika pengguna membatalkan, browser kembali ke halaman autentikasi dengan `google=cancelled`. Jika email untuk pendaftaran sudah terdaftar, browser kembali dengan `google=email_exists`.

Untuk kegagalan yang ditangani sebagai JSON, bentuk responsnya:

```json
{
  "status": "error",
  "message": "Pesan kegagalan"
}
```

Status yang dapat diterima:

- `400`: state atau kode otorisasi tidak valid.
- `401`: Google menolak kode atau identitas tidak valid.
- `405`: metode selain GET.
- `503`: konfigurasi Google OAuth belum tersedia.
- `500`: login gagal karena kesalahan server.

## 4. Profil pengguna

Kedua endpoint memakai session pengguna. Identitas pengguna diambil dari session, bukan dari ID yang dikirim frontend.

### GET https://perawatperiwit.site.je/users/me

Mengambil profil pengguna yang sedang login.

Contoh respons `200 OK`:

```json
{
  "status": "success",
  "data": {
    "id_user": 7,
    "nama": "Nama",
    "email": "nama@example.com"
  }
}
```

### PUT https://perawatperiwit.site.je/users/me

Memperbarui nama dan email. Password tidak dapat diubah melalui endpoint ini.

Header:

```text
Content-Type: application/json
Accept: application/json
```

Body:

```json
{
  "nama": "Nama Baru",
  "email": "baru@example.com"
}
```

Status yang dapat diterima:

- `200`: profil berhasil dibaca atau diperbarui.
- `400`: JSON tidak valid.
- `401`: belum login.
- `404`: pengguna tidak ditemukan.
- `405`: metode selain GET atau PUT; header `Allow` mencantumkan metode yang diterima.
- `409`: email sudah dipakai.
- `415`: PUT tidak menggunakan `Content-Type: application/json`.
- `422`: field tidak valid.
- `500`: terjadi kesalahan server.

## 5. Daftar wisata

`GET https://perawatperiwit.site.je/Backend/api/wisata.php`

Mengembalikan daftar wisata beserta kategori, foto, harga, jam operasional, dan objek `lokasi`. Objek lokasi berisi `alamat`, `domisili`, `latitude`, `longitude`, dan `koordinat_lengkap`. Setiap item juga memiliki `jarak_km`; nilainya `null` bila koordinat pengguna tidak dikirim atau koordinat wisata tidak lengkap.

Contoh parameter jarak:

```text
GET https://perawatperiwit.site.je/Backend/api/wisata.php?latitude=-6.9&longitude=108.4
```

`latitude` dan `longitude` harus dikirim bersama sebagai angka. Latitude harus berada pada rentang -90 sampai 90, dan longitude pada -180 sampai 180. Koordinat pengguna yang tidak lengkap atau tidak valid menghasilkan `422`.

## 6. Kategori wisata

Request kategori tidak memerlukan login.

### GET https://perawatperiwit.site.je/categories

Mengembalikan semua kategori aktif.

Contoh item:

```json
{
  "id_kategori": 1,
  "nama": "Alam",
  "gambar": "FrontEnd/assets/images/gununglandscape.jpg"
}
```

### GET https://perawatperiwit.site.je/categories/{id}/wisata

Mengembalikan kategori aktif dan daftar wisata di kategori tersebut. Setiap wisata memiliki `id_wisata`, `id_kategori`, `nama_wisata`, `gambar`, `deskripsi`, `alamat`, `wilayah`, `harga_tiket`, `jam_buka`, `jam_tutup`, `latitude`, `longitude`, dan objek `lokasi`.

- ID yang bukan bilangan bulat positif menghasilkan `400 invalid_category_id`.
- Kategori tidak ditemukan atau tidak aktif menghasilkan `404 category_not_found`.
- Kategori tanpa wisata menghasilkan `200` dengan `wisata` berupa array kosong.

Untuk database lama, jalankan migration `Backend/database/categories_api_migration.sql`. Database baru dari `Backend/database/eksplormajaku_export.sql` sudah memiliki kolom kategori yang diperlukan.

## 7. Pencarian destinasi

`GET https://perawatperiwit.site.je/api/destinations/search?q=<kata-kunci>`

Endpoint ini mencari destinasi dan mengembalikan informasi wisata beserta lokasinya. Halaman frontend `https://perawatperiwit.site.je/search` juga memproses filter `wilayah[]`, `kategori[]`, `min`, dan `max` secara terpisah.

- `q` wajib berupa teks minimal 2 karakter.
- Pencarian mencocokkan nama, deskripsi, alamat, domisili, dan kategori.
- Respons sukses berbentuk `{"status":"success","data":[...]}` dan menyertakan informasi lokasi.
- Kata kunci tidak valid menghasilkan `400` atau `422`.

## 8. Data lokasi destinasi

### GET https://perawatperiwit.site.je/api/destinations/{id}/location

Mengembalikan `id_wisata`, `nama_wisata`, `alamat`, `latitude`, `longitude`, dan `koordinat_lengkap`. Jika koordinat kosong atau tidak valid, kedua koordinat dikembalikan sebagai `null` dan `koordinat_lengkap` bernilai `false`.

Status yang diminta: `200` berhasil, `400` ID tidak valid, `404` destinasi tidak ditemukan.

### PUT https://perawatperiwit.site.je/api/destinations/{id}/location

Endpoint ini memerlukan session admin dan token CSRF.

Header:

```text
Content-Type: application/json
X-CSRF-Token: <token-session-admin>
Cookie: PHPSESSID=<session-admin>
```

Body wajib menyertakan semua field:

```json
{
  "alamat": "Alamat wisata",
  "latitude": -6.94333,
  "longitude": 108.43917
}
```

Alamat berupa teks maksimal 255 karakter. Latitude dan longitude dikirim berpasangan sebagai angka dalam rentang latitude -90 sampai 90 dan longitude -180 sampai 180. Kirim keduanya `null` jika koordinat belum diketahui.

Status yang diminta: `200` berhasil, `400` JSON atau ID tidak valid, `401` session admin tidak ada, `403` CSRF tidak valid, `404` destinasi tidak ditemukan, `415` Content-Type salah, `422` alamat atau koordinat tidak valid, dan `500` kesalahan server.

## 9. Ulasan destinasi

`POST https://perawatperiwit.site.je/api/destinations/{id}/reviews`

Endpoint ini memerlukan session pengguna dan token CSRF.

Header:

```text
Content-Type: application/json
X-CSRF-Token: <token-session-user>
Cookie: PHPSESSID=<session-user>
```

Body:

```json
{
  "rating": 5,
  "text": "Tempatnya nyaman dan bersih."
}
```

`rating` harus bilangan bulat 1 sampai 5. `text` wajib berisi 1 sampai 500 karakter; nama field `komentar` juga dapat digunakan.

Respons sukses `201` berisi `id_review`, `destination_id`, `rating`, `text`, `average_rating`, dan `review_count`. Ulasan disimpan di tabel `ulasan`; rata-rata disimpan pada kolom `wisata.average_rating`. Jalankan migration `Backend/database/destination_reviews_migration.sql` pada database lama.

Status yang diminta: `201` tersimpan, `400` JSON atau ID tidak valid, `401` belum login sebagai pengguna, `403` CSRF tidak valid, `404` destinasi tidak ditemukan, `405` metode tidak didukung, `415` Content-Type salah, `422` payload tidak valid, dan `500` kesalahan server.

## 10. Favorit destinasi

`POST https://perawatperiwit.site.je/api/favorites`

Endpoint ini memerlukan session pengguna dan token CSRF.

Header:

```text
Content-Type: application/json
X-CSRF-Token: <token-session-user>
Cookie: PHPSESSID=<session-user>
```

Body untuk menambah favorit:

```json
{
  "destination_id": "DEST001",
  "action": "add"
}
```

Gunakan `action: "remove"` untuk menghapus favorit. `destination_id` merujuk ke `wisata.id_wisata`; identitas pengguna diambil dari session, bukan body. Data disimpan di tabel `favorit` dengan relasi `Id_user` dan `Id_wisata`.

Status yang diminta: `200` berhasil, `400` JSON tidak valid, `401` belum login, `403` CSRF tidak valid, `404` destinasi tidak ditemukan, `405` metode tidak didukung, `415` Content-Type salah, `422` payload tidak valid, dan `500` kesalahan server.

## 11. Mengubah password

`PUT https://perawatperiwit.site.je/api/user/change-password`

Endpoint ini memerlukan session pengguna.

Header:

```text
Content-Type: application/json
Cookie: PHPSESSID=<session-user>
```

Body:

```json
{
  "currentPassword": "PasswordLama1!",
  "newPassword": "PasswordBaru2!"
}
```

Password baru harus memiliki minimal 8 karakter dan terdiri dari huruf besar, huruf kecil, angka, serta karakter khusus.

Status: `200` berhasil, `400` JSON tidak valid, `401` belum login, `405` metode selain PUT, `415` Content-Type salah, `422` password tidak valid atau password lama tidak cocok, `404` pengguna tidak ditemukan, dan `500` kesalahan server.

## 12. Logout

`POST https://perawatperiwit.site.je/Backend/api/logout.php`

Logout memerlukan cookie session dan token CSRF yang sama dengan token session. Kirim token sebagai field `csrf` dengan format form-urlencoded atau multipart form-data.

```text
Cookie: PHPSESSID=<session-id>
csrf=<token-session>
```

Jika berhasil, session dihapus dan browser diarahkan kembali ke halaman utama dengan status `303`. Token tidak valid menghasilkan `403`; metode selain POST menghasilkan `405`. Endpoint ini bukan respons JSON.

## Kolom lokasi dan migration

Tabel `wisata` menggunakan kolom `alamat`, `latitude`, dan `longitude`. Spesifikasi awal menyebut migration `Backend/database/destination_location_migration.sql` untuk database lama. Pastikan file migration tersedia sebelum menjalankannya.

## Collection Postman

Collection tersedia di:

```text
Backend/api/postman/Perawat_Periwit.postman_collection.json
```

Atur `baseUrl` ke:

```text
http://localhost/Perawat_Periwit
```

Collection menguji profil dan kategori. Jika `loginEmail` dan `loginPassword` diisi dengan kredensial valid, collection juga menguji login serta akses terautentikasi. Jangan menyimpan kredensial ke repository.
