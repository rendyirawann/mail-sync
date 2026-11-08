---
name: validation-and-errors
description: Use when adding or changing form validation, error messages, JSON 422 responses, or HTTP error pages in Mail Sync. Covers FormRequest patterns, Indonesian messages, per-field error display, AJAX error handling, and errors/*.blade.php.
---

# Validasi & Error (Mail Sync)

## Validasi form web

1. Buat FormRequest dengan `rules()`, `authorize()`, `messages()`/`attributes()` Bahasa Indonesia. Atribut umum sudah ada di `lang/id/validation.php` (`attributes`).
2. Di Blade, setiap input:
   ```blade
   <input name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" />
   <x-field-error name="email" />
   ```
   Komponen: `resources/views/components/field-error.blade.php`. Ringkasan di atas form: `@if ($errors->any()) ... $errors->first() ... @endif` (lihat `resources/views/auth/forgot-password.blade.php`).
3. Laravel otomatis `redirect()->back()->withInput()` saat validasi gagal.

## Validasi AJAX / JSON

- Request dengan `Accept: application/json` (atau `api/*`) gagal validasi → 422 `{status, message, errors: {field: [..]}}` (lihat `withExceptions()` di `bootstrap/app.php`).
- jQuery AJAX sudah mengirim `Accept: application/json` + `X-CSRF-TOKEN` (`resources/views/partials/ajax-setup.blade.php`). Tampilkan error:
  ```js
  error: function (xhr) {
      if (xhr.status === 422) { window.showValidationErrors(form, xhr.responseJSON.errors); }
  }
  ```
- 419 otomatis memunculkan dialog "Sesi Kedaluwarsa" + tombol muat ulang.

## Halaman error

- `resources/views/errors/layout.blade.php` MANDIRI (CSS inline, tanpa Vite/DB/session). Jangan tambahkan `@vite`, `$brand`, `auth()` di sana.
- Kode tersedia: 400, 401, 403, 404, 405, 408, 413, 419, 422, 429, 500, 502, 503, 504. File baru cukup:
  ```blade
  @extends('errors.layout')
  @section('code', '410')
  @section('title', 'Halaman Dihapus')
  @section('message', 'Halaman ini sudah tidak tersedia.')
  ```
- `abort(403, 'Pesan khusus')` → pesan tampil di 4xx. 5xx tidak pernah menampilkan detail exception.

## Checklist

- [ ] Semua pesan Bahasa Indonesia
- [ ] Setiap input punya `@error` + `<x-field-error>`
- [ ] Test: 422 web (redirect + session errors) dan JSON (`assertJsonValidationErrors`)

## Hindari

- Mengembalikan `$e->getMessage()` ke klien (pakai `$this->debugMessage($e)`), validasi 200 OK berisi `errors` untuk fitur baru (gunakan 422).
