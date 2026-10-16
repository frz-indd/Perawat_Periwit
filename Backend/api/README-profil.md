# API Profil Pengguna

Endpoint profil menggunakan session PHP yang dibuat saat pengguna berhasil login. Permintaan harus membawa cookie session yang sama (umumnya `PHPSESSID`); endpoint tidak menerima ID pengguna dari URL atau body.

## URL

Pada instalasi lokal di XAMPP:

```text
http://localhost/eksplorMajaKu/users/me
```

Rewrite Apache di `.htaccess` root meneruskan URL tersebut ke `Backend/api/users_me.php`. Jika aplikasi dipasang di document root, gunakan `/users/me`.

## GET /users/me

Mengambil profil pengguna berdasarkan `id_user` dari session login.

Contoh respons `200`:

```json
{
  "status": "success",
  "data": {
    "id_user": 7,
    "nama": "Nama Pengguna",
    "email": "pengguna@example.com"
  }
}
```

## PUT /users/me

Memperbarui nama dan email. Kedua field wajib dikirim. `Password` tidak dapat diubah melalui endpoint ini.

Header:

```text
Content-Type: application/json
Cookie: PHPSESSID=<session-login>
```

Body:

```json
{
  "nama": "Nama Baru",
  "email": "nama.baru@example.com"
}
```

Contoh respons `200`:

```json
{
  "status": "success",
  "message": "Profil berhasil diperbarui.",
  "data": {
    "id_user": 7,
    "nama": "Nama Baru",
    "email": "nama.baru@example.com"
  }
}
```

## Pengujian dengan Postman

Collection Postman tersedia di `Backend/api/postman/users-me.postman_collection.json`. Import file tersebut, atur variabel `loginEmail` dan `loginPassword` di Postman secara lokal, lalu jalankan collection. Jangan commit variabel berisi kredensial.

1. Jalankan Apache dan MySQL di XAMPP, lalu kirim `POST http://localhost/eksplorMajaKu/Backend/api/fungsi_login.php` dengan form-urlencoded `email` dan `password`.
2. Pertahankan cookie session yang diterima dari login di cookie jar Postman.
3. Kirim `GET http://localhost/eksplorMajaKu/users/me`; pastikan respons `200` hanya berisi ID, nama, dan email pengguna yang login.
4. Kirim `PUT` ke URL yang sama dengan header `Content-Type: application/json` dan body nama/email; pastikan respons `200`, lalu GET ulang untuk memverifikasi perubahan tersimpan.
5. Ulangi GET/PUT tanpa cookie untuk memastikan respons `401`. Coba email pengguna lain untuk memastikan respons `409`, dan nama/email tidak valid untuk memastikan respons `422`.

## Status respons

- `200`: profil berhasil dibaca atau diperbarui.
- `400`: body JSON tidak valid.
- `401`: session login tidak ada atau tidak valid.
- `404`: pengguna di database tidak ditemukan.
- `405`: metode selain GET/PUT; header `Allow` mencantumkan metode yang diterima.
- `409`: email sudah digunakan pengguna lain.
- `415`: PUT tidak menggunakan `application/json`.
- `422`: nilai nama/email tidak memenuhi validasi.
- `500`: kesalahan server/database; detail teknis hanya dicatat ke log server.