# AQDesk — automated feature test suite

Ten PHPUnit feature tests covering the modules implemented in the prototype.
They mirror, case for case, the manual test plan document (Suites A–H).

## Install

Copy this folder into your Laravel app:

```
src/tests/Feature/AqDesk/
```

Nothing else to change — no composer packages, no config edits.

## Run

```
cd src
php artisan test --testsuite=Feature
```

Or one suite at a time (recommended on the first run):

```
php artisan test tests/Feature/AqDesk/SmokeTest.php
php artisan test tests/Feature/AqDesk/RoleAccessTest.php
php artisan test --filter=RoleAccessTest
php artisan test --filter=test_b13
```

Always run `SmokeTest` first. If it fails, the install or the seed data is
wrong and every later failure is noise.

## ⚠ Read this before the first run

These tests use `RefreshDatabase`, which **migrates the configured database
down and back up on every test** — your development data is wiped each run.

You picked "same MySQL DB", so that is what happens by default. To keep your
dev data, create a second database and add a `.env.testing` file next to
`.env`:

```
APP_ENV=testing
DB_DATABASE=aqdesk_testing
```

Then `php artisan test` uses it automatically. Or, for a database-free run,
put this in `phpunit.xml` instead:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## What is in each file

| File | Suite | Cases | Covers |
| --- | --- | --- | --- |
| `SmokeTest.php` | — | 5 | Seed data, role helpers, public pages |
| `AuthenticationTest.php` | A | 8 | Register, validation, sign in/out, remember me |
| `RoleAccessTest.php` | B | 14 | 403s per role, ownership, role change, deactivation |
| `DashboardTest.php` | C | 5 | Per-role controls, empty states |
| `ProfileTest.php` | D | 6 | Details, avatar upload, password change |
| `QuestionAnswerTest.php` | E | 17 | Ask, answer, vote, accept, filter, moderate |
| `JobOpportunityTest.php` | F | 12 | Post, validate, apply, shortlist, withdraw |
| `ModuleNavigationTest.php` | G | 4 | Phase 2 placeholders, route sweep, search |
| `InterfaceTest.php` | H | 6 | Theme, drawer, viewport, skip link, labels |

Test method names carry their case ID (`test_b13_deactivated_user_is_locked_out`),
so a failure points straight at a row in the test plan document.

## What is NOT automated

Suite H's visual half needs a real browser: dark mode repainting, the 375px
layout, no-zoom-on-focus, and tabbing through a form. These tests assert the
markup and hooks are present; the rendered behaviour stays a manual check in
the test plan. `test_h6` is deliberately marked skipped so it appears in the
run summary rather than silently passing.

## Design note

Records are created by POSTing to the application's own routes
(`askQuestionAs`, `postJobAs`, `applyAs` in `AqDeskTestCase`), not by writing
models directly. Two benefits: the suite does not depend on column names or
model factories, and every helper doubles as a test of the create path it
uses. If a helper fails, the bug is in that controller, not the test.

## Verified against the real controllers

Every route name, validation field and authorisation rule in this suite was
checked against `routes/web.php` and the controllers in
`app/Http/Controllers/`. Confirmed:

- Route names match `routes/web.php` exactly.
- Validation keys match each controller's `$request->validate()` array
  (`title`, `category_id`, `body`, `tags`, `cover_letter`, `resume`,
  `salary_max`, `deadline`, `current_password`, `avatar`, `role_slug`).
- Role slugs are `admin`, `expert`, `member` (`App\Models\Role` constants).
- Application statuses are `pending, reviewed, shortlisted, rejected, accepted`
  — `pending` is the default on submit.
- `EnsureUserIsActive` is appended to the `web` middleware group, which is why
  `test_b13` can assert that a live session is ejected.
- The `User` model casts `password` as `hashed`, so the base class assigns the
  plain string `'password'` — hashing it first would double-hash and break
  sign-in.

If a test still fails, treat it as a real finding and check the log at
`storage/logs/laravel.log`.
