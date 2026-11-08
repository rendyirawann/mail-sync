# Deployment — disposisi-in.hustlesync.my.id

Panduan memasang aplikasi di VPS baru, dari server kosong sampai siap dipakai pelanggan.

| Item | Nilai |
| --- | --- |
| Domain | `disposisi-in.hustlesync.my.id` |
| IP server | `76.13.23.58` (DNS record A, TTL 14400) |
| OS yang diasumsikan | Ubuntu 22.04 / 24.04 LTS, akses root/sudo |
| Stack | Nginx · PHP 8.4-FPM · PostgreSQL 16 · Node 20 (hanya untuk build) · Supervisor · Certbot |
| Folder aplikasi | `/var/www/disposisi-in` |
| User aplikasi | `www-data` |
| Repo | `https://github.com/rendyirawann/mail-sync` (branch `feat/saas-surat`, atau `main` setelah digabung) |

> Semua perintah dijalankan sebagai user dengan sudo. Ganti setiap `GANTI_...` dengan nilai Anda sendiri. Jangan pernah menaruh password asli di repo.

---

## 0. Cek DNS

Dari komputer mana saja:

```bash
nslookup disposisi-in.hustlesync.my.id
# harus menjawab 76.13.23.58
```

TTL 14400 berarti perubahan DNS bisa memakan waktu hingga 4 jam. Lanjutkan ke langkah 7 (SSL) hanya setelah domain sudah mengarah ke IP ini.

---

## 1. Persiapan server

```bash
sudo apt update && sudo apt -y upgrade
sudo timedatectl set-timezone Asia/Jakarta

# Firewall: SSH, HTTP, HTTPS saja
sudo apt -y install ufw
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full' 2>/dev/null || sudo ufw allow 80,443/tcp
sudo ufw --force enable

# Swap 2 GB (berguna di VPS RAM kecil, saat composer/npm build)
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## 2. Pasang paket

```bash
# PHP 8.4 + ekstensi yang dipakai aplikasi (gd wajib untuk logo/QR, pgsql untuk database)
sudo apt -y install software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt -y install nginx git unzip supervisor certbot python3-certbot-nginx \
  php8.4-fpm php8.4-cli php8.4-pgsql php8.4-gd php8.4-intl php8.4-mbstring php8.4-xml \
  php8.4-zip php8.4-curl php8.4-bcmath php8.4-opcache

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20 (hanya untuk build aset)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt -y install nodejs

# PostgreSQL
sudo apt -y install postgresql
```

## 3. Database

```bash
sudo -u postgres psql <<'SQL'
CREATE USER disposisi WITH PASSWORD 'GANTI_PASSWORD_DB_KUAT';
CREATE DATABASE disposisi_in OWNER disposisi ENCODING 'UTF8';
SQL
```

PostgreSQL bawaan hanya mendengarkan `localhost`. **Jangan** membuka port 5432 ke internet.

## 4. Ambil kode

```bash
sudo mkdir -p /var/www/disposisi-in
sudo chown $USER:www-data /var/www/disposisi-in
git clone -b feat/saas-surat https://github.com/rendyirawann/mail-sync.git /var/www/disposisi-in
cd /var/www/disposisi-in
```

## 5. Konfigurasi `.env`

```bash
cp .env.example .env
nano .env
```

Ubah nilai berikut. Sisanya biarkan sesuai `.env.example`.

```dotenv
APP_NAME="Mail Sync"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://disposisi-in.hustlesync.my.id
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

SUPERADMIN_EMAIL=GANTI_EMAIL_ANDA
SUPERADMIN_PASSWORD=          # kosongkan → dibuatkan password acak saat seeding

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=disposisi_in
DB_USERNAME=disposisi
DB_PASSWORD=GANTI_PASSWORD_DB_KUAT

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=disposisi_in_session
CACHE_STORE=database
CACHE_PREFIX=disposisi_in_cache
QUEUE_CONNECTION=database

# Email (untuk lupa password). Isi dengan SMTP asli.
MAIL_MAILER=smtp
MAIL_HOST=GANTI_SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=GANTI_SMTP_USER
MAIL_PASSWORD=GANTI_SMTP_PASSWORD
MAIL_FROM_ADDRESS="no-reply@hustlesync.my.id"
MAIL_FROM_NAME="${APP_NAME}"

# Realtime (notifikasi langsung). Lihat langkah 9 — boleh tetap "log" bila belum dipakai.
BROADCAST_CONNECTION=log
REVERB_APP_ID=GANTI_ANGKA_ACAK
REVERB_APP_KEY=GANTI_STRING_ACAK
REVERB_APP_SECRET=GANTI_STRING_ACAK_PANJANG
REVERB_HOST="disposisi-in.hustlesync.my.id"
REVERB_PORT=443
REVERB_SCHEME=https

# Keamanan
SECURITY_HSTS=false           # ubah ke true SETELAH HTTPS dipastikan berjalan (langkah 7)
TRUSTED_PROXIES=              # isi "*" HANYA jika domain di belakang Cloudflare (proxy oranye)
```

Untuk mengisi nilai acak Reverb:

```bash
php -r 'echo random_int(100000,999999), PHP_EOL, bin2hex(random_bytes(10)), PHP_EOL, bin2hex(random_bytes(16)), PHP_EOL;'
```

## 6. Install, build, migrate

```bash
cd /var/www/disposisi-in
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
npm ci && npm run build

php artisan migrate --force
php artisan db:seed --force          # template bawaan, role & permission, akun Superadmin
#   → CATAT password Superadmin yang tampil di layar (hanya muncul sekali)

php artisan app:branding --apply     # logo, favicon, OG image
php artisan storage:link

# Hak akses folder
sudo chown -R $USER:www-data /var/www/disposisi-in
sudo find /var/www/disposisi-in -type d -exec chmod 755 {} \;
sudo find /var/www/disposisi-in -type f -exec chmod 644 {} \;
sudo chmod -R ug+rwx storage bootstrap/cache
chmod 600 .env && sudo chown $USER:www-data .env && chmod 640 .env

php artisan optimize                  # cache config, route, view, event
```

> Setelah login pertama, kosongkan `SUPERADMIN_PASSWORD` di `.env` (bila diisi), lalu jalankan `php artisan optimize` lagi.

## 7. Nginx + HTTPS

Buat `/etc/nginx/sites-available/disposisi-in`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name disposisi-in.hustlesync.my.id;

    root /var/www/disposisi-in/public;
    index index.php;
    charset utf-8;

    client_max_body_size 6M;          # bukti transfer maks. 5 MB
    server_tokens off;
    autoindex off;

    # Jangan pernah layani dotfile (.env, .git) atau berkas sensitif
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(env|log|sql|bak|sh|ini)$ { deny all; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Aset statis: cache panjang (nama file Vite sudah ber-hash)
    location ~* ^/(build|assets)/ {
        expires 30d;
        access_log off;
        try_files $uri =404;
    }

    location = /favicon.ico { access_log off; log_not_found off; }

    # Websocket Reverb (realtime) — lihat langkah 9
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass http://127.0.0.1:8080;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;      # PDF & laporan Excel
    }

    location ~ /\.ht { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/disposisi-in /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Sertifikat SSL gratis (Let's Encrypt) — otomatis menambah blok 443 & redirect HTTP→HTTPS
sudo certbot --nginx -d disposisi-in.hustlesync.my.id --redirect -m GANTI_EMAIL_ANDA --agree-tos -n
sudo certbot renew --dry-run
```

Setelah `https://disposisi-in.hustlesync.my.id` terbuka normal, aktifkan HSTS:

```bash
sed -i 's/^SECURITY_HSTS=.*/SECURITY_HSTS=true/' .env && php artisan optimize
```

## 8. PHP production (php.ini)

Edit `/etc/php/8.4/fpm/php.ini`:

```ini
expose_php = Off
display_errors = Off
log_errors = On
memory_limit = 256M
upload_max_filesize = 6M
post_max_size = 8M
max_execution_time = 120
date.timezone = Asia/Jakarta

opcache.enable = 1
opcache.memory_consumption = 128
opcache.validate_timestamps = 0   ; wajib restart php-fpm setiap deploy (lihat langkah 12)
```

```bash
sudo systemctl restart php8.4-fpm
```

## 9. Proses latar (Supervisor)

`/etc/supervisor/conf.d/disposisi-in.conf`:

```ini
[program:disposisi-in-queue]
command=php /var/www/disposisi-in/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/disposisi-in/storage/logs/queue.log
stopwaitsecs=3600

; Opsional: notifikasi realtime (lonceng langsung muncul tanpa refresh).
; Aktifkan bersama BROADCAST_CONNECTION=reverb di .env.
[program:disposisi-in-reverb]
command=php /var/www/disposisi-in/artisan reverb:start --host=127.0.0.1 --port=8080
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/disposisi-in/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status
```

Tanpa Reverb, notifikasi tetap tersimpan dan muncul di lonceng saat halaman dimuat ulang. Untuk mengaktifkan realtime, set `BROADCAST_CONNECTION=reverb`, lalu jalankan `php artisan optimize` dan `sudo supervisorctl restart disposisi-in-reverb`.

## 10. Cron (scheduler)

Scheduler membekukan otomatis tim yang langganannya lewat jatuh tempo (setiap hari pukul 00:15).

```bash
sudo crontab -u www-data -e
```

Tambahkan:

```cron
* * * * * cd /var/www/disposisi-in && php artisan schedule:run >> /dev/null 2>&1
```

## 11. Backup harian

`/usr/local/bin/backup-disposisi.sh`:

```bash
#!/bin/bash
set -e
DIR=/var/backups/disposisi-in
mkdir -p "$DIR"
STAMP=$(date +%F)
sudo -u postgres pg_dump disposisi_in | gzip > "$DIR/db-$STAMP.sql.gz"
tar -czf "$DIR/files-$STAMP.tar.gz" -C /var/www/disposisi-in storage/app .env
find "$DIR" -type f -mtime +14 -delete   # simpan 14 hari
```

```bash
sudo chmod 700 /usr/local/bin/backup-disposisi.sh
echo '30 1 * * * root /usr/local/bin/backup-disposisi.sh' | sudo tee /etc/cron.d/backup-disposisi
```

Salin backup ke luar server secara berkala (Google Drive, rclone, atau komputer lokal). Backup yang hanya ada di server yang sama tidak melindungi dari kerusakan server. Sesekali lakukan uji restore: `gunzip -c db-TANGGAL.sql.gz | sudo -u postgres psql disposisi_in_test`.

## 12. Update aplikasi (deploy berikutnya)

Simpan sebagai `/var/www/disposisi-in/deploy.sh` (jangan di-commit bila berisi info sensitif):

```bash
#!/bin/bash
set -e
cd /var/www/disposisi-in
php artisan down --retry=30
git pull --ff-only
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl restart php8.4-fpm
sudo supervisorctl restart disposisi-in-queue disposisi-in-reverb || true
php artisan up
echo "Deploy selesai: $(git log -1 --oneline)"
```

## 13. Pengaturan awal di aplikasi (wajib sebelum menerima pelanggan)

1. Buka `https://disposisi-in.hustlesync.my.id/admin/login`, lalu masuk dengan akun Superadmin dari langkah 6. **Segera ganti password** di menu Keamanan.
2. **Layanan SaaS → Pengaturan Pembayaran**:
   - isi **nomor WhatsApp admin** (tujuan konfirmasi transfer),
   - tambahkan **rekening** (bank, nomor, atas nama),
   - periksa harga paket (Rp39.000 bulanan, Rp99.000 lifetime) dan template pesan WA.
3. **Pengaturan** (menu Settings): cek nama aplikasi, deskripsi, dan logo. Untuk SEO, set robots ke `index, follow`.
4. Uji alur dari ponsel: buka `/daftar`, isi sampai muncul tagihan, tekan tombol WA, lalu setujui dari panel Superadmin dan login ke `/{kode}/login`.

## 14. Verifikasi setelah deploy

```bash
# Header keamanan
curl -sI https://disposisi-in.hustlesync.my.id | grep -iE "strict-transport|content-security|x-frame|x-content-type|referrer"

# File sensitif harus tertutup (403/404)
curl -s -o /dev/null -w "%{http_code}\n" https://disposisi-in.hustlesync.my.id/.env
curl -s -o /dev/null -w "%{http_code}\n" https://disposisi-in.hustlesync.my.id/composer.json

# Halaman publik 200
for p in / /daftar /masuk /robots.txt /sitemap.xml /admin/login; do
  printf "%s %s\n" "$(curl -s -o /dev/null -w '%{http_code}' https://disposisi-in.hustlesync.my.id$p)" "$p"; done

# Proses latar & jadwal
sudo supervisorctl status
php artisan schedule:list
```

Buka beranda, login, dan dashboard di browser, lalu pastikan tidak ada error di Console (F12). Daftarkan juga `https://disposisi-in.hustlesync.my.id/sitemap.xml` di Google Search Console.

## 15. Bila terjadi masalah

| Gejala | Periksa |
| --- | --- |
| Error 500 | `tail -n 50 storage/logs/laravel-*.log` |
| Halaman kosong / 502 | `sudo systemctl status php8.4-fpm`, `sudo tail /var/log/nginx/error.log` |
| "Sesi kedaluwarsa" (419) terus-menerus | `APP_URL` harus persis `https://disposisi-in.hustlesync.my.id`; `SESSION_SECURE_COOKIE=true` hanya jika HTTPS aktif |
| Perubahan `.env` tidak berlaku | `php artisan optimize:clear && php artisan optimize` |
| Logo kop tidak muncul di PDF | Tautan Google Drive harus diatur "Siapa saja yang memiliki link" |
| Tim tidak terbeku otomatis | Cron langkah 10 belum terpasang → `php artisan schedule:list` |
| Semua pengunjung terbaca satu IP (rate limit kena semua) | Domain di belakang proxy/Cloudflare → isi `TRUSTED_PROXIES` |

## Checklist singkat

- [ ] DNS A `disposisi-in.hustlesync.my.id` → `76.13.23.58`
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` https
- [ ] Database & `.env` (chmod 640) siap; `key:generate` dijalankan
- [ ] `migrate --force` + `db:seed --force`; password Superadmin dicatat & diganti
- [ ] Nginx root = `public/`, SSL Certbot aktif, lalu `SECURITY_HSTS=true`
- [ ] php.ini `expose_php=Off`, opcache aktif
- [ ] Supervisor queue (+ Reverb opsional) berjalan
- [ ] Cron `schedule:run` terpasang
- [ ] Backup harian + salinan di luar server
- [ ] WA admin & rekening diisi di Pengaturan Pembayaran
- [ ] Uji alur daftar → setujui → login dari ponsel
