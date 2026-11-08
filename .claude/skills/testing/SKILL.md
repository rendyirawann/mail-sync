---
name: testing
description: Use when writing or running tests for Mail Sync — PHPUnit feature/unit test patterns, factories, database setup, how to run the suite, and the minimum coverage expected for each feature.
---

# Testing (Mail Sync)

## Menjalankan

```bash
php artisan test                         # semua
php artisan test --filter=SecurityHeaders
php artisan test --parallel              # lebih cepat
```
`phpunit.xml` memakai SQLite in-memory, sehingga test tidak menyentuh database PostgreSQL lokal.

## Pola

- Feature test di `tests/Feature`, pakai `RefreshDatabase`.
- User: `User::factory()->create()`. Peran Spatie: `Role::create(['name' => 'Superadmin'])` lalu `$user->assignRole('Superadmin')`. Permission: `Permission::create(['name' => 'view_resources'])`.
- Form publik dengan anti-spam: kirim field time-trap yang valid:
  ```php
  $payload['_hp_time'] = Crypt::encryptString((string) (time() - 10));
  ```
- Contoh nyata: `tests/Feature/SecurityHeadersTest.php`, `tests/Feature/ErrorPagesTest.php`, `tests/Feature/AntiSpamTest.php`, `tests/Feature/AccessControlTest.php`, `tests/Feature/SeoTest.php`, `tests/Feature/ValidationTest.php`.

## Target minimal per fitur

- [ ] Guest → 302 ke login (web) / 401 (JSON)
- [ ] User tanpa izin → 403; akses data user lain → 403/404 (IDOR)
- [ ] Validasi gagal → 422 JSON & redirect + session errors (web)
- [ ] Jalur sukses
- [ ] Halaman publik: 200 + meta SEO

## Hindari

- Test yang bergantung pada data DB lokal, `sleep()` (pakai `Carbon::setTestNow()` / time-trap dengan timestamp lampau), memanggil layanan eksternal (pakai `Http::fake()`).
