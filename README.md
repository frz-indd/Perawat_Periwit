# Explore MajaKu — Platform Informasi & Eksplorasi Wisata Majalengka

**Explore MajaKu** adalah platform berbasis web untuk mengeksplorasi destinasi wisata di Majalengka dan Kuningan. Platform ini memudahkan pengunjung dalam mencari destinasi wisata, melihat detail lokasi beserta peta koordinat, membaca dan memberikan rating/ulasan, serta menyimpan destinasi favorit, dilengkapi dengan panel administrasi berbasis hak akses (*Role-Based Access Control*) untuk pengelolaan konten wisata.

---

## Fitur Utama

### Pengunjung & Pengguna (User)
* **Akses Publik:** Eksplorasi halaman Beranda, daftar Kategori Wisata, Detail Wisata (deskripsi, jam operasional, hari operasional, dan lokasi peta), serta halaman Tentang Web tanpa harus masuk (*login*).
* **Pencarian & Filter:** Pencarian destinasi wisata secara cepat berdasarkan kata kunci dan kategori.
* **Autentikasi & Pemulihan Akun:** Registrasi dan Login manual (dilengkapi fitur *Remember Me*), *Single Sign-On* (SSO) menggunakan akun Google OAuth, serta fitur **Lupa Password User** melalui verifikasi token email.
* **Interaksi Pengguna:** Memberikan rating bintang dan ulasan destinasi (1–500 karakter), menyimpan destinasi ke daftar Favorit (*Bookmark*), serta manajemen profil pengguna.

### Panel Administrator (Admin)
* **Otorisasi & Keamanan:** Proteksi rute khusus `/admin` menggunakan *Role-Based Access Control* (RBAC), Login Admin, dan fitur **Lupa Password Admin** via email.
* **Dashboard Statistik:** Ringkasan jumlah destinasi wisata, kategori wisata, dan total ulasan pengguna.
* **Manajemen Data Wisata (CRUD):** Tambah, ubah, detail, dan hapus data wisata beserta deskripsi, jam & hari operasional, serta pembaruan koordinat lokasi peta.
* **Manajemen Kategori & Moderasi Ulasan:** Pengelolaan kategori wisata serta moderasi ulasan pengguna.

---

## Teknologi yang Digunakan

* **Frontend:** HTML5, CSS3, Tailwind CSS, JavaScript
* **Backend & Database:** PHP Native (MySQLi) / RESTful API, MySQL, Google OAuth 2.0, SMTP Email Service
* **UI/UX Design & Prototyping:** Figma, Draw.io
* **Manajemen Proyek & Pengujian (SQA):** Trello, Corrello, Qase.io, Postman

---

## Panduan Instalasi dan menjalankan

**1. Clone Repository**

git clone [https://github.com/nur545315-oss/explore-majaku.git](https://github.com/nur545315-oss/explore-majaku.git)
cd explore-majaku

**2. Konfigurasi Database**
* Buat database baru di MySQL (misalnya db_explore_majaku).
* Impor file skema SQL yang berada di folder database/ ke dalam database tersebut.

**3. Konfigurasi Environment**
Sesuaikan pengaturan koneksi database (DB_HOST, DB_USER, DB_PASS, DB_NAME), kredensial Google OAuth, serta konfigurasi SMTP email (untuk fitur Lupa Password) di dalam folder config/.

**4. Jalankan Server Lokal**
* Pindahkan folder proyek ke direktori web server lokal (htdocs pada XAMPP atau www pada Laragon), lalu jalankan layanan Apache dan MySQL.
* Buka browser dan akses http://localhost/explore-majaku.

**Tim Pengembang (Kelompok Perawat Periwit)**
* Nurkhotimah (Project Manager/Scrum Master)
* Ummu Badriyah Mualifah (UI/UX Designer)
* Rifa'ah Fatihatu Sa'adah (Frontend Developer)
* Tiara Salsabila (Frontend Developer)
* Mohammad Fariz Rachman Pratama (Backend Developer)
* Luthfi Baihaqi (Software Quality Assurance)

## Struktur Direktori Proyek

```text
explore-majaku/
├── admin/                  # Halaman & modul Dashboard Admin (RBAC /admin)
├── api/                    # Endpoint REST API (Auth, Wisata, Kategori, Ulasan, Favorit)
├── assets/                 # Aset statis (CSS, JavaScript, ikon, gambar)
├── config/                 # Konfigurasi koneksi database, SMTP, & Google OAuth
├── includes/               # Komponen UI reusable (navbar, footer, middleware auth)
├── pages/                  # Halaman publik & pengguna (beranda, detail, profil, login, reset password)
├── database/               # Skema SQL & file migrasi database
└── index.php               # Entry point utama aplikasi
