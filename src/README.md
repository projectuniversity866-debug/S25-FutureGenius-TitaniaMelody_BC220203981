# AQDesk — application source

This folder holds the Laravel application. The full project README, with the
overview, setup instructions, implemented features, reflection and future work,
is in the repository root: **`../README.md`**

Quick start (see `../docs/SETUP-GUIDE.md` for the detailed Windows/WAMP walkthrough):

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Then open http://localhost:8000 and sign in as `member@aqdesk.test` / `password`.

## Where things live

| Path | Contents |
|---|---|
| `app/Models` | 10 Eloquent models |
| `app/Http/Controllers` | 11 controllers, grouped by module |
| `app/Http/Middleware` | `EnsureUserHasRole`, `EnsureUserIsActive` |
| `routes/web.php` | All routes, grouped and commented by module |
| `database/migrations` | 11 application migrations |
| `database/seeders` | Demo data — roles, categories, users, Q&A threads, jobs |
| `resources/views` | 32 Blade templates |
| `public/css/aqdesk.css` | The project's own styles (no build step) |
