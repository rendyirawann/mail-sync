# Mail Sync

Mail Sync — aplikasi web untuk menyinkronkan, mengelola, dan memantau email antar akun dalam satu panel admin yang aman.

## Fitur utama

- **Beranda publik** yang bisa diindeks mesin pencari (SEO, Open Graph, JSON-LD, sitemap).
- **Panel admin** (`/admin`) dengan dashboard, statistik aktivitas, dan notifikasi real-time (Reverb).
- **Manajemen pengguna & peran** (Spatie Permission): buat/ubah/hapus user, ban/unban, generate permission dari route.
- **Akun saya**: profil, foto, ganti password, riwayat sesi login, logout perangkat lain.
- **Log aktivitas** (Spatie Activitylog) dengan diff sebelum–sesudah.
- **Pengaturan aplikasi**: nama, logo, favicon, SEO, login sosial (Google/Facebook/GitHub/LinkedIn), mode pemeliharaan.
- **Keamanan berlapis**: security headers + CSP, rate limiting, honeypot & time-trap anti-spam, log keamanan terpisah.

## Tech stack

| Komponen | Versi |
| --- | --- |
| PHP | ^8.2 (diuji di 8.4) |
| Laravel | 12.x |
| Database | PostgreSQL (pgsql) — MySQL/SQLite juga didukung Laravel |
| Frontend panel | Blade + tema Metronic (Bootstrap 5, jQuery, DataTables) di `public/assets` |
| Frontend publik | Blade + Tailwind CSS 4 via Vite 7 |
| Realtime | Laravel Reverb + Laravel Echo |
| Paket utama | spatie/laravel-permission, spatie/laravel-activitylog, laravel/socialite, yajra/laravel-datatables, cybercog/laravel-ban |
| Kualitas kode | Laravel Pint, Larastan (level 5), ESLint, Prettier |
| AI tooling | Laravel Boost (guidelines, skills, MCP) |

## Kebutuhan sistem

- PHP 8.2+ dengan ekstensi: `pdo_pgsql`, `gd` (wajib, untuk generator logo), `intl`, `mbstring`, `openssl`
- Composer 2
- Node.js 20+ dan npm
- PostgreSQL 14+

## Instalasi

```bash
git clone <repo> mail-sync && cd mail-sync
composer install
npm install
cp .env.example .env
php artisan key:generate
# isi DB_*, REVERB_APP_ID/KEY/SECRET (acak), MAIL_* di .env
php artisan migrate --seed
php artisan storage:link
php artisan app:branding --apply   # logo, favicon, OG image dari APP_NAME & BRAND_* colors
npm run build
```

## Konfigurasi `.env` penting

| Key | Fungsi |
| --- | --- |
| `APP_NAME`, `APP_DESCRIPTION` | Identitas aplikasi & meta description default |
| `APP_URL` | Harus sama persis dengan host di browser (`127.0.0.1` vs `localhost`) agar tidak 419 |
| `APP_LOCALE=id` | Pesan validasi & error Bahasa Indonesia |
| `SEO_KEYWORDS`, `SEO_OG_IMAGE` | SEO (lihat `config/seo.php`) |
| `BRAND_PRIMARY_COLOR`, `BRAND_SECONDARY_COLOR`, `BRAND_SHORT` | Warna & singkatan logo (lihat `config/branding.php`) |
| `SECURITY_*` | CSP, HSTS, pemblokir URL mencurigakan (lihat `config/security.php`) |
| `RATE_LIMIT_*` | Batas request per menit |
| `TRUSTED_PROXIES` | Isi bila di belakang nginx/Cloudflare |
| `TURNSTILE_*` | Cloudflare Turnstile opsional untuk form publik |
| `PERMISSION_DEFAULT_ROLE` | Peran otomatis untuk user baru dari login sosial (kosong = tanpa peran) |

Setiap key di `.env.example` sudah diberi komentar dan nilai yang disarankan untuk production.

## Menjalankan

```bash
php artisan serve --port=8881     # aplikasi
npm run dev                        # Vite (hot reload)
php artisan reverb:start           # websocket
php artisan queue:work             # job/queue
php artisan schedule:work          # scheduler
# atau sekaligus: composer run dev
```

## Testing & kualitas

```bash
php artisan test                   # PHPUnit
./vendor/bin/pint --test           # format PSR-12 (preset laravel)
./vendor/bin/phpstan analyse       # Larastan level 5
npm run lint && npm run format:check
composer audit && npm audit --omit=dev
```

## Struktur folder

```
app/
  Console/Commands/      app:branding, generate permissions
  Http/Controllers/      Auth/, Backend/ (panel), Frontend/ (publik + robots/sitemap)
  Http/Middleware/       SecurityHeaders, BlockSuspiciousRequests, ProtectAgainstSpam, SanitizeInput, CheckMaintenanceMode
  Services/              BrandingService (generator logo/favicon/OG)
  Support/               Brand (identitas per request), ActivityRecorder, ActivityDiff
config/                  security.php, seo.php, branding.php (+ config Laravel standar)
lang/id/                 pesan validasi, auth, password (Bahasa Indonesia)
resources/views/
  frontend/              layout & beranda publik (Tailwind/Vite)
  auth/                  login, register, lupa/reset password, verifikasi
  backend/               panel admin (Metronic)
  errors/                halaman error mandiri (400–504)
  partials/seo.blade.php blok <head> SEO untuk semua layout
  components/            <x-honeypot />, <x-field-error />, <x-avatar />
docs/                    dokumentasi teknis
.ai/                     guidelines & skill Claude (sumber), disinkronkan oleh Boost
```

## Skill & plugin Claude

- **Laravel Boost**: guidelines + skills (`.claude/skills`) + MCP `laravel-boost` (`.mcp.json`). Sinkronkan: `php artisan boost:update`.
- **Skill project** (`.ai/skills/`): `laravel-feature`, `code-standards`, `security-review`, `validation-and-errors`, `ui-and-branding`, `seo-page`, `testing`, `deployment`, `project-setup`.
- Plugin eksternal yang disarankan: superpowers, example-skills (anthropics/skills), semgrep, supply-chain-risk-auditor, ask-questions-if-underspecified. Lihat [CLAUDE.md](CLAUDE.md).

## Dokumentasi

- [Arsitektur](docs/ARCHITECTURE.md)
- [Database](docs/DATABASE.md)
- [Keamanan](docs/SECURITY.md)
- [Deployment](docs/DEPLOYMENT.md)
- [API](docs/API.md)
- [Changelog](docs/CHANGELOG.md)
