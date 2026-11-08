---
name: security-review
description: Use before committing or merging any feature in Mail Sync, or when asked to audit security. Walks the OWASP Top 10 / ASVS L2 checklist adapted to this project (IDOR, CSRF, CSP, uploads, logging, secrets).
---

# Security Review (Mail Sync)

Acuan lengkap: `docs/SECURITY.md`.

## Langkah
1. `git diff main...HEAD --stat` — daftar file yang berubah.
2. Telusuri setiap route baru: `php artisan route:list --path=admin -v`.
3. Jalankan checklist di bawah per perubahan.
4. Jalankan alat: `composer audit`, `npm audit --omit=dev`, `vendor/bin/phpstan analyse`, semgrep bila plugin tersedia.
5. Catat temuan + perbaikan di deskripsi PR / `docs/CHANGELOG.md` (bagian Security).

## Checklist
**A01 Access control**
- [ ] Route di grup `auth`; aksi admin punya `can:`/Policy.
- [ ] Data diambil lewat scope pemilik (`$request->user()->mailAccounts()->findOrFail($id)` atau Policy), bukan `Model::findOrFail($id)` mentah. Untuk akun sendiri: `$this->ownAccountOrFail($id)`.
- [ ] Tidak ada peran default yang memberi Superadmin (lihat `config/permission.php` `default_role`).

**A02 Crypto**
- [ ] Kredensial pihak ketiga (password IMAP, API key) cast `encrypted` + masuk `$hidden`.

**A03 Injection**
- [ ] Tidak ada input user di `DB::raw`/`whereRaw`/`orderByRaw` tanpa binding; kolom sort di-whitelist.
- [ ] Blade memakai `{{ }}`; `{!! !!}` hanya untuk konten statis/tersanitasi.
- [ ] Upload: `mimes` + `max`, nama acak, tanpa svg/html/php.

**A04/A05 Design & config**
- [ ] Aksi berbahaya: konfirmasi + otorisasi + `ActivityRecorder`.
- [ ] Tautan sensitif memakai `URL::temporarySignedRoute`.
- [ ] Error ke klien pakai `$this->debugMessage($e)`, bukan `$e->getMessage()`.
- [ ] Domain eksternal baru ditambahkan ke CSP (`SecurityHeaders::CDN_HOSTS` atau `SECURITY_CSP_EXTRA_HOSTS`) dan dicek di console.

**A07 Auth**
- [ ] Form publik: `@csrf`, `<x-honeypot />`, middleware `spam` + throttle.
- [ ] Pesan tidak membocorkan keberadaan akun.

**A09 Logging**
- [ ] Event penting → `Log::channel('security')`; tidak ada password/token di log.

**A10 SSRF**
- [ ] Fetch URL dari input user (mis. server IMAP): hanya `http/https` atau host/port tervalidasi, tolak IP privat/loopback (`FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE` setelah resolve DNS).

## Hindari
- Melonggarkan CSP dengan `*` atau mematikan CSP; mematikan CSRF selain untuk webhook bertanda tangan.
