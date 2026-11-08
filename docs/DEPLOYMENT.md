# Deployment Mail Sync

## Checklist production

### Environment
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=error`
- [ ] `APP_URL=https://domain-anda` (persis, tanpa slash akhir)
- [ ] `APP_KEY` baru (`php artisan key:generate`) — jangan salin dari local
- [ ] `REVERB_APP_ID/KEY/SECRET` acak dan unik
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=null` (atau domain persis)
- [ ] `TRUSTED_PROXIES` diisi bila di belakang Cloudflare/nginx/load balancer
- [ ] `MAIL_*` mengarah ke SMTP asli, `MAIL_FROM_ADDRESS` domain sendiri

### Web server
- [ ] Document root = `public/` (bukan root project)
- [ ] HTTPS aktif (sertifikat valid), redirect HTTP → HTTPS
- [ ] Setelah HTTPS stabil: `SECURITY_HSTS=true`
- [ ] php.ini: `expose_php=Off`, `display_errors=Off`, `upload_max_filesize` & `post_max_size` sesuai kebutuhan
- [ ] nginx: `autoindex off;`, tolak `location ~ /\.(?!well-known)` dan `location ~* \.(env|log|sql)$`
- [ ] Cloudflare: SSL Full (strict), aktifkan Turnstile bila spam meningkat (`TURNSTILE_ENABLED=true`)

### Deploy

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize          # config, route, view, event cache
php artisan queue:restart
php artisan reverb:restart
```

### Proses latar (supervisor)

```ini
[program:mail-sync-queue]
command=php /var/www/mail-sync/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/mail-sync/storage/logs/queue.log

[program:mail-sync-reverb]
command=php /var/www/mail-sync/artisan reverb:start --host=127.0.0.1 --port=8080
autostart=true
autorestart=true
user=www-data
```

### Cron scheduler

```
* * * * * cd /var/www/mail-sync && php artisan schedule:run >> /dev/null 2>&1
```

### Permission

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Backup
- [ ] Backup DB harian (`pg_dump mail_sync | gzip > backup-$(date +%F).sql.gz`), simpan off-site, uji restore berkala.
- [ ] Backup `storage/app/public` (avatar) dan `.env` (di tempat aman, bukan repo).

### SEO
- [ ] `APP_ENV=production` membuka beranda untuk diindeks; panel/auth tetap noindex.
- [ ] Daftarkan `https://domain/sitemap.xml` di Google Search Console.

### Verifikasi setelah deploy
- [ ] `curl -I https://domain/` → header keamanan lengkap, 200
- [ ] Buka beranda, login, dashboard: tidak ada error console / CSP violation
- [ ] `https://domain/.env` → 403/404
