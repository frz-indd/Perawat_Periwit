# Security test report – ADMIN-08 hardening

Tanggal paket: 12 November 2026  
Metode: static source review + local smoke tests. Bukan penetration test eksternal.

## Hasil yang benar-benar dijalankan

| Pemeriksaan | Hasil |
|---|---|
| `php -l` seluruh file PHP | Lulus, 51 file, 0 syntax error |
| JWT secret tidak dikonfigurasi | Lulus, fungsi menolak secret kosong |
| JWT valid HS256 | Lulus |
| JWT expired | Lulus, ditolak |
| JWT tanpa klaim `exp` | Lulus, ditolak |
| JWT signature dimodifikasi | Lulus, ditolak |
| Postman Collection JSON | Lulus parsing |
| OpenAPI YAML | Lulus parsing |
| ZIP integrity | Dijalankan saat packaging |

Smoke test dapat dijalankan dengan:

```bash
php Backend/tests/security_smoke_test.php
```

## Belum dapat diverifikasi tanpa staging/database

- Rate limit benar-benar mengunci setelah 5 kegagalan dan pulih setelah 15 menit.
- Password change benar-benar mencabut semua record sesi admin pada database target.
- Endpoint file SQL/config mengembalikan 403/404 pada konfigurasi hosting sesungguhnya.
- Eksekusi script di folder upload ditolak oleh web server.
- Header keamanan benar-benar dikirim oleh Apache/proxy hosting.
- Alur login, profil, dashboard, dan endpoint lain tetap kompatibel dengan data database produksi.

Jalankan test case pada `SECURITY-HARDENING.md` di staging sebelum deploy. Pastikan migration dijalankan dan backup database tersedia.
