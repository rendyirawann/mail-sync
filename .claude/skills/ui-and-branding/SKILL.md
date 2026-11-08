---
name: ui-and-branding
description: Use when building or changing UI in Mail Sync — layouts, components, logo/favicon, brand colors, navbar/sidebar/footer, Vite assets, or when a new CDN/external domain must be allowed in the CSP without breaking assets.
---

# UI & Branding (Mail Sync)

## Layout

| Area | Layout | Aset |
| --- | --- | --- |
| Publik | `resources/views/frontend/layout.blade.php` | Tailwind 4 via `@vite(['resources/css/app.css'])` |
| Auth | `resources/views/auth/app.blade.php` | Metronic `public/assets/css/app-auth.css` |
| Panel | `resources/views/backend/layout/app.blade.php` (+ `navbar`, `sidebar`, `footer`) | Metronic `public/assets/*` |
| Error | `resources/views/errors/layout.blade.php` | CSS inline (mandiri) |

Semua layout meng-include `partials.seo` di `<head>`.

## Brand

- Nama: selalu `config('app.name')` atau `$brand['name']` (bisa diubah admin di Pengaturan). Jangan hardcode.
- Warna: primary `#1D4ED8`, secondary `#0E7490` (`config/branding.php`, Tailwind `brand-*`/`accent-*` di `resources/css/app.css`). Teks putih di atas primary lolos WCAG AA (6.7:1).
- Logo: `public/logo.svg` (ikon+teks), `logo-icon.svg`, `logo-light.svg` (latar gelap). Favicon set + `og-image.png` + `site.webmanifest` di `public/`.
- Membuat ulang semua aset: `php artisan app:branding --apply` (atau tombol di Pengaturan). Generator: `app/Services/BrandingService.php`. URL di view lewat `$brand['logo_url']`, `$brand['favicon_url']`, dst. (sudah `?v=filemtime`).
- Footer: `&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.`

## Menambah CDN / domain eksternal

1. Utamakan aset lokal atau paket npm via Vite.
2. Bila harus CDN: tambahkan origin ke `SecurityHeaders::CDN_HOSTS` (`app/Http/Middleware/SecurityHeaders.php`) atau `.env` `SECURITY_CSP_EXTRA_HOSTS="https://host"`.
3. Sertakan `integrity` + `crossorigin="anonymous"` pada `<script>`/`<link>`.
4. Buka halaman, cek console: tidak boleh ada "Refused to load ... Content Security Policy". Debug sementara: `SECURITY_CSP_REPORT_ONLY=true`.
5. Embed iframe (YouTube/Maps): tambahkan ke `frameHosts()`.

## Checklist

- [ ] Gambar punya `alt`, `width`, `height`; di bawah fold `loading="lazy"`
- [ ] Satu `<h1>` per halaman
- [ ] `npm run build` sukses, tidak ada 404 aset
- [ ] Tidak ada CSP violation di console

## Hindari

- Nonce/`strict-dynamic` di CSP (mematikan inline script tema), menghapus `'unsafe-inline'`, mengedit file vendor tema di `public/assets/plugins`.
