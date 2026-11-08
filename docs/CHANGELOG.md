# Changelog

Semua perubahan penting dicatat di sini. Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/), versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [Unreleased]

## [0.1.0] - 2026-11-08

Setup awal project **Mail Sync** dari base starter.

### Added
- Identitas Mail Sync: nama, deskripsi, warna brand (#1D4ED8 / #0E7490), logo & favicon set baru, OG image, web manifest.
- Beranda publik (Tailwind/Vite), `partials/seo.blade.php` (title, meta, canonical, Open Graph, Twitter Card, JSON-LD), `config/seo.php`, robots.txt & sitemap.xml dinamis.
- Halaman error mandiri 400–504, respon JSON error konsisten, terjemahan `lang/id`.
- Anti-spam honeypot + time-trap + Cloudflare Turnstile opsional pada form publik.
- Setup AJAX global (CSRF otomatis, error 422 per field, dialog sesi kedaluwarsa 419).
- Channel log `security` dan pencatatan event autentikasi.
- View `auth/confirm-password` dan `auth/verify-email` yang sebelumnya hilang.
- Laravel Boost (guidelines, skills, MCP), Larastan level 5, Pint, ESLint, Prettier.
- Dokumentasi `docs/`, `CLAUDE.md`, dan skill project di `.ai/skills`.

### Fixed
- **Keamanan (kritis)**: IDOR pada profil/keamanan/avatar — user dapat mengubah email & password user lain.
- **Keamanan (kritis)**: login sosial memberi peran pertama (Superadmin) ke akun baru dan mengabaikan saklar registrasi.
- Lupa password tidak lagi membocorkan apakah email terdaftar.
- Pesan exception tidak lagi dikirim ke browser saat `APP_DEBUG=false`.
- `TRUSTED_PROXIES` kini terbaca setelah `config:cache`.
- Route `change.password` yang mengarah ke method tidak ada (500).
- Resource route profil/keamanan dibatasi ke method yang ada.

### Changed
- CSP dilonggarkan secara terarah (CDN terdeteksi, Reverb, Vite dev); `Cross-Origin-Resource-Policy` dihapus; `Permissions-Policy` tidak lagi mematikan kamera/mikrofon/lokasi.
- Upload logo/favicon menolak SVG dan memakai nama file acak.
- Kebijakan password: huruf besar-kecil wajib di semua environment.

### Security
- `composer audit` & `npm audit` bersih (phpspreadsheet, protobuf, livewire, phpseclib, symfony/http-client, picomatch diperbarui).
