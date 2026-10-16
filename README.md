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