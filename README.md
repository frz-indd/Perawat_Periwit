# Explore MajaKu

## API Profil Pengguna

API profil mengambil dan memperbarui data pengguna yang sedang login. Identitas pengguna ditentukan dari session PHP (`PHPSESSID`), bukan dari ID yang dikirim Frontend.

### URL dan autentikasi

Di XAMPP lokal, gunakan:

```text
http://localhost/eksplorMajaKu/users/me
```

Apache meneruskan URL tersebut melalui `.htaccess` ke `Backend/api/users_me.php`. Pengguna harus login terlebih dahulu melalui `Backend/api/fungsi_login.php`. Browser harus mengirim cookie session yang sama; untuk request same-origin gunakan `credentials: "same-origin"`.

### GET /users/me

Mengambil profil pengguna dari database berdasarkan session aktif.

Contoh respons `200 OK`:

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

Frontend membaca nama dari `data.nama` (kolom database `Nama_user`) dan email dari `data.email`.

Contoh pemanggilan dari halaman `FrontEnd/pages/profil.php`:

```javascript
const response = await fetch(new URL("../../users/me", window.location.href), {
    credentials: "same-origin",
    headers: { Accept: "application/json" }
});
const result = await response.json();

if (!response.ok) {
    throw new Error(result.message || "Profil gagal dimuat.");
}

document.getElementById("profile-name").textContent = result.data.nama;
document.getElementById("profile-email").textContent = result.data.email;
```

### PUT /users/me

Memperbarui nama dan email pengguna yang sedang login. Kedua field wajib dikirim sebagai JSON. Password tidak diperbarui melalui endpoint ini.

Header:

```text
Content-Type: application/json
Accept: application/json
```

Body:

```json
{
  "nama": "Nama Baru",
  "email": "nama.baru@example.com"
}
```

Contoh respons `200 OK` mengembalikan profil yang sudah diperbarui di `data`.

### Status respons

- `200`: profil berhasil dibaca atau diperbarui.
- `400`: body bukan JSON valid.
- `401`: session login tidak ada atau tidak valid.
- `404`: pengguna dari session tidak ditemukan di database.
- `405`: metode selain GET atau PUT; lihat header `Allow`.
- `409`: email sudah digunakan pengguna lain.
- `415`: PUT tidak memakai `Content-Type: application/json`.
- `422`: nama atau email tidak memenuhi validasi.
- `500`: kesalahan server atau database.

### Pengujian Postman

Import collection `Backend/api/postman/users-me.postman_collection.json`. Atur variabel `loginEmail` dan `loginPassword` secara lokal sebelum menjalankan seluruh collection. Collection menguji akses tanpa login, login, GET profil, validasi PUT, dan PUT dengan nilai profil yang sama. Jangan commit kredensial.

## API Kategori Wisata

Kategori disimpan pada tabel `kategori`. Kolom `aktif` menentukan apakah kategori ditampilkan; `gambar` menyimpan path gambar relatif terhadap root aplikasi. Relasi wisata menggunakan `wisata.id_kategori`.

Untuk database yang sudah ada, jalankan migration satu kali melalui phpMyAdmin atau MySQL CLI:

```text
Backend/database/categories_api_migration.sql
```

Database baru yang diimpor dari `Backend/database/eksplormajaku_export.sql` sudah memiliki kolom tersebut.

### GET /categories

Mengambil semua kategori aktif. Setiap item menyertakan ID, nama, dan gambar.

URL lokal: `http://localhost/eksplorMajaKu/categories`

Contoh respons `200 OK`:

```json
{
  "status": "success",
  "data": [
    {
      "id_kategori": 1,
      "nama": "Alam",
      "gambar": "FrontEnd/assets/images/gununglandscape.jpg"
    }
  ]
}
```

### GET /categories/{id}/wisata

Mengambil wisata yang `wisata.id_kategori`-nya cocok dengan ID kategori aktif. Contoh URL lokal: `http://localhost/eksplorMajaKu/categories/1/wisata`.

Contoh respons `200 OK`:

```json
{
  "status": "success",
  "data": {
    "kategori": {
      "id_kategori": 1,
      "nama": "Alam",
      "gambar": "FrontEnd/assets/images/gununglandscape.jpg"
    },
    "wisata": [
      {
        "id_wisata": "K00001",
        "id_kategori": 1,
        "nama_wisata": "Curug Putri Palutungan",
        "gambar": "FrontEnd/assets/images/CurugPutri.png",
        "deskripsi": "curug",
        "alamat": "Cisantana, Cigugur, Kuningan Regency, West Java 45552",
        "wilayah": "Kabupaten Kuningan, Jawa Barat",
        "harga_tiket": 15000,
        "jam_buka": "08:00:00",
        "jam_tutup": "16:00:00",
        "latitude": -6.94333,
        "longitude": 108.43917
      }
    ]
  }
}
```

ID nonpositif atau bukan bilangan bulat menghasilkan `400 invalid_category_id`. ID kategori yang tidak ada atau tidak aktif menghasilkan `404 category_not_found`. Jika kategori aktif belum memiliki wisata, endpoint tetap mengembalikan `200` dengan array `wisata` kosong.

Collection Postman yang sama juga menguji `GET /categories`, filter wisata, format ID invalid, serta `GET /Backend/api/wisata.php` untuk memastikan ID dan foto ikut dikirim; request kategori tidak memerlukan autentikasi.

## API Pencarian Destinasi

Halaman pencarian tersedia di `/search?q=...` dan mengambil hasil dari endpoint berikut:

```text
GET /api/destinations/search?q=...
```

Contoh URL lokal: `http://localhost/eksplorMajaKu/api/destinations/search?q=curug`.
Parameter `q` wajib berisi minimal 2 karakter. Respons sukses menggunakan format
`{"status":"success","data":[...]}`; jika tidak ada kecocokan, `data` berupa array kosong.
Kata kunci dicocokkan dengan nama, deskripsi, alamat, domisili, dan kategori wisata.

Halaman `/search` juga menerima parameter filter `wilayah[]`, `kategori[]`, `min`, dan `max`.
Kotak pencarian memvalidasi panjang kata kunci di browser sebelum membuka halaman hasil.

### Data lokasi wisata

Data detail wisata pada `GET /Backend/api/wisata.php`, `GET /categories/{id}/wisata`, dan pencarian menyertakan alamat serta koordinat. Endpoint daftar wisata menaruhnya di objek `lokasi`; endpoint kategori juga menyediakan field `alamat`, `latitude`, dan `longitude` langsung pada setiap item.

Gunakan `GET /api/destinations/{id}/location` untuk mengambil lokasi satu destinasi. Contoh respons saat koordinat tersedia:

```json
{
  "status": "success",
  "data": {
    "id_wisata": "K00001",
    "nama_wisata": "Curug Putri Palutungan",
    "alamat": "Contoh alamat wisata",
    "latitude": -6.94333,
    "longitude": 108.43917,
    "koordinat_lengkap": true
  }
}
```

Jika koordinat belum tersedia atau hanya salah satunya terisi, endpoint mengembalikan `latitude` dan `longitude` sebagai `null` serta `koordinat_lengkap: false`. Alamat tetap ditampilkan jika tersedia.

Admin dapat menyimpan atau memperbarui alamat dan koordinat dengan `PUT /api/destinations/{id}/location`. Request wajib memakai session admin, header `X-CSRF-Token` dari respons login admin, dan JSON berikut. Latitude dan longitude harus dikirim berpasangan, atau keduanya `null` bila lokasi koordinat belum diketahui. Batas valid: latitude `-90..90`, longitude `-180..180`; alamat maksimal 255 karakter.

```json
{
  "alamat": "Contoh alamat wisata",
  "latitude": -6.94333,
  "longitude": 108.43917
}
```

Kolom `alamat`, `latitude`, dan `longitude` disediakan oleh schema wisata. Untuk database lama, jalankan `Backend/database/destination_location_migration.sql`; migration menambahkan kolom yang belum tersedia dan mengosongkan pasangan koordinat yang tidak lengkap atau di luar rentang. Request Postman untuk membaca data lokasi dan menolak koordinat tidak lengkap tersedia di `Backend/api/postman/users-me.postman_collection.json`; isi variabel `adminUsername` dan `adminPassword` sebelum menjalankan bagian uji lokasi.

### POST /api/destinations/{id}/reviews

Endpoint terproteksi untuk pengguna yang sudah login. Request harus mengirim cookie session PHP yang didapat saat login serta token CSRF pada header `X-CSRF-Token`. Token dibuat di session oleh halaman detail wisata (`$_SESSION["csrf"]`). Body harus berupa JSON dengan rating bilangan bulat 1–5 dan teks ulasan 1–500 karakter:

```http
POST http://localhost/eksplorMajaKu/api/destinations/K00001/reviews
Content-Type: application/json
X-CSRF-Token: <token-session>
Cookie: PHPSESSID=<session-id>

{"rating":5,"text":"Tempatnya nyaman dan bersih."}
```

Request menyimpan record baru. Implementasi project memakai tabel `ulasan` (`Id_user`, `Id_wisata`, `rating`, `komentar`) dan tabel `wisata`, bukan tabel `reviews` dan `destinations`. Setelah insert, kolom `wisata.average_rating` diperbarui dengan rata-rata rating (dibulatkan dua angka desimal). Terapkan `Backend/database/destination_reviews_migration.sql` satu kali agar kolom tersedia; migration juga mengisi rata-rata untuk ulasan yang sudah tersimpan. Nilai rata-rata dikembalikan sebagai `average_rating` pada respons.

Respons sukses `201 Created` menyertakan ID ulasan, rating, teks, rata-rata terbaru, dan jumlah ulasan. Status lain: `400` ID destinasi atau JSON tidak valid, `401` belum login sebagai pengguna, `403` token CSRF tidak valid, `404` destinasi tidak ditemukan, `405` metode selain POST, `415` content type bukan JSON, `422` payload tidak valid, dan `500` kesalahan server.

## Seed Data Wisata

Data destinasi statis pada halaman kategori dan pencarian disatukan menjadi 27 wisata unik di `Backend/database/wisata_seed.sql`. Path gambar yang memang tersedia di `FrontEnd/assets/images` disimpan pada tabel `foto_wisata`; endpoint wisata mengirim path foto pertama sebagai field `gambar`. Jalankan script ini setelah schema dan kategori tersedia:

Urutan setup database saat instalasi baru: impor `Backend/database/eksplormajaku_export.sql`, jalankan `Backend/database/categories_api_migration.sql`, `Backend/database/destination_location_migration.sql`, `Backend/database/destination_reviews_migration.sql`, lalu `Backend/database/wisata_seed.sql`. `Backend/database/exploremajaku.sql` adalah migrasi lama dan tidak digunakan setelah impor dump terbaru.

```text
Backend/database/wisata_seed.sql
```

Script aman dijalankan ulang: data wisata di-upsert berdasarkan `id_wisata`, sedangkan foto yang cocok diperbarui tanpa menghapus foto lain. Harga yang tidak tersedia pada data frontend disimpan sebagai `0`; alamat rinci, jam operasional, dan koordinat tetap kosong jika tidak tersedia, dan URL peta menggunakan pencarian Google Maps berdasarkan nama/lokasi.

## API Admin

Saat ini API yang tersedia untuk admin adalah login melalui endpoint yang sama dengan login pengguna. Endpoint ini hanya membuat session admin; project belum menyediakan endpoint khusus admin untuk mengelola wisata, kategori, atau akun.

### POST /Backend/api/fungsi_login.php

Kirim data sebagai form (`application/x-www-form-urlencoded` atau `multipart/form-data`), bukan JSON. Gunakan `role=admin`, username, dan password.

Contoh request:

```text
POST http://localhost/eksplorMajaKu/Backend/api/fungsi_login.php
Content-Type: application/x-www-form-urlencoded

role=admin&username=admin&password=kata-sandi
```

Contoh respons `200 OK`:

```json
{
  "status": "success",
  "message": "Login admin berhasil.",
  "data": {
    "id_admin": 1,
    "nama": "Administrator",
    "username": "admin",
    "role": "admin"
  }
}
```

Login sukses membuat session PHP (`PHPSESSID`) dan mengembalikan cookie session. Simpan/kirim cookie tersebut pada request berikutnya. Respons login juga menyertakan token `data.csrf` untuk header `X-CSRF-Token` pada endpoint yang mengubah data. Status umum: `401` untuk username atau password salah, `422` untuk input atau role yang tidak valid, dan `500` untuk kesalahan server. Password admin yang masih tersimpan sebagai teks biasa akan diubah menjadi hash setelah login berhasil.

Logout menggunakan `POST /Backend/api/logout.php` dengan token `csrf` yang sama dengan `$_SESSION["csrf"]`; endpoint memvalidasi token, menghapus session, lalu mengarahkan ke halaman login. Endpoint logout ini bukan API JSON.
