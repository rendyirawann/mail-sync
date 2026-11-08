# API Mail Sync

`routes/api.php` sudah disiapkan tetapi **belum memiliki endpoint**. Semua route di file ini otomatis:

- berprefiks `/api`,
- melalui rate limiter `api` (`RATE_LIMIT_API`, default 60/menit per user atau IP),
- melalui `SanitizeInput`,
- dikenai CORS dari `config/cors.php` (`CORS_ALLOWED_ORIGINS`).

## Format respon

Sukses (gunakan Eloquent API Resource):

```json
{ "data": { "id": "…", "name": "…" } }
```

Error (otomatis dari `bootstrap/app.php`):

```json
{ "status": "error", "message": "Data yang dikirim tidak valid.", "errors": { "email": ["Email wajib diisi."] } }
```

| Kode | Arti |
| --- | --- |
| 401 | Belum login / token tidak valid |
| 403 | Tidak punya izin |
| 404 | Data tidak ditemukan |
| 419 | Sesi/CSRF kedaluwarsa (request stateful) |
| 422 | Validasi gagal (`errors` per field) |
| 429 | Terlalu banyak request (lihat header `Retry-After`) |
| 500 | Kesalahan server (tanpa detail di production) |

## Menambah endpoint

Ikuti skill `laravel-feature`: FormRequest untuk validasi, Policy untuk otorisasi, API Resource untuk respon, lalu dokumentasikan endpoint (method, URL, auth, contoh request/response) di file ini.
