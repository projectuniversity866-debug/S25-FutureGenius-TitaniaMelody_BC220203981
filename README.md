# AQDesk

**An accessible community, learning and knowledge platform.** Ask and answer
questions, learn from verified experts, find jobs and scholarships, work on
projects together, join events and podcasts, and book consulting sessions,
all with one account.

CS619 Final Year Project · Titania Melody (BC220203981) · Supervisor: Mr. Abdullah Qamar

---

## Features

| Area | What you can do |
|---|---|
| **Accounts** (FR1) | Register with email verification, Google/GitHub sign-in, password reset, roles and permissions, account settings, public profiles with reputation and badges |
| **Community Q&A** (FR2) | Markdown questions and answers, comments, edit history, voting without page reload, accepted answers, tags to follow, bookmarks, trending |
| **Knowledge & learning** | Expert verification, articles and guides by verified experts, learning paths with progress tracking |
| **Opportunities** (FR5) | Jobs, internships, scholarships, fellowships, grants, hackathons, calls for papers; organization profiles; application tracking with reviewer notes; job alerts |
| **Projects** (FR3) | Project spaces with roles, invitations, versioned private files, feedback threads, task board |
| **Events & podcasts** (FR4) | Events with seats and waitlist, registration questions, webinar links, calendar files, reminders; podcasts with transcripts and RSS |
| **Consulting** (FR6) | Consultant profiles and weekly hours, conflict-free booking, private session rooms, client records with private notes, proposals (Draft → Sent → Accepted / Rejected → Completed) |
| **Forms** | Form Builder for creators (10 question types), responses and CSV export |
| **Governance** | Community guidelines, reporting and moderation queue with decision log, audit trail, download my data, delete my account |
| **Accessibility** | WCAG 2.1 AA target, works without JavaScript, accessibility toolbar (text size, high contrast, readable font, line spacing, reduced motion), light and dark themes |
| **Newsletter** (FR7) | Subscribe with double opt-in, one-click unsubscribe; administrators write, test, schedule and send campaigns (sent from the queue), with delivery and open reports; site announcements as banners and an Updates page |


## Built with

Laravel 12 (PHP 8.2+) · MySQL 8 · Blade and Bootstrap 5 · Vue 3 components
built with Vite · PHPUnit. It is a **modular monolith**: each feature lives
in its own module under `src/app/Modules/` (Identity, Community, Knowledge,
Careers, Projects, EventsMedia, Consulting, Communications, Governance, and
the shared Platform). See [the architecture document](docs/architecture/ARCHITECTURE.md).

## Repository layout

| Folder | Contents |
|---|---|
| `src/` | The Laravel application |
| `docs/` | Report, user manual, architecture, setup and deployment guides, diagrams |
| `schema/` | Database schema SQL (`aqdesk_schema.sql`) |
| `presentation.pptx` | Project presentation |

## Run it locally (WampServer)

Full steps with troubleshooting: [docs/SETUP-GUIDE.md](docs/SETUP-GUIDE.md).

```bash
cd src
composer install
cp .env.example .env          # set DB_CONNECTION=mysql and DB_DATABASE=aqdesk
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve             # http://localhost:8000
```

Node.js is not needed to run it: the built JavaScript in `src/public/build`
is committed.

**Demo accounts** (password `password`):

| Role | Email |
|---|---|
| Administrator | `admin@aqdesk.test` |
| Expert | `expert@aqdesk.test` |
| Member | `member@aqdesk.test`, `sara@aqdesk.test` |

## Tests

```bash
cd src
php vendor/bin/phpunit        # feature tests on an in-memory SQLite database
bash tests/e2e/smoke.sh       # end-to-end: starts the app and drives it over HTTP
```

## Deploy

[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) covers a DigitalOcean server and cPanel hosting
(HTTPS, SMTP, cron, queue worker); `src/deploy.sh` does each update.

## Documents

| Document | |
|---|---|
| [Final report](docs/report/FINAL-REPORT.md) | Analysis, design, implementation, evaluation |
| [User manual](docs/USER-MANUAL.md) | For all users, including form creators and respondents |
| [Architecture](docs/architecture/ARCHITECTURE.md) | Modules, layers, decisions |
| [Diagrams](docs/diagrams/README.md) | ERDs per module, use cases, architecture |
| [Roadmap](ROADMAP.md) | Every step, with how it was tested |

## Reflection

- **Accessibility built in, not bolted on.** Shared components (dialogs,
  tabs, tables, toasts) were made accessible once, so every module got
  keyboard and screen-reader support for free.
- **Pages first, JavaScript second.** Every page works without JavaScript,
  which kept the Vue parts small and the testing simple.
- **Testing through the real app** caught problems unit tests missed, such
  as pages reached directly by URL and a database engine that quietly
  ignored foreign keys.
- **Small, logged steps** made a large scope manageable for one developer.

## Future work

Online payments for paid sessions and events, instant notifications and
project chat with WebSockets, a mobile app through a JSON API, a dedicated
search engine, and an Urdu interface.
