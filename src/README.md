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

### Front-end (Vue) changes

The compiled JavaScript in `public/build` is committed, so **running** the app
needs no Node.js. Only when you change files in `resources/js` do you need
Node.js 20+:

```bash
npm install        # once
npm run dev        # live reload while editing
npm run build      # before committing — rebuilds public/build
```

## Where things live

The code is organised as a modular monolith — see
`../docs/architecture/ARCHITECTURE.md`.

| Path | Contents |
|---|---|
| `app/Modules/ModuleServiceProvider.php` | Base class that wires up each module's routes, views and migrations |
| `app/Modules/Identity` | FR1 — authentication, roles, profiles, admin users, `role` / `active` middleware |
| `app/Modules/Community` | FR2 — questions, answers, votes, tags, categories, question moderation |
| `app/Modules/Careers` | FR5 — jobs, internships, applications, opportunity moderation |
| `app/Modules/{Knowledge, Projects, EventsMedia, Consulting, Communications, Governance}` | Registered skeletons for the modules still to build |
| `app/Http/Controllers` | Application shell — home page, dashboard, placeholder pages |
| `bootstrap/providers.php` | The list of enabled modules |
| `routes/web.php` | Shell routes only; each module has its own `routes/web.php` |
| `database/migrations` | Laravel framework tables; module tables live in `app/Modules/*/Database/Migrations` |
| `database/seeders/DatabaseSeeder.php` | Runs the module seeders in order |
| `resources/views` | Shared layouts and partials; module views live in `app/Modules/*/resources/views` |
| `resources/views/components` | Design-system Blade components: `<x-page-header>`, `<x-card>`, `<x-stat>`, `<x-status>`, `<x-avatar>`, `<x-org-mark>`, `<x-empty>`, `<x-vue>`, `<x-tabs>`, `<x-filter-panel>`, `<x-bar-list>`, `<x-line-chart>` (every chart has a "View as table"). Tables use class `aq-table` with `data-label` on each cell to stack as cards on phones |
| `public/css/aqdesk.css` | The project's own styles (plain CSS, not built). Views use its classes — no inline styling, except a CSS custom property carrying a data value (bar width) |
| `app/Services/DashboardMetrics.php` | Read-only figures for the admin, expert and member dashboards |
| `resources/js/app.js` | Mounts Vue components on `data-vue="Name"` placeholders in Blade pages |
| `resources/js/components` | Vue 3 single-file components |
| `resources/js/feedback.js` | Confirmation dialog for forms with `data-confirm`, success toasts, screen-reader announcer |
| `public/build` | Compiled JavaScript from `npm run build` (committed) |
| `tests/Feature/AqDesk` | Feature test suite (92 tests) |
| `tests/e2e/smoke.sh` | End-to-end smoke test over HTTP on a throw-away database (`bash tests/e2e/smoke.sh`) |
| `tests/e2e/screens.sh` | Browser screenshots of every screen as each role + behaviour checks, using installed Edge (`bash tests/e2e/screens.sh [out-dir] [light\|dark]`) |
