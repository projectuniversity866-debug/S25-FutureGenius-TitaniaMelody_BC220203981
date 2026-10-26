# AQDesk — notes for Claude Code

AQDesk is a CS619 final-year project (student: Titania Melody, BC220203981;
supervisor: Mr. Abdullah Qamar). It is a Laravel 12 modular monolith with
Blade + Bootstrap 5 and Vue 3 "islands", on MySQL (WAMP).

## Start here

1. Read `ROADMAP.md`: the step list, the "Next steps" line, and the progress
   log at the bottom (what each finished step did and how it was tested).
2. Read `docs/architecture/ARCHITECTURE.md` for the modules and layering.
3. Setup on a new machine: `docs/SETUP-GUIDE.md`.

## How the student wants us to work

- **Two roadmap steps per go.** When they say "continue", build the next two
  steps from `ROADMAP.md`, test them, mark them `[x]`, update the phase counts,
  the "Next steps" line and the progress log, report in short bullets, then
  **wait** for "continue" or "commit".
- **Test it yourself; never ask the student to test.** Per step: PHPUnit on
  CLI PHP and on WAMP's PHP 8.3, plus `bash tests/e2e/smoke.sh` (extend the
  smoke test for new flows). Add PHPUnit cases for every new feature.
- **No screenshot runs or ad-hoc browser scripts** unless asked (they said it
  wasted time). **No extra multi-agent AI reviews** unless asked.
- After migrations, run `php artisan migrate` on the WAMP MySQL database and
  check new pages return 200. Seed demo data only additively; say so in the report.
- **Commit and push only when the student says "commit"**, to the
  `development` branch. Never commit `.env`, `.env.testing`, SQLite files,
  logs, `storage/app` uploads, `vendor` or `node_modules`. End commit
  messages with: `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`
- Explain things in plain language; the student prefers short bullet points.

## Code conventions

- Modules live in `src/app/Modules/<Module>` (Identity, Community, Knowledge,
  Careers, Projects, EventsMedia, Consulting, Communications, Governance, and
  the shared Platform). Each `<Module>ServiceProvider` (base:
  `ModuleServiceProvider`) loads its routes, namespaced views (`community::…`),
  migrations, `policies()`, `listeners()`, `morphMap()` and `relations()`.
- Identity depends on no feature module; feature modules depend only on
  Identity and Platform (Knowledge also reads Community's categories as a
  shared taxonomy). The app shell (`src/app/Http/Controllers`,
  `src/resources/views`) may combine modules (dashboard, profile, Saved).
- Layers: Form Requests validate (no inline validation in controllers),
  Policies authorise, Services do every write, controllers stay thin.
- Permissions: slugs in `Identity/Support/PermissionCatalog.php` are Gate
  abilities (`can:<slug>`). A new permission also needs a migration that adds
  it to existing databases (see `2026_10_27_000910_create_forms_tables.php`).
- `Relation::requireMorphMap()` is on: every polymorphic model needs an alias
  in its module's `morphMap()`.
- Notifications extend `Platform\Notifications\AppNotification` (in-app +
  email by category). Modules raise domain events and listen in `listeners()`.
- Every page works without JavaScript. Vue islands mount on
  `<x-vue name="…" :props="…">` with the no-JS markup inside; Vue components
  live in `src/resources/js/components`. After JS changes: `npm run build`
  (`public/build` is committed).
- Accessibility (WCAG 2.1 AA): labelled controls, `aria-describedby` for
  hints/errors, visible text plus icon (never colour alone), confirm dialogs
  via `data-confirm`, toasts via `showToast()`. **No inline styles** — use
  `public/css/aqdesk.css` tokens and classes.
- Match the surrounding code: comment density, naming, Blade components
  (`x-page-header`, `x-card`, `x-tabs`, `x-filter-panel`, `x-empty`,
  `x-status`, `x-bar-list`, `x-verified-badge`).

## Commands (Git Bash, from `src/`)

```
php vendor/bin/phpunit                                   # CLI PHP (8.4)
/c/wamp64/bin/php/php8.3.28/php.exe vendor/bin/phpunit   # WAMP's PHP 8.3
bash tests/e2e/smoke.sh                                  # real app on a throw-away SQLite DB
export PATH="/c/Program Files/nodejs:$PATH" && npm run build
php artisan migrate                                      # WAMP MySQL (.env)
```

## Environment gotchas

- CLI PHP may be newer than WAMP's Apache PHP (8.3). `composer.json` pins
  `config.platform.php` = 8.2.0; after Composer changes, check the site still
  loads under Apache.
- Tests run on in-memory SQLite (`phpunit.xml`); the real app uses MySQL.
  Keep SQL portable (no aliases inside expressions; bind PHP `now()`, never
  MySQL `NOW()` — the MySQL server clock is in a different time zone).
- perl/sed mangle PHP namespace backslashes — use the Edit/Write tools for
  PHP files.
- Install npm packages with `--before=<date at least two weeks ago>`.
- Page-wide `assertDontSee` in tests can be tripped by the notification bell —
  sign out or clear notifications first.
