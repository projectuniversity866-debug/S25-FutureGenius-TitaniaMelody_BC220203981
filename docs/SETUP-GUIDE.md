# AQDesk — Step-by-step setup on WampServer (Windows)

Follow these in order. The whole process takes about ten minutes.

## 1. Install WampServer
Download from https://www.wampserver.com and install with the default options.
Start it and wait for the tray icon to turn **green**. If it stays orange or red,
another program is using port 80 (often Skype or IIS) — close it and restart WAMP.

## 2. Install Composer
Download https://getcomposer.org/Composer-Setup.exe
During setup it asks for your PHP executable. Point it at:

    C:\wamp64\bin\php\php8.2.x\php.exe

(substitute the PHP version WAMP actually installed — check the folder name).

Open a **new** Command Prompt and confirm both tools work:

    php -v
    composer -V

## 3. Create the database
Open http://localhost/phpmyadmin — username `root`, password blank.
Click **New**, name the database `aqdesk`, choose collation `utf8mb4_general_ci`, click **Create**.

## 4. Put the project in place
Copy the `src` folder contents into `C:\wamp64\www\aqdesk`, then open a Command Prompt there:

    cd C:\wamp64\www\aqdesk

## 5. Install and configure

    composer install
    copy .env.example .env
    php artisan key:generate

## 6. Build the database and load the demo data

    php artisan migrate --seed
    php artisan storage:link

## 7. Run it

    php artisan serve

Open http://localhost:8000

## Demo accounts — password is `password` for all of them

| Role | Email |
|---|---|
| Administrator | admin@aqdesk.test |
| Expert | expert@aqdesk.test |
| Expert (employer) | hr@aqdesk.test |
| Member | member@aqdesk.test |
| Member | sara@aqdesk.test |
| Member | faisal@aqdesk.test |

---

## Troubleshooting

**"composer is not recognized"** — reopen Command Prompt, or reinstall Composer and tick "add to PATH".

**`SQLSTATE[HY000] [1049] Unknown database 'aqdesk'`** — the database was not created in step 3, or the name is misspelled.

**`SQLSTATE[HY000] [1045] Access denied for user 'root'`** — your MySQL root account has a password. Set `DB_PASSWORD=` in `.env` to match it.

**`Please provide a valid cache path`** — run:

    php artisan cache:clear
    php artisan config:clear

**Uploaded images do not display** — `php artisan storage:link` was not run, or it failed. On Windows run the Command Prompt as Administrator and try again.

**Page is blank / 500 error** — check `storage/logs/laravel.log` for the real error. Make sure `APP_DEBUG=true` in `.env` while developing.

**Reset everything and start fresh**

    php artisan migrate:fresh --seed
