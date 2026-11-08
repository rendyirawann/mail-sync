---
name: laravel-feature
description: Use when adding a new module, CRUD, or end-to-end feature to Mail Sync (e.g. "buat modul X", "tambah CRUD akun email"). Covers migration, model, enum, factory, seeder, FormRequest, Policy, Service, controller, routes, views, sidebar menu, SEO, tests, and docs in this project's conventions.
---

# Laravel Feature (Mail Sync)

## Tujuan

Membuat fitur baru yang konsisten dengan arsitektur project: controller tipis, validasi di FormRequest, logika di Service, otorisasi di Policy, UI di tema Metronic panel.

## Langkah

1. **Migration** — `php artisan make:migration create_mail_accounts_table`. Tabel plural snake_case, FK `foreignUuid('user_id')->constrained()->cascadeOnDelete()` (users.id adalah UUID), index kolom filter.
2. **Enum** — status/tipe di `app/Enums/MailAccountStatus.php` (`enum MailAccountStatus: string`), cast di model.
3. **Model** — `app/Models/MailAccount.php`: `$fillable` eksplisit, `$hidden` untuk secret, `casts()` (`'password' => 'encrypted'` untuk kredensial IMAP), relasi bertipe, local scope untuk query berulang.
4. **Factory & seeder** — `database/factories`, daftarkan seeder di `DatabaseSeeder`.
5. **FormRequest** — `StoreMailAccountRequest` / `UpdateMailAccountRequest` dengan `authorize()`, `rules()`, `messages()` dan `attributes()` Bahasa Indonesia. Tambah atribut ramah ke `lang/id/validation.php`.
6. **Policy** — `php artisan make:policy MailAccountPolicy --model=MailAccount`; scope ke pemilik (cegah IDOR). Superadmin otomatis lolos via `Gate::before` di `app/Providers/AppServiceProvider.php`.
7. **Service** — `app/Services/MailAccountService.php`; operasi multi-tabel dalam `DB::transaction()`; catat audit dengan `App\Support\ActivityRecorder`.
8. **Controller** — `app/Http/Controllers/Backend/MailAccount/MailAccountController.php` (resource). Hanya: authorize → panggil service → response. Error JSON pakai `$this->debugMessage($e)`.
9. **Route** — di `routes/web.php` dalam grup `['auth', 'forbid-banned-user']`; aksi tulis di dalam `throttle:write`. URL kebab-case `/admin/mail-accounts`, nama `mail-accounts.index`. Pakai `->only([...])` untuk method yang ada saja.
10. **Permission** — jalankan `php artisan` command generate permission (lihat `app/Console/Commands/GeneratePermissions.php`) agar permission dari nama route terbuat.
11. **View** — `resources/views/backend/mail_account/index.blade.php` extends `backend.layout.app`; `@section('title', 'Akun Email')`. Form: `@csrf`, `@method`, `@error` + `<x-field-error name="..." />`. AJAX sudah otomatis mengirim CSRF (`partials/ajax-setup.blade.php`) dan bisa memakai `window.showValidationErrors(form, errors)`.
12. **Menu** — tambahkan item di `resources/views/backend/layout/sidebar.blade.php` dengan `@can(...)`.
13. **Test** — `tests/Feature/MailAccountTest.php`: guest 302, user tanpa izin 403, validasi 422, sukses.
14. **Docs** — perbarui `docs/ARCHITECTURE.md` (tabel modul), `docs/DATABASE.md` (tabel + ERD), `docs/CHANGELOG.md`.

## Checklist selesai

- [ ] `php artisan migrate --pretend` bersih
- [ ] `vendor/bin/pint --dirty` dan `vendor/bin/phpstan analyse` lulus
- [ ] `php artisan test --filter=MailAccount` lulus
- [ ] Tidak ada query di Blade, tidak ada N+1 (cek `storage/logs/laravel.log` untuk peringatan lazy loading)
- [ ] Skill `security-review` dijalankan

## Hindari

- `$guarded = []`, validasi inline di controller untuk fitur baru, `findOrFail($id)` tanpa scope pemilik, nama aplikasi hardcode, teks UI berbahasa Inggris.
