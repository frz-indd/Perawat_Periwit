EXPLORE MAJAKU - KONTRAK API BACKEND
===================================

Base URL
--------
Gunakan URL sesuai nama folder aplikasi di htdocs. Contoh instalasi ini:
http://localhost/Perawat_Periwit

Route bersih (clean URL) di bawah ini diteruskan oleh .htaccess ke file PHP
backend yang disebutkan. Semua endpoint JSON mengirim Content-Type
application/json; endpoint logout dan OAuth dapat mengembalikan redirect.

Autentikasi dan session
-----------------------
- Login menggunakan session PHP dan cookie PHPSESSID.
- Browser harus menyimpan dan mengirim cookie yang sama (same-origin).
- Endpoint perubahan data yang disebutkan di bawah memakai header
  X-CSRF-Token dengan token dari respons login atau session halaman.
- Login admin menggunakan role=admin. Endpoint pengelolaan lokasi mensyaratkan
  session admin; endpoint ulasan mensyaratkan session pengguna.

1. Login pengguna atau admin
----------------------------
POST /Backend/api/fungsi_login.php
Content-Type: application/x-www-form-urlencoded atau multipart/form-data

Pengguna:
  role=user (boleh dihilangkan), email=<email atau nomor telepon>,
  password=<password>

Admin:
  role=admin, username=<username>, password=<password>

Respons sukses 200:
  {"status":"success","data":{"id_user":7,"nama":"Nama","email":"nama@example.com","csrf":"<token>"}}

Login admin mengembalikan id_admin, nama, username, role=admin, dan csrf.
Status umum: 200 sukses; 401 kredensial salah; 404 akun pengguna tidak
ditemukan; 422 input atau role tidak valid; 500 kesalahan server.

2. Registrasi pengguna
----------------------
POST /Backend/api/fungsi_register.php
Content-Type: application/x-www-form-urlencoded atau multipart/form-data

Field:
  nama (opsional), email, password, confirmPassword

Password minimal 8 karakter. Jika nama kosong, backend menggunakan bagian
email sebelum tanda @. Registrasi tidak langsung membuat session login.

Respons sukses 201:
  {"status":"success","data":{"id_user":8,"nama":"Nama","email":"nama@example.com"}}

Status umum: 201 dibuat; 409 email telah digunakan; 422 input tidak valid;
500 kesalahan server.

3. Login Google
---------------
GET /Backend/api/google_login.php
GET /Backend/api/google_login.php?intent=register

Memulai OAuth Google dan mengarahkan browser ke Google. intent=register
memulai alur registrasi. Jika konfigurasi Google belum tersedia, browser
diarahkan kembali ke halaman login/registrasi dengan google=not_configured.

GET /Backend/api/google_callback.php?code=<authorization-code>&state=<state>

Callback OAuth yang dipanggil Google. State harus cocok dengan session yang
dibuat pada saat login dimulai dan hanya dapat digunakan sekali. Status JSON
yang dapat terjadi: 400 state/kode tidak valid; 401 kode ditolak Google;
503 konfigurasi OAuth belum tersedia; selain itu dapat mengarahkan browser ke
halaman login/registrasi.

4. Profil pengguna
------------------
GET /users/me
  Memerlukan session pengguna.
  Respons 200: {"status":"success","data":{"id_user":7,"nama":"Nama","email":"nama@example.com"}}

PUT /users/me
Content-Type: application/json
Accept: application/json
  Memerlukan session pengguna.
Body:
  {"nama":"Nama Baru","email":"baru@example.com"}

Status: 200 berhasil; 400 JSON tidak valid; 401 belum login; 404 pengguna
tidak ditemukan; 405 metode tidak didukung; 409 email sudah dipakai;
415 Content-Type bukan JSON; 422 field tidak valid; 500 kesalahan server.

5. Daftar wisata
----------------
GET /Backend/api/wisata.php
  Mengembalikan daftar wisata beserta kategori, foto, harga, jam, dan objek
  lokasi yang berisi alamat, domisili, latitude, longitude, dan
  koordinat_lengkap.

Parameter opsional untuk jarak pengguna:
  ?latitude=-6.9&longitude=108.4
  Kedua koordinat harus dikirim bersama. Latitude harus berada pada -90..90
  dan longitude pada -180..180. Koordinat pengguna yang tidak lengkap atau
  tidak valid menghasilkan 422.

6. Kategori wisata
------------------
GET /categories
  Mengembalikan kategori aktif.
  Bentuk item: {"id_kategori":1,"nama":"Alam","gambar":"..."}

GET /categories/{id}/wisata
  Mengembalikan kategori dan wisata di kategori aktif tersebut. Item wisata
  berisi id_wisata, nama_wisata, gambar, deskripsi, alamat, wilayah,
  harga_tiket, jam_buka, jam_tutup, latitude, longitude, dan objek lokasi.
  ID bukan bilangan bulat positif menghasilkan 400; kategori tidak ada/tidak
  aktif menghasilkan 404; kategori tanpa wisata tetap menghasilkan 200 dengan
  array wisata kosong.

7. Pencarian destinasi
----------------------
GET /api/destinations/search?q=<kata-kunci>
  q wajib berupa teks minimal 2 karakter. Pencarian mencocokkan nama,
  deskripsi, alamat, domisili, dan kategori. Respons sukses:
  {"status":"success","data":[...]}
  Data menyertakan informasi lokasi. q tidak valid menghasilkan 400 atau 422.

Halaman frontend /search juga memakai parameter filter wilayah[], kategori[],
min, dan max; parameter filter tersebut diproses oleh halaman pencarian.

8. Data lokasi destinasi
------------------------
GET /api/destinations/{id}/location
  Mengembalikan id_wisata, nama_wisata, alamat, latitude, longitude, dan
  koordinat_lengkap. Jika salah satu koordinat kosong atau pasangan tidak
  valid, kedua koordinat dikembalikan sebagai null dan koordinat_lengkap=false.
  Status: 200 sukses; 400 ID tidak valid; 404 destinasi tidak ditemukan.

PUT /api/destinations/{id}/location
Content-Type: application/json
X-CSRF-Token: <token-session-admin>
Cookie: PHPSESSID=<session-admin>
  Hanya admin yang login boleh mengubah lokasi.
Body wajib menyertakan semua field:
  {"alamat":"Alamat wisata","latitude":-6.94333,"longitude":108.43917}

Alamat harus berupa teks maksimal 255 karakter. Latitude dan longitude harus
dikirim berpasangan, berupa angka dalam rentang latitude -90..90 dan longitude
-180..180. Kirim keduanya null jika koordinat belum diketahui.
Status: 200 berhasil; 400 JSON/ID tidak valid; 401 session admin tidak ada;
403 CSRF tidak valid; 404 destinasi tidak ditemukan; 415 Content-Type salah;
422 alamat/koordinat tidak valid atau koordinat tidak lengkap; 500 kesalahan
server.

9. Ulasan destinasi
-------------------
POST /api/destinations/{id}/reviews
Content-Type: application/json
X-CSRF-Token: <token-session-user>
Cookie: PHPSESSID=<session-user>
  Memerlukan session pengguna.
Body:
  {"rating":5,"text":"Tempatnya nyaman dan bersih."}

rating harus bilangan bulat 1..5. text wajib 1..500 karakter; field komentar
juga dapat digunakan sebagai nama alternatif. Respons sukses 201 berisi
id_review, destination_id, rating, text, average_rating, dan review_count.

Implementasi schema saat ini menyimpan ulasan di tabel ulasan dan wisata di
tabel wisata. Kolom wisata.average_rating menyimpan rata-rata terbaru dengan
dua angka desimal. Terapkan migration berikut sebelum memakai endpoint:
  Backend/database/destination_reviews_migration.sql

Status: 201 tersimpan; 400 JSON/ID tidak valid; 401 belum login sebagai
pengguna; 403 CSRF tidak valid; 404 destinasi tidak ditemukan; 405 metode
tidak didukung; 415 Content-Type salah; 422 payload tidak valid; 500
kesalahan server.

10. Logout
----------
POST /Backend/api/logout.php
Content-Type: application/x-www-form-urlencoded
Cookie: PHPSESSID=<session-id>
Body: csrf=<token-session>

Token harus sama dengan token CSRF pada session. Sukses menghapus session dan
mengalihkan ke halaman login (303). Token tidak valid menghasilkan 403; metode
selain POST menghasilkan 405. Endpoint ini bukan respons JSON.

Kolom alamat dan koordinat
--------------------------
Tabel wisata menggunakan kolom alamat, latitude, dan longitude. Untuk database
lama, jalankan:
  Backend/database/destination_location_migration.sql

Collection Postman
------------------
Collection:
  Backend/api/postman/users-me.postman_collection.json

Variabel baseUrl untuk folder project ini:
  http://localhost/Perawat_Periwit

Jika loginEmail/loginPassword atau adminUsername/adminPassword diisi dengan
kredensial valid, collection juga menguji alur login dan akses terautentikasi.
Jika kosong, collection memeriksa validasi login dan penolakan akses tanpa
autentikasi. Jangan commit kredensial.
