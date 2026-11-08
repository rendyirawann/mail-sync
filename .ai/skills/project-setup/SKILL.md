---
name: project-setup
description: Use when this Laravel base folder has been copied to start a NEW project and the user says "jalankan skill project-setup" (or asks to set up / rebrand / harden the project). Runs the full idempotent setup — identity detection, rename, logo/favicon, security headers, rate limits, anti-injection/CSRF/spam/defacement, SEO, error pages, OWASP review, code standards, docs, Boost, CLAUDE.md & skills, and verification.
---

# Project Setup (idempoten)

Skill ini adalah prompt setup lengkap (bagian 0–16). Aman dijalankan ulang: **setiap langkah dimulai dengan memeriksa keadaan saat ini** dan hanya mengubah yang belum sesuai. Jangan membuat ulang file/middleware yang sudah benar.

## Peta base ini (sudah tersedia — perbaiki, jangan duplikasi)
| Kebutuhan | Lokasi |
| --- | --- |
| Header keamanan + CSP | `app/Http/Middleware/SecurityHeaders.php`, `config/security.php` |
| Blokir URL mencurigakan | `app/Http/Middleware/BlockSuspiciousRequests.php` |
| Anti-spam | `app/Http/Middleware/ProtectAgainstSpam.php` (alias `spam`), `<x-honeypot />` |
| Rate limiter | `AppServiceProvider::configureRateLimiting()` |
| Trusted proxies | `AppServiceProvider::configureProxies()` ← `config('security.trusted_proxies')` |
| Exception JSON | `bootstrap/app.php` `withExceptions()` |
| Identitas | `App\Support\Brand` (settings DB + config), `config/branding.php`, `config/seo.php` |
| Logo/favicon | `App\Services\BrandingService`, `php artisan app:branding --apply` |
| SEO head | `resources/views/partials/seo.blade.php`; robots/sitemap `Frontend\SeoController` |
| Error pages | `resources/views/errors/*` (layout mandiri) |
| AJAX CSRF/422/419 | `resources/views/partials/ajax-setup.blade.php` |
| IDOR helper | `App\Http\Controllers\Controller::ownAccountOrFail()` |
| Log keamanan | channel `security` di `config/logging.php` |

Catatan: `partials/_rt.blade.php` berisi skrip kredit developer (base64, dialog Ctrl+Shift+Alt+R, tanpa request jaringan). Biarkan kecuali pemilik meminta dihapus.

## Aturan kerja
1. Buat branch `setup/<PROJECT_SLUG>` dan commit kondisi awal (`git init` bila belum repo).
2. Audit dulu (bootstrap/app.php, config/, routes/, Middleware, Providers, views, resources/js, vite.config.js, public/), baru ubah.
3. Jangan hapus fitur/route/logika bisnis. Jangan tampilkan isi `.env`/APP_KEY/secret.
4. Tidak boleh ada white screen, 500, error console, CSP violation, aset 404.
5. Commit per bagian (Conventional Commits). Format Pint dalam commit terpisah `style: apply pint`.
6. Gunakan skill/plugin yang terpasang (superpowers, frontend-design, webapp-testing, semgrep, supply-chain-risk-auditor, skill-creator, Boost). Bila tidak ada, lanjut tanpa.
7. Di Windows: `php artisan tinker` meminta konfirmasi trust interaktif — gunakan skrip PHP yang mem-bootstrap aplikasi (`require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();`).

## 0. Deteksi identitas (tanpa bertanya kecuali mustahil)
- Folder root → `PROJECT_NAME` (Title Case), `PROJECT_SLUG` (kebab), `PROJECT_SHORT` (1–3 huruf), `DB_DATABASE` (snake). `APP_URL` dipertahankan.
- `PROJECT_DESCRIPTION` (Bahasa Indonesia, ≤155 karakter) + 5–10 keyword dari README, routes, Models, Controllers, migration, menu; bila masih template → dari arti nama.
- Pilih `WARNA_UTAMA` (kontras teks putih ≥ 4.5:1) & `WARNA_SEKUNDER`. Tampilkan hasil deteksi lalu lanjut.

## 1. Rename & identitas
- Grep case-insensitive nama lama (nama project sebelumnya, "StarterTemp", "Laravel" di title/meta/footer). Ganti.
- `.env` & `.env.example`: `APP_NAME`, `APP_DESCRIPTION`, `DB_DATABASE`, `MAIL_FROM_NAME="${APP_NAME}"`, `SESSION_COOKIE=<slug>_session`, `CACHE_PREFIX=<slug>_cache`, `SEO_KEYWORDS`, `BRAND_PRIMARY_COLOR`, `BRAND_SECONDARY_COLOR`, `BRAND_SHORT`. `.env.example` tanpa secret.
- `php artisan key:generate --force`; REVERB_APP_ID/KEY/SECRET acak baru.
- composer.json name/description, package.json name, README, fallback `config/app.php`.
- **Update tabel `settings`**: `site_name`, `site_short_name`, `site_tagline`, `site_description`, `site_keywords`, `site_theme_color` (Brand memprioritaskan nilai DB!).
- Footer: `&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.`

## 2. Logo & favicon
- Set `BRAND_*` di `.env`, jalankan `php artisan app:branding --apply` → `public/logo.svg`, `logo-icon.svg`, `logo-light.svg`, `favicon.ico|svg`, `favicon-32x32.png`, `apple-touch-icon.png`, `android-chrome-192x192.png`, `android-chrome-512x512.png`, `og-image.png`, `site.webmanifest`. Lihat hasil PNG untuk memastikan rapi.
- Referensi lewat `$brand[...]` (sudah `?v=filemtime`). Hapus aset lama yang tidak dipakai.

## 3. Security headers (grade A, tidak terlalu ketat)
- CSP: `default-src 'self'`; script `'self' 'unsafe-inline'` + CDN terdeteksi (+`'unsafe-eval'` bila Alpine/Vue runtime); style `'self' 'unsafe-inline' https:`; img/media `data: blob: https:`; font `data: https:`; connect `'self'` + Reverb ws/wss host:port + CDN; frame `'self'` + embed; `frame-ancestors/base-uri/form-action 'self'`; `object-src 'none'`; local + Vite dev 5173. Scan blade/js/css untuk domain eksternal. Tanpa nonce/strict-dynamic. `SECURITY_CSP_REPORT_ONLY` tersedia.
- nosniff, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, Permissions-Policy hanya fitur yang jelas tak dipakai, HSTS hanya `SECURITY_HSTS=true` + HTTPS, hapus X-Powered-By. Tanpa COEP/COOP/CORP ketat. Berlaku juga di error & JSON.

## 4. Rate limit
- Limiter `web`, `login` (email+IP), `password`, `write`, `api` dari `RATE_LIMIT_*`; per user ID bila login. robots/sitemap/health/aset tidak kena limit. 429 dengan `Retry-After`.

## 5. Anti injection
- Audit `DB::raw|whereRaw|selectRaw|orderByRaw|DB::statement` (binding), whitelist sort/filter, `{!! !!}`, `$fillable`, upload (mimes+max, nama acak, tolak php/phtml/svg/html, `.htaccess` no-exec). `BlockSuspiciousRequests` hanya URL/query.

## 6. Anti 419
- Semua form tulis punya `@csrf` (+`@method`); `<meta name="csrf-token">` di semua layout; AJAX global; `APP_URL`/`SESSION_DOMAIN=null`/`SESSION_SECURE_COOKIE` konsisten (peringatkan localhost vs 127.0.0.1); tabel `sessions`; dialog 419.

## 7. Anti spam
- `<x-honeypot />` + middleware `spam` + throttle pada login/register/lupa/reset/kontak; Turnstile di balik `TURNSTILE_ENABLED`.

## 8. Anti defacement
- Document root `public/`, `.htaccess` (Options -Indexes, tolak dotfile/composer/log), root `.htaccess` pengaman, semua route ubah data ber-auth + otorisasi (laporkan yang tidak), cek IDOR (`findOrFail($id)` tanpa scope!), cek peran default login sosial, catatan production di `.env.example`, SRI untuk CDN.

## 9. SEO
- `config/seo.php` + `partials/seo.blade.php` di semua layout; title "Halaman | Nama" / beranda "Nama — tagline"; description ≤155; keywords, author, theme-color, canonical tanpa tracking, OG, Twitter, JSON-LD Organization+WebSite(+WebApplication); robots.txt & sitemap.xml dinamis (hapus `public/robots.txt` statis); noindex untuk auth/admin/error & seluruh situs non-production; satu h1, alt, lazy, width/height; beranda publik bila belum ada.

## 10. Error pages & validasi
- Layout error mandiri; 400–504 Bahasa Indonesia; 4xx tampilkan pesan `abort()` (bukan pesan bawaan framework); 5xx tanpa detail; 429 dari Retry-After.
- Form web: `@error` + `<x-field-error>` + ringkasan; JSON 422 `{status,message,errors}`; `lang/id/*` lengkap; `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`.

## 11. OWASP Top 10 / ASVS L2
- A01 IDOR & policy; A02 encrypted cast + `$hidden` + cookie aman; A03; A04 konfirmasi + signed URL; A05 debug off, `env()` hanya di config, `.env` tak ter-commit; A06 `composer audit`/`npm audit`; A07 `Password::defaults()` (min 8, mixed case, angka, uncompromised di prod), regenerate/invalidate sesi, pesan generik; A08 SRI, lock file; A09 channel `security` 30 hari; A10 SSRF. `preventLazyLoading` & `preventSilentlyDiscardingAttributes` di non-production. Tulis `docs/SECURITY.md`.

## 12. Standar kode
- Deteksi versi stack; Pint (`pint.json` preset laravel, commit terpisah); Larastan level 5 (`phpstan.neon`) — perbaiki tanpa ubah perilaku, tanpa ignore; ESLint + Prettier untuk `resources/js`; hapus `console.log`. Arsitektur & penamaan sesuai skill `code-standards`.

## 13. Dokumentasi
- PHPDoc Bahasa Indonesia; komentar `.env.example`; README; `docs/ARCHITECTURE.md`, `DATABASE.md` (Mermaid ERD), `SECURITY.md`, `DEPLOYMENT.md`, `API.md` (bila ada routes/api.php), `CHANGELOG.md` (Keep a Changelog).

## 14. Laravel Boost & plugin
- `composer require laravel/boost --dev`. Pastikan `boost.json` berisi `"agents": ["claude_code"]` (tanpa itu, mode non-interaktif memilih Cursor), lalu `php artisan boost:install --guidelines --skills --mcp --no-interaction`. `.mcp.json` harus berisi `laravel-boost`.
- Guidelines project di `.ai/guidelines/*.md`, skill di `.ai/skills/<nama>/SKILL.md`, lalu `php artisan boost:update`.
- Laporkan plugin: superpowers, example-skills (anthropics/skills), semgrep, supply-chain-risk-auditor, ask-questions-if-underspecified + perintah `/plugin` untuk yang belum. Jangan pasang dari sumber lain tanpa izin.

## 15. CLAUDE.md & skill
- Bagian project di LUAR blok `<laravel-boost-guidelines>` (±100–150 baris). Skill: laravel-feature, code-standards, security-review, validation-and-errors, ui-and-branding, seo-page, testing, deployment, project-setup (frontmatter `name` + `description`). Perbarui nama/path/warna di semua skill untuk project baru.
- `.gitignore`: commit CLAUDE.md, .ai/, .claude/skills/, .claude/settings.json, .mcp.json; abaikan `.claude/settings.local.json`.

## 16. Verifikasi (wajib)
1. `composer install`, `npm install`, `npm run build`.
2. `php artisan optimize:clear` → `config:cache`, `route:cache`, `view:cache` → `optimize:clear`.
3. `php artisan migrate --pretend`, `php artisan route:list`.
4. `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`.
5. `composer audit`, `npm audit --omit=dev` (+ semgrep).
6. `php artisan test` (headers, error pages, 422 web & JSON, @csrf, honeypot, guest 302/401, 403, robots & sitemap 200, meta SEO beranda).
7. `php artisan serve --port=8881` + `curl -I` beranda, aset build, favicon, sitemap.
8. Browser: beranda, login, dashboard tanpa error console/CSP.
9. Uji tiap kode error lewat route sementara lalu hapus.
10. Grep ulang nama lama, logo lama, secret.
11. Validasi SKILL.md (frontmatter, path ada, muncul di `.claude/skills`), link di CLAUDE.md & docs.
12. Laporan akhir: identitas, file per bagian, temuan keamanan + perbaikan, hasil pint/phpstan/audit/test, skill, status plugin, tindakan manual (php.ini, web server, HTTPS, Cloudflare, Search Console).
