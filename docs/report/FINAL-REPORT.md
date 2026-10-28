# AQDesk — Final Project Report

| | |
|---|---|
| **Project** | AQDesk — an accessible community, learning and knowledge platform |
| **Course** | CS619 Final Year Project |
| **Student** | Titania Melody (BC220203981) |
| **Supervisor** | Mr. Abdullah Qamar |
| **Repository** | `S25-FutureGenius-TitaniaMelody_BC220203981` (branch `development`) |
| **Status** | **Draft 1 (roadmap step 5.2).** Sections marked ⏳ are completed after roadmap steps 3.14–3.18 (Proposals, Newsletter, Announcements) and 4.1–4.4 (quality, accessibility audit, usability study). |

---

## Abstract

AQDesk brings the services a student or early-career professional usually
finds on separate websites into one accessible platform: community questions
and answers, expert articles and learning paths, jobs and professional
opportunities, project collaboration, events and podcasts, consulting
sessions and newsletters. It was built as a Laravel 12 **modular monolith**
(nine feature modules and a shared platform layer) with server-rendered
Blade pages that work without JavaScript, enhanced by small Vue 3
components. Accessibility to WCAG 2.1 AA is treated as a requirement on every
page rather than as a final check: an accessibility toolbar, transcripts for
every podcast episode, keyboard-operable components and screen-reader
announcements are part of the design. Each feature was built in small
roadmap steps and verified by an automated suite of 363 PHPUnit tests (95%
line coverage) and a 273-check end-to-end test that drives the real
application over HTTP.

---

## 1. Introduction

### 1.1 Background

Students in Pakistan and elsewhere move between forums to ask questions,
job boards to find internships, social media for events, and messaging apps
to work on projects together. Each of these has its own account, its own
rules and, often, poor accessibility for people who use screen readers,
keyboards or large text. Knowledge given in one place (an expert's answer)
rarely connects to opportunities in another (a matching internship).

### 1.2 Problem statement

There is no single, accessible place where a learner can **ask, learn,
collaborate, apply and meet** experts, with clear governance over the
content and their own data.

### 1.3 Objectives

1. Provide the seven services of the FYP Requirement File (FR1–FR7) in one
   application with one account.
2. Add the two services of the project vision: **expert knowledge sharing**
   and **professional opportunities**.
3. Meet **WCAG 2.1 AA** on every page and make the accessibility features
   demonstrable.
4. Integrate **education technology** (learning paths), **community knowledge
   systems** (reputation, verified experts) and **digital governance**
   (moderation, decision log, data rights), as the FYP note asks.
5. Use a **modular** design so new services can be added without rewriting
   existing ones.

### 1.4 Scope

In scope: everything in Section 2. Out of scope: payments (consulting prices
are shown but not charged), a native mobile app, video hosting (webinars use
Jitsi, Zoom, Meet or Teams links), and real-time chat.

### 1.5 Report structure

Section 2 states the requirements, Section 3 the design, Section 4 the
implementation, Section 5 the testing and evaluation, Section 6 deployment,
and Section 7 reflects on the project and future work.

---

## 2. Requirements analysis

### 2.1 Users and roles

| Role | Who | Typical goals |
|---|---|---|
| **Guest** | Anyone not signed in | Browse questions, articles, events, podcasts, opportunities, consultants |
| **Member** | Students, community members | Ask and answer, follow learning paths, apply, join projects and events, book sessions |
| **Expert** | Professionals, instructors, employers, consultants | Everything a member does, plus publish opportunities, write articles (once verified), organize events, publish podcasts, offer consulting, build forms |
| **Administrator** | Platform team | Users, roles and permissions, moderation, expert verification, activity log |

Roles are bundles of **12 permissions** (for example `events.create`,
`consulting.offer`, `content.moderate`), editable by administrators on a
Roles & permissions screen, so a new role needs no code change.
Organizations (employers, event hosts) are profiles owned by experts rather
than a separate role.

### 2.2 Functional requirements

| ID | Requirement | Main features delivered |
|---|---|---|
| **FR1** | User management | Email registration with verification, Google/GitHub sign-in, password reset, login rate limiting, roles → permissions → policies, account settings (email preferences, profile privacy, signed-in devices), tabbed public profile |
| **FR2** | Community Q&A | Markdown questions/answers with code highlighting, comments, edit history with diffs, votes without page reload, accepted answers, reputation ledger, badges and reputation-gated privileges, tags and tag following, bookmarks, Newest/Trending/Votes/Unanswered sorting, related questions |
| **FR3** | Project collaboration | Project spaces with owner/collaborator/viewer roles, invitations (token, expiry, accept/decline), private file sharing with versions, feedback threads, task board |
| **FR4** | Events & podcasts | Events with capacity and numbered waitlist, registration through the Form Builder, webinar links (Zoom, Meet, Teams, Jitsi) shown only to seat-holders, `.ics` calendar files, scheduled reminders, podcast series and episodes with mandatory transcripts and an RSS feed |
| **FR5** | Jobs & opportunities | Organization profiles; jobs, internships, scholarships, fellowships, grants, hackathons, calls for papers; application pipeline with status history, messages and private reviewer notes; private CV storage; saved opportunities; automatic deadline closing; job alerts |
| **FR6** | Consulting | Consultant profiles, weekly hours and days off (time-zone and daylight-saving safe), conflict-free booking, private session rooms, client management with private notes, proposals Draft → Sent → Accepted/Rejected → Completed |
| **FR7** | Newsletter & announcements | Double opt-in subscription, one-click unsubscribe (RFC 8058), campaigns with scheduling and queued sending, delivery and open reports with retry of failures, site announcements and banners |
| Vision | Expert knowledge sharing | Expert verification (apply → admin review → verified badge), Knowledge Hub articles and guides |
| Vision | Professional opportunities | The five non-job opportunity types in FR5 |
| Note | Education technology | Learning Hub: resources, ordered learning paths, personal progress |
| Note | Digital governance | Community guidelines, report → moderation queue → decision log, audit trail, consent records, download my data, delete my account |
| Deliverable | Forms for creators and respondents | Form Builder: ten field types, draft/open/closed, response viewing and CSV export; reused for event registration |

### 2.3 Non-functional requirements

| Attribute | Requirement |
|---|---|
| Accessibility | WCAG 2.1 AA; every page usable by keyboard and screen reader; works without JavaScript |
| Security | No user can read or change another user's private data; uploads private by default; OWASP Top 10 basics (XSS, CSRF, injection, broken access control) |
| Privacy | Consent recorded; personal data exportable and erasable on request |
| Performance | Pages render server-side; slow work (email) runs on a queue |
| Maintainability | One module per service; validation, authorisation and business rules each in one place |
| Portability | Runs on WampServer (development), cPanel shared hosting and a Linux VPS |

### 2.4 Use cases

The use cases of every module and role are in
`docs/diagrams/10-use-case-final.mmd` (drawn in `docs/diagrams/README.md`);
the prototype's user journeys remain in `docs/diagrams/05-user-flow.svg`.

---

## 3. System design

The full design is in `docs/architecture/ARCHITECTURE.md`; this section
summarises the decisions.

### 3.1 Architecture

AQDesk is a **modular monolith**: one deployable Laravel application divided
into self-contained modules, each owning its routes, controllers, form
requests, policies, services, models, migrations and views.

| Module | Requirement |
|---|---|
| Identity | FR1 |
| Community | FR2 |
| Knowledge | Expert knowledge sharing, education technology |
| Careers | FR5, professional opportunities |
| Projects | FR3 |
| EventsMedia | FR4 |
| Consulting | FR6 |
| Communications | FR7 |
| Governance | Digital governance |
| Platform (shared) | Notifications, forms engine, file storage, Markdown, activity log, personal-data registry, accessibility preferences |

**Dependency rule:** Identity depends on no feature module; feature modules
depend only on Identity and Platform. A module adds relations to the `User`
model from its own service provider, so the dependency points one way.
Modules talk to each other through domain events (for example
`AnswerPosted`, `ApplicationStatusChanged`), not by writing each other's
tables.

Microservices were rejected (one developer, one database, shared hosting);
a flat Laravel app was rejected because ~65 tables in one folder hide which
code owns which data. See ADR-01 to ADR-07 in the architecture document.

### 3.2 Layers

| Layer | Responsibility |
|---|---|
| Blade page / Vue island | Presentation; every page works without JavaScript |
| Form Request | Validation, nothing else |
| Policy | "May this person do this at all?" → 403/404 |
| Controller | Thin: validate, authorise, call one service, return a view |
| Service | Business rules and every write (e.g. "no booking may overlap") |
| Model | Eloquent model with query scopes |
| Infrastructure | MySQL, database queue, scheduler, private/public storage, SMTP |

### 3.3 Data design

One MySQL 8 schema (`utf8mb4`) with about **71 tables** created by 47
module migrations. Key decisions:

- **Polymorphic relations** with short aliases (`question`, `answer`) for
  votes, comments, reports and bookmarks; any model without an alias is
  refused, so stored data survives code reorganisation.
- **Ledgers instead of counters** where history matters: reputation events,
  application status changes, moderation decisions, file versions.
- **Database-enforced rules** as a second line of defence: a unique slot key
  on consultations stops two bookings of the same slot even if two requests
  race past the service checks.
- **UTC storage** for every time, with each event or consultant's own time
  zone stored beside it for display.

The ERDs (an overview and one per module, `docs/diagrams/erd/`) and the
schema export (`schema/aqdesk_schema.sql`, 71 tables, 99 foreign keys) are
generated from a migrated MySQL database by `php artisan docs:schema`, so
they always match the migrations. Every table uses InnoDB, which the
booking and waitlist row locks depend on.

### 3.4 Security design

| Threat | Control |
|---|---|
| Broken access control | Permissions as Gate abilities on routes; a Policy per model; other people's private records answer 404, not 403, so their existence is not revealed |
| XSS | Blade escaping; Markdown rendered with raw HTML escaped and `javascript:` links removed |
| CSRF | Token on every form and JSON request |
| Brute force | 5 failed sign-ins → lockout; throttled verification, reset, voting and saving |
| File exposure | CVs, project files, evidence documents and podcast audio on a private disk, streamed only after a policy check |
| Account takeover via OAuth | Social sign-in links to an existing account only when the provider confirms the same email |
| Accountability | Activity log of administrative and moderation actions; moderation decision log |

### 3.5 Accessibility design

- Semantic landmarks, skip link, one `h1` per page, labelled controls,
  `aria-describedby` for hints and errors, visible focus.
- Status shown with **icon + text**, never colour alone.
- Shared Blade components (`x-page-header`, `x-card`, `x-tabs`,
  `x-filter-panel`, `x-empty`, `x-status`) built accessible once.
- Accessible confirm dialog (native `<dialog>`, focus starts on Cancel,
  returns to the trigger) and toasts announced through a live region.
- **Accessibility toolbar**: text size, high contrast, dyslexia-friendly
  font, reduced motion, line spacing; saved to the profile or the browser.
- Podcast episodes cannot be published without a transcript (WCAG 1.2.1).
- Tables become labelled cards on phones; light and dark themes.

### 3.6 User interface

Bootstrap 5 with an AQDesk token layer (`public/css/aqdesk.css`; no inline
styles anywhere). Six Vue 3 islands enhance plain forms: `VoteControl`,
`BookmarkButton`, `FollowButton`, `MarkdownEditor`, `CharacterCounter`,
`LineChart`. Role dashboards show charts, a To-do card and recent activity.
⏳ Screenshots are added in the final version.

---

## 4. Implementation

### 4.1 Technology

| Part | Choice |
|---|---|
| Language / framework | PHP 8.2+ / Laravel 12 |
| Database | MySQL 8 (development: WampServer; tests: in-memory SQLite) |
| Front end | Blade, Bootstrap 5, Bootstrap Icons, Vue 3.5 via Vite 8 |
| Libraries | Laravel Socialite (Google/GitHub), league/commonmark (Markdown), highlight.js |
| Tooling | Git and GitHub, Composer, npm, PHPUnit 11 |

### 4.2 Size

| Measure | Count |
|---|---|
| PHP files in `app/` | 443 (≈ 26,500 lines) |
| Routes | 252 |
| Blade views | 150 |
| Tables / migrations | 71 / 47 |
| Permissions | 12 |
| Automated tests | 363 PHPUnit (358 feature, 5 unit) + 273 end-to-end checks |

### 4.3 Development method

The work followed a 61-step roadmap (`ROADMAP.md`) in five phases:
foundation, rebuilding the prototype's modules, missing scope, new modules,
quality and deliverables. Each step was built, tested automatically, logged
with what changed and how it was tested, and approved before the next step
started. Work was committed to the `development`
branch in groups of steps.

### 4.4 Notable implementation details

- **Conflict-free booking (FR6).** A booking is accepted only if, with the
  consultant's row locked, the start is exactly one of the consultant's
  computed free slots (weekly hours minus days off, notice period, booking
  window and existing sessions) and the client has nothing else at that
  time. A unique slot key makes the database refuse a second booking of the
  same slot as a last line of defence.
- **Waitlist (FR4).** Seats are first come, first served under a row lock;
  cancelling, removing someone, raising capacity or deleting an account
  promotes the next people and tells them.
- **Exactly-once reminders (FR4).** Each reminder is claimed in the database
  before it is sent, so overlapping scheduler runs never send it twice.
- **Calendar files.** `.ics` files follow RFC 5545 (UTC times, escaping,
  75-byte line folding, sequence numbers so updates replace the saved event).
- **Reputation ledger (FR2).** Every vote and acceptance writes or reverses a
  line; totals can be rebuilt from the ledger with one command.
- **Personal-data registry (governance).** Each module declares what it
  exports and how it erases a user, so "Download my data" and "Delete my
  account" cover every module without Governance knowing their tables.
- **Queued notifications (5.1).** Every notification is delivered in-app and
  by email (per category, switchable) by the queue worker, after the
  database transaction that raised it commits.

---

## 5. Testing and evaluation

### 5.1 Strategy

| Level | Tool | What it proves |
|---|---|---|
| Unit tests | PHPUnit 11, no database | Rules in plain classes and models: edit-history diff, permission catalogue, proposal lifecycle, confirmation expiry, announcement phases |
| Feature tests | PHPUnit 11 on in-memory SQLite, PHP 8.3 and 8.4/8.5 | Business rules, permissions, validation and edge cases per module (e.g. no double booking, drafts 404 for others, exports) |
| End-to-end | `src/tests/e2e/smoke.sh` | Starts the real application on a throw-away database and drives it over HTTP with real sessions and CSRF tokens as guest, member, expert and admin; any PHP warning fails the run |
| Manual | Browser checks on WampServer | Pages load on MySQL after each migration |

Test cases are grouped in suites with IDs (for example BK1–BK8 for booking,
DP1–DP5 for deployment) and listed with results in the progress log of
`ROADMAP.md`. The catalogue per module is in `docs/testing/TEST-SUITE.md`.

### 5.2 Results

| Run | Result |
|---|---|
| PHPUnit | 363 tests, 3,182 assertions, all pass on PHP 8.3 and 8.5 (1 skipped: a manual keyboard check) |
| Code coverage (Xdebug) | 95.0% of lines and 91.0% of methods in `src/app`; every module 93% or more of lines |
| End-to-end | 273 of 273 checks pass |

A coverage run pointed to untested paths (for example a moderator removing a
reported article, deleting forms and draft campaigns), which suite GP now
covers; the unit tests found and fixed a small bug in the edit-history diff.

### 5.3 Performance

- **N+1 queries removed.** Running the suite with Laravel's lazy-loading guard
  found 115 relations loaded one row at a time (each avatar's profile, each
  person's role, authors and tags on the profile's question list). They are
  now loaded together, and the guard stays on in tests so a new one fails the
  suite; four list pages make the same number of queries with twice the rows.
- **Indexes** for the opportunity board, the daily deadline closing and the
  notification bell's unread count, alongside the existing list indexes.
- **Caching** of the home page totals (10 minutes) and of the live
  announcement banners read on every page (1 minute, cleared on any change).
- Searches use `LIKE`, which indexes cannot speed up; a FULLTEXT index or a
  search engine is the next step if content grows (Section 7.4).

### 5.4 Accessibility evaluation

Full audit: `docs/testing/ACCESSIBILITY-AUDIT.md`.

**Automated (done).** A PHPUnit audit opens every page as a visitor, a member
and an administrator plus the detail and edit pages of every module (135 page
views) and checks language, skip link, landmarks, one `h1` and heading order,
labels on every form control, image alternatives, link and button names,
unique ids and valid ARIA references. axe-core 4.14 ran its WCAG 2.1 A/AA
rules on 52 pages of the running application: **0 violations**. Colour
contrast is computed for every text colour on every background in both
themes: all pass, lowest normal-text pair 4.76:1. Four issues were found and
fixed: empty-state headings skipping a level (17 pages), resource cards
without a section heading, muted text at 4.36:1 on tinted backgrounds
(darkened), and inline styles in the bar charts. These checks now run with
the test suite.

**Manual ⏳.** Keyboard-only walk-through, NVDA screen-reader pass,
Lighthouse and WAVE scores, recorded in part B of the audit.

### 5.5 Usability study ⏳

Plan, consent text, nine tasks across the modules, the SUS questionnaire
with scoring, and result tables: `docs/testing/USABILITY-STUDY.md`. Results
(participants, completion rates, times, SUS score, changes made) are added
after the sessions.

### 5.6 Requirement coverage

| Requirement | Status |
|---|---|
| FR1–FR7 | Delivered and tested |
| Expert knowledge sharing, education technology, digital governance, forms | Delivered and tested |
| Accessibility | Automated audit: 0 issues on every page; manual checks ⏳ |

---

## 6. Deployment

The deployment design, the production settings template
(`src/.env.production.example`), the deploy script (`src/deploy.sh`) and
step-by-step instructions for a DigitalOcean Ubuntu server and for cPanel
hosting are in `docs/DEPLOYMENT.md`. In short: the document root is
`src/public`; HTTPS from Let's Encrypt or cPanel AutoSSL; SMTP mail; one
cron entry runs the scheduler (reminders, deadline closing, pruning); a
Supervisor-managed queue worker sends notifications, or on cPanel the same
cron drains the queue. No Node.js is needed on the server because built
assets are committed. ⏳ Live demo URL: to be added.

---

## 7. Conclusion

### 7.1 Achievements

AQDesk delivers, in one accessible application, the community Q&A, careers,
project, events and podcast, consulting and governance services of the
requirement file, plus the expert-knowledge and learning features of the
project vision, with every feature covered by automated tests.

### 7.2 Reflection

- **Accessibility from the start is cheaper than at the end.** Building the
  shared components accessible once (dialogs, tabs, tables, toasts) meant
  every later module inherited keyboard and screen-reader support.
- **No-JavaScript first** kept every page simple to test and made the Vue
  components small enhancements rather than the whole application.
- **Testing through the real HTTP flows** caught problems unit tests miss,
  such as permissions on pages reached directly by URL.
- **Small steps with a written log** kept a large scope manageable for one
  developer and made progress easy to show the supervisor.

### 7.3 Limitations

- Consulting prices are displayed but not charged; no payment gateway.
- Notifications refresh by polling every 30 seconds rather than WebSockets.
- Search uses database queries rather than a dedicated search engine.
- Webinars rely on external meeting links; there is no built-in video.

### 7.4 Future work

- Payment integration (e.g. a local gateway) for paid sessions and events.
- WebSockets (Laravel Reverb) for instant notifications and chat in projects.
- A JSON API for a mobile app.
- Full-text search engine (Meilisearch) across all content.
- Multilingual interface (Urdu and English).

---

## References

1. W3C, *Web Content Accessibility Guidelines (WCAG) 2.1*, 2018. https://www.w3.org/TR/WCAG21/
2. Laravel, *Laravel 12.x Documentation*. https://laravel.com/docs/12.x
3. Vue.js, *Vue 3 Guide*. https://vuejs.org/guide/
4. IETF, *RFC 5545 — Internet Calendaring and Scheduling (iCalendar)*, 2009.
5. OWASP Foundation, *OWASP Top 10*, 2021. https://owasp.org/Top10/
6. J. Brooke, "SUS: A quick and dirty usability scale", in *Usability Evaluation in Industry*, 1996.

## Appendices

- A. Architecture document — `docs/architecture/ARCHITECTURE.md`
- B. Roadmap and test log — `ROADMAP.md`
- C. Setup guide — `docs/SETUP-GUIDE.md`
- D. Deployment guide — `docs/DEPLOYMENT.md`
- E. Diagrams — `docs/diagrams/`
- F. Database schema — `schema/aqdesk_schema.sql`
- G. User Manual (incl. form creators and respondents) — `docs/USER-MANUAL.md`
