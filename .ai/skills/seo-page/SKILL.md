---
name: seo-page
description: Use when creating a public (indexable) page in Mail Sync or adjusting SEO — title, meta description, canonical, Open Graph, JSON-LD, sitemap registration, robots rules, and deciding when a page must be noindex.
---

# SEO Page (Mail Sync)

## Langkah membuat halaman publik
1. Controller di `app/Http/Controllers/Frontend/` (contoh: `HomeController`).
2. Route GET publik di `routes/web.php` dengan nama, mis. `Route::get('/fitur', FeatureController::class)->name('features');`.
3. View extends `frontend.layout`:
   ```blade
   @extends('frontend.layout')
   @section('title', 'Fitur')                       {{-- → "Fitur | Mail Sync" (maks ±60 karakter) --}}
   @section('meta_description', 'Deskripsi unik maks 155 karakter.')
   @section('og_image', asset('og-image.png'))      {{-- opsional --}}
   @section('content') <h1>…</h1> @endsection
   ```
4. Daftarkan nama route di `config/seo.php` → `sitemap_routes`, lalu `php artisan cache:clear` (sitemap dicache 1 jam).
5. Bila menambah area privat baru, tambahkan prefix ke `SeoController::DISALLOW` dan `config/seo.php` → `noindex_paths`.

## Yang sudah otomatis (`resources/views/partials/seo.blade.php`)
- `<title>`, meta description/keywords/author/theme-color, canonical tanpa `utm_*`/`fbclid`/`gclid`.
- Open Graph (`og:locale=id_ID`), Twitter `summary_large_image`, JSON-LD Organization + WebSite + WebApplication.
- `noindex, nofollow` (meta + header `X-Robots-Tag`) untuk: semua halaman bila `APP_ENV` ≠ production, path admin/auth, dan semua respon error.

## Kapan wajib noindex
- Halaman auth, panel `/admin/*`, error, hasil pencarian internal, halaman dengan data pribadi. Paksa per halaman: `@section('robots', 'noindex, nofollow')`.

## Checklist
- [ ] Satu `<h1>`, heading berurutan
- [ ] `<img>` punya `alt` bermakna, `width`/`height`, `loading="lazy"` di bawah fold
- [ ] `curl http://127.0.0.1:8881/sitemap.xml` memuat URL baru
- [ ] Test feature: status 200 + `assertSee('<meta name="description"', false)`

## Hindari
- Deskripsi duplikat antar halaman, title > 60 karakter, file `public/robots.txt` statis (akan menimpa route dinamis).
