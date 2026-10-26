# AQDesk — Deploying the live demo

Roadmap step 5.1. Two routes: **A. a DigitalOcean (or AWS Lightsail / EC2)
Ubuntu server**, which is recommended because it runs the queue worker
properly, or **B. cPanel shared hosting**. Both serve the `main` branch over
HTTPS, with SMTP mail, the scheduler cron and a queue worker.

What the server needs:

| Need | Version / note |
|---|---|
| PHP | 8.2 or newer, with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `intl`, `gd`, `zip`, `bcmath`, `curl` |
| MySQL / MariaDB | MySQL 8 or MariaDB 10.6+, `utf8mb4` |
| Composer | 2.x |
| Node.js | **not needed**: `src/public/build` is committed |
| Git | to pull the repository |

The web server's document root must be **`src/public`**, never the repository
root or `src/`; otherwise `.env` and the private uploads could be downloaded.

---

## A. Ubuntu server (DigitalOcean droplet)

1. **Create the server.** Ubuntu 24.04, 1 GB RAM is enough for the demo.
   Point the domain's `A` record at its IP address.

2. **Install the software** (as root):

   ```bash
   apt update && apt -y upgrade
   apt -y install nginx mysql-server git unzip supervisor certbot python3-certbot-nginx \
       php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
       php8.3-intl php8.3-gd php8.3-bcmath
   curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
   ```

3. **Database:**

   ```sql
   -- sudo mysql
   CREATE DATABASE aqdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'aqdesk'@'localhost' IDENTIFIED BY '<strong password>';
   GRANT ALL ON aqdesk.* TO 'aqdesk'@'localhost';
   ```

4. **Get the code** and configure it:

   ```bash
   cd /var/www
   git clone -b main https://github.com/<account>/S25-FutureGenius-TitaniaMelody_BC220203981.git aqdesk
   cd aqdesk/src
   cp .env.production.example .env      # fill in APP_URL, DB_*, MAIL_*
   bash deploy.sh                       # installs, creates APP_KEY, migrates, caches
   php artisan db:seed --force          # optional: demo accounts and content
   chown -R www-data:www-data storage bootstrap/cache
   ```

   For the demo seed the passwords are all `password`; change the admin's
   password straight away, or skip the seed and register real accounts.

5. **Nginx** (`/etc/nginx/sites-available/aqdesk`, then link it into
   `sites-enabled` and `systemctl reload nginx`):

   ```nginx
   server {
       listen 80;
       server_name <your-domain>;
       root /var/www/aqdesk/src/public;
       index index.php;
       client_max_body_size 60M;          # 50 MB podcast audio

       location / { try_files $uri $uri/ /index.php?$query_string; }
       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/run/php/php8.3-fpm.sock;
       }
       location ~ /\.(?!well-known) { deny all; }
   }
   ```

   Also raise PHP's limits in `/etc/php/8.3/fpm/php.ini`:
   `upload_max_filesize = 60M`, `post_max_size = 64M`, then
   `systemctl restart php8.3-fpm`.

6. **HTTPS** (free Let's Encrypt certificate, renews itself):

   ```bash
   certbot --nginx -d <your-domain> --redirect
   ```

7. **Scheduler cron** (`crontab -u www-data -e`): runs reminders, closes
   expired opportunities and prunes logs.

   ```cron
   * * * * * cd /var/www/aqdesk/src && php artisan schedule:run >> /dev/null 2>&1
   ```

8. **Queue worker** (`/etc/supervisor/conf.d/aqdesk-worker.conf`): sends the
   notification emails.

   ```ini
   [program:aqdesk-worker]
   command=php /var/www/aqdesk/src/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   user=www-data
   autostart=true
   autorestart=true
   stopwaitsecs=60
   redirect_stderr=true
   stdout_logfile=/var/www/aqdesk/src/storage/logs/worker.log
   ```

   ```bash
   supervisorctl reread && supervisorctl update && supervisorctl status
   ```

**Updating later:** `cd /var/www/aqdesk/src && sudo -u www-data bash deploy.sh`.

---

## B. cPanel shared hosting

1. **Domain → document root.** In *Domains*, set the domain's document root to
   `aqdesk/src/public` (inside your home folder). If the host does not allow
   that, upload the repository outside `public_html` and replace
   `public_html` with a symbolic link to `src/public` (*Terminal*:
   `ln -s ~/aqdesk/src/public ~/public_html`, after renaming the old folder).
2. **PHP version.** *MultiPHP Manager* → PHP 8.2 or 8.3. In *MultiPHP INI
   Editor* raise `upload_max_filesize` and `post_max_size` to 60M.
3. **Database.** *MySQL Databases*: create the database and user, and grant
   all privileges. cPanel prefixes names (`cpuser_aqdesk`); use the full names
   in `.env`.
4. **Code.** *Git Version Control* → clone the repository (branch `main`) into
   `~/aqdesk`, or upload a zip. In *Terminal*:

   ```bash
   cd ~/aqdesk/src
   cp .env.production.example .env     # fill in; set QUEUE_WORKER_IN_SCHEDULER=true
   bash deploy.sh
   php artisan db:seed --force         # optional demo data
   ```

   No terminal on the plan? Run `composer install --no-dev` and
   `php artisan migrate --seed` on your own PC against an exported copy, and
   import the database with phpMyAdmin.
5. **HTTPS.** *SSL/TLS Status* → *Run AutoSSL* (free certificate). AQDesk
   already forces https links in production; set `SESSION_SECURE_COOKIE=true`.
6. **Mail.** *Email Accounts* → create `no-reply@<your-domain>`; use
   *Connect Devices* for the SMTP host and port (usually `mail.<your-domain>`,
   465 with `MAIL_SCHEME=smtps`).
7. **Cron.** *Cron Jobs* → every minute:

   ```cron
   * * * * * cd ~/aqdesk/src && php artisan schedule:run >> /dev/null 2>&1
   ```

   With `QUEUE_WORKER_IN_SCHEDULER=true` this same cron also empties the queue
   for up to 50 seconds each minute, so no Supervisor is needed.

---

## Check the live site

| Check | Expected |
|---|---|
| `https://<your-domain>/up` | 200 "Application up" |
| `http://<your-domain>` | redirects to https |
| Home, Questions, Events, Podcasts, Consultants | load without errors |
| Register a new account | verification email arrives |
| `php artisan schedule:list` | shows reminders, close-expired, prune (and `queue:work` on cPanel) |
| `php artisan queue:failed` | empty |
| `https://<your-domain>/.env` | 404, never the file |

## Settings that matter

| Setting | Why |
|---|---|
| `APP_DEBUG=false` | Error pages never show code, paths or secrets |
| `APP_URL=https://…` | Links in emails, calendar files and the RSS feed |
| `SESSION_SECURE_COOKIE=true` | Session cookie is sent over HTTPS only |
| `QUEUE_CONNECTION=database` | Pages stay fast; mail is sent by the worker |
| `TRUSTED_PROXIES` | Only behind Cloudflare / a load balancer, so https is detected |
| `MAIL_*` | Verification, password reset, notifications and reminders |

## Troubleshooting

- **500 error** — `storage/logs/laravel-<date>.log`. Usually folder
  permissions: `storage` and `bootstrap/cache` must be writable by the web
  server user.
- **Changed `.env` but nothing happens** — the configuration is cached; run
  `php artisan optimize` again (deploy.sh does this).
- **No emails** — `php artisan queue:failed` for errors; check the worker is
  running (`supervisorctl status`) or that the cron runs on cPanel.
- **Uploads fail above 2 MB** — PHP's `upload_max_filesize`/`post_max_size`
  (and Nginx `client_max_body_size`).
- **Images do not show** — `php artisan storage:link` (deploy.sh runs it once).
