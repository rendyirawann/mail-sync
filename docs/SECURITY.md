# Keamanan Mail Sync

Acuan: OWASP Top 10 (2021) dan OWASP ASVS Level 2.

## Melaporkan celah keamanan

Jangan membuka issue publik. Kirim laporan ke pemilik project (email pengelola repo) berisi: langkah reproduksi, dampak, dan versi/commit. Kami merespons dalam 3 hari kerja.

## Kontrol per kategori

### A01 — Broken Access Control
- Semua route panel di grup `auth` + `forbid-banned-user`; user & peran & pengaturan memakai `can:view_resources`. Superadmin lolos semua gate via `Gate::before` (`AppServiceProvider`).
- **IDOR**: route "akun saya" (`/admin/my-profile/{id}`, `/admin/my-security/{id}`, `/admin/my-account/{id}/avatar`) memanggil `Controller::ownAccountOrFail($id)` → 403 + log bila bukan akun sendiri.
- Log aktivitas di-scope ke `causer_id` user untuk non-Superadmin.
- Login sosial **tidak** pernah memberi peran Superadmin; peran default dari `PERMISSION_DEFAULT_ROLE` dan pendaftaran mengikuti saklar *Izinkan registrasi*.

### A02 — Cryptographic Failures
- Password: cast `hashed` + `Hash::make`. Atribut `password`, `remember_token` di `$hidden`.
- Secret OAuth di tabel `settings` dienkripsi `Crypt::encryptString` (APP_KEY).
- Cookie sesi: HttpOnly, `SameSite=lax`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true` di HTTPS.
- **Rekomendasi**: kolom `users.nik` belum dienkripsi karena dipakai pencarian ILIKE di daftar user. Bila mulai diisi data asli, pindahkan ke cast `encrypted` + kolom hash terpisah untuk pencarian.

### A03 — Injection
- Query memakai Eloquent/binding; satu-satunya `DB::raw` statis (`COUNT(*)`).
- `{!! !!}` hanya untuk SVG ikon statis (login sosial) dan JSON-LD (`json_encode` + `JSON_HEX_TAG`).
- Upload: validasi `mimes` + `max`, nama file acak, ekstensi dari MIME. SVG ditolak (bisa membawa script). Folder upload publik punya `.htaccess` yang mematikan eksekusi PHP/HTML/SVG.
- `BlockSuspiciousRequests` hanya memeriksa URL/query string — body form tidak pernah diblokir.

### A04 — Insecure Design
- Aksi berbahaya (hapus massal, ban, generate permission) membutuhkan konfirmasi SweetAlert, `can:view_resources`, rate limit `write`, dan tercatat di activity log.
- Verifikasi email memakai signed URL (`signed` middleware).

### A05 — Security Misconfiguration
- Header: CSP (longgar, tanpa nonce), `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS (opsional), `X-Robots-Tag` (non-production/admin/error). Lihat `app/Http/Middleware/SecurityHeaders.php` dan `config/security.php`.
- `X-Powered-By` dihapus; set juga `expose_php=Off` di php.ini.
- Detail exception hanya muncul bila `APP_DEBUG=true`. `env()` hanya dipakai di `config/`.
- `.env` di-`.gitignore`; `public/.htaccess` menolak dotfile, file composer/package, log, dan directory listing.

### A06 — Vulnerable Components
- `composer audit` & `npm audit` bersih saat setup (lihat CHANGELOG). Jalankan sebelum setiap rilis.

### A07 — Identification & Authentication
- `Password::defaults()`: min 8, huruf besar-kecil, angka; `uncompromised()` di production.
- `session()->regenerate()` setelah login; `invalidate()` + `regenerateToken()` saat logout.
- Pesan login & lupa password generik (tidak membocorkan email terdaftar).
- Throttle login per email+IP dan per IP; token reset kedaluwarsa 60 menit.
- Anti-spam: honeypot + time-trap (< 3 detik) + Turnstile opsional (`ProtectAgainstSpam`).

### A08 — Software & Data Integrity
- `composer.lock` & `package-lock.json` di-commit. CSRF aktif untuk semua route web; pengecualian hanya untuk webhook bertanda tangan.
- Aset tema disajikan lokal (bukan CDN), sehingga SRI tidak diperlukan. Bila menambah `<script src="https://cdn...">`, sertakan `integrity` + `crossorigin="anonymous"`.

### A09 — Logging & Monitoring
- Channel `security` (`storage/logs/security-*.log`, harian, retensi 30 hari): login berhasil/gagal (email di-mask), logout, lockout, reset password, 403, percobaan IDOR, spam, login sosial ditolak.
- Tidak pernah mencatat password, token, isi `.env`.

### A10 — SSRF
- Tidak ada fitur fetch URL dari input user. Satu-satunya request keluar: verifikasi Turnstile ke URL tetap Cloudflare.

### Mass assignment
- `$fillable` eksplisit di semua model. `Model::preventSilentlyDiscardingAttributes()` + `preventLazyLoading()` aktif di non-production (dicatat ke log, tidak melempar exception).

## Checklist sebelum merge

Gunakan skill `security-review` (`.ai/skills/security-review/SKILL.md`).
