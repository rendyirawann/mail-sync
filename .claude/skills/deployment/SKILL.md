---
name: deployment
description: Use when preparing, executing, or verifying a production deployment of Mail Sync — env hardening, HTTPS/HSTS, caches, queue/Reverb supervisors, cron, permissions, backups, and post-deploy checks.
---

# Deployment (Mail Sync)

Sumber lengkap: `docs/DEPLOYMENT.md`.

## Langkah

1. **Env** — `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=error`, `APP_URL` https persis, `SESSION_SECURE_COOKIE=true`, `TRUSTED_PROXIES` bila di belakang proxy, secret Reverb unik.
2. **Kode & aset**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   php artisan queue:restart && php artisan reverb:restart
   ```
3. **Proses** — supervisor untuk `queue:work` dan `reverb:start`; cron `schedule:run` tiap menit.
4. **Server** — document root `public/`, HTTPS, lalu `SECURITY_HSTS=true`; php.ini `expose_php=Off`.
5. **Verifikasi**
   ```bash
   curl -sI https://domain/ | grep -iE "content-security|x-frame|strict-transport|x-content-type"
   curl -s -o /dev/null -w "%{http_code}" https://domain/.env      # harus 403/404
   curl -s https://domain/robots.txt                              # Allow / + Disallow /admin
   ```
   Buka beranda, login, dashboard: tidak ada error console/CSP.
6. **Backup** — `pg_dump` harian off-site + uji restore.

## Checklist

- [ ] `composer audit` & `npm audit --omit=dev` bersih
- [ ] `php artisan test` lulus di CI
- [ ] Sitemap didaftarkan di Google Search Console

## Hindari

- Menyalin `.env`/APP_KEY dari local, `APP_DEBUG=true` di production, menjalankan `migrate:fresh` di production, HSTS sebelum HTTPS stabil.
