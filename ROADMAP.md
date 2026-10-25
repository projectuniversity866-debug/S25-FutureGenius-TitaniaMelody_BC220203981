# AQDesk — Final Project Roadmap

Step-by-step tracker for taking the AQDesk prototype to the full FYP.
Two steps per go: build → **self-test** → mark done → report → approve → next two steps.

**Self-test (part of every step):** Claude runs the project itself before
marking a step done: the PHPUnit suite on PHP 8.3 and 8.4, and the end-to-end
smoke test `src/tests/e2e/smoke.sh`, which starts the app on a throw-away
database and clicks through the real flows as guest, member, expert and admin.
Results go in the progress log.

**Legend:** `[ ]` To do · `[~]` In progress · `[x]` Done & tested

**Sources:** FYP Requirement File (FR1–FR7, Deliverables, Note) · Prototype Assignment (Vision, Deliverables)

| Phase | Title | Steps | Done |
|---|---|---|---|
| 0 | Foundation | 11 | 11 |
| 1 | Rebuild existing modules | 15 | 5 |
| 2 | Missing scope | 7 | 0 |
| 3 | New modules (FR3, FR4, FR6, FR7) | 18 | 0 |
| 4 | Quality | 4 | 0 |
| 5 | Deployment & deliverables | 6 | 0 |
| | **Total** | **61** | **16** |

**Next steps:** 1.6 + 1.7

---

## Phase 0 — Foundation

| Status | ID | Step | Output |
|---|---|---|---|
| [x] | 0.1 | Merge this working folder with the `S25-FutureGenius-…` git repository | One repo with `/docs` `/src` `/schema` `README.md` `presentation.pptx` |
| [x] | 0.1b | Fix the 6 pre-existing failing tests (deactivated-user lockout, guest access, job applications) | Green test suite |
| [x] | 0.2 | Architecture document (modules, layers, data flow) | Diagram + one-page summary for supervisor sign-off |
| [x] | 0.3 | Modular structure `app/Modules/{Identity, Community, Knowledge, Careers, Projects, Events, Consulting, Communications, Governance}` | Modular folder layout |
| [x] | 0.4 | Layered pattern: Services, Form Requests, Policies, thin controllers | Convention applied to all modules |
| [x] | 0.5 | Vue 3 + Vite alongside Bootstrap | Frontend build pipeline |
| [x] | 0.6a | Design system foundation: Blade component library (page header, card, stat, badge, avatar, empty state), type scale, all inline styles removed, prototype leftovers removed, visual bugs fixed | Consistent screens |
| [x] | 0.6b | Data display: responsive data tables (cards on phones), filter panel, tabs | Professional list screens |
| [x] | 0.6c | Feedback: accessible confirm dialog (replaces browser `confirm()`), toast notifications | Polished interactions |
| [x] | 0.6d | Role dashboards with charts (Admin / Expert / Member) | Data-rich dashboards |
| [x] | 0.7 | Shared services: notifications (in-app + email), private file storage, queues, scheduler, activity log | Platform infrastructure |

## Phase 1 — Rebuild existing modules

### FR1 Identity & Access

| Status | ID | Step | Output |
|---|---|---|---|
| [x] | 1.1 | Social login (Google, GitHub) | Socialite integration |
| [x] | 1.2 | Email verification + forgot/reset password | Complete auth flows |
| [x] | 1.3 | Permissions table, role–permission mapping, Policies | Fine-grained RBAC |
| [x] | 1.4 | Login rate limiting, active sessions, account & privacy settings | Hardened accounts |

### Dashboard & Profile

| Status | ID | Step | Output |
|---|---|---|---|
| [x] | 1.5 | Role-specific dashboards (Admin / Expert / Member) with charts, activity feed, pending tasks | Professional dashboards |
| [ ] | 1.6 | Tabbed profile (Overview, Questions, Answers, Activity), expertise, reputation & badges | Rich profile |

### FR2 Community Q&A

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 1.7 | Markdown editor, safe rendering, code highlighting | Rich content |
| [ ] | 1.8 | Comments on questions/answers, edit history | Discussion depth |
| [ ] | 1.9 | Reputation points, badges, reputation-gated privileges | Gamification |
| [ ] | 1.10 | Sorting (Newest / Votes / Unanswered / Trending), follow tags, bookmarks, related questions | Discovery |
| [ ] | 1.11 | Vue voting without page reload + notifications | Interactive Q&A |

### FR5 Careers

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 1.12 | Organization (employer) profiles | Employer pages |
| [ ] | 1.13 | Professional Opportunities type: scholarships, fellowships, grants, hackathons, calls for papers | Vision requirement covered |
| [ ] | 1.14 | Application pipeline with status history & reviewer notes; move CVs to a private disk | Secure ATS-style flow |
| [ ] | 1.15 | Saved jobs, automatic deadline closing (scheduler), job alerts | Career tools |

## Phase 2 — Missing scope

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 2.1 | Expert verification: apply → admin review → verified badge | Expert workflow |
| [ ] | 2.2 | Knowledge Hub: expert articles & guides by category | Expert knowledge sharing |
| [ ] | 2.3 | Learning Hub: resources & learning paths per category | EdTech integration |
| [ ] | 2.4 | Form Builder: creators build forms, respondents submit (events, consulting intake, job questions, surveys) | Forms engine |
| [ ] | 2.5 | Governance: community guidelines, report/flag → moderation queue → decision log, admin audit trail | Digital governance |
| [ ] | 2.6 | Privacy: terms/privacy consent, export my data, delete my account | Data rights |
| [ ] | 2.7 | Accessibility toolbar (text size, high contrast, dyslexia font, reduced motion, line spacing) + accessibility statement | Demonstrable accessibility |

## Phase 3 — New modules

### FR3 Project Collaboration

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 3.1 | Project spaces with member roles (owner / collaborator / viewer) | Projects core |
| [ ] | 3.2 | Invitations with token, expiry, accept/decline | Invite collaborators |
| [ ] | 3.3 | File sharing with versions (private storage) | Share files |
| [ ] | 3.4 | Feedback threads | Provide feedback |
| [ ] | 3.5 | Task board | Project tracking |

### FR4 Events & Podcasts

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 3.6 | Events with capacity & waitlist | Event creation |
| [ ] | 3.7 | Event registration (via Form Builder) | Event registration |
| [ ] | 3.8 | Webinar links (Zoom / Meet / Jitsi) + `.ics` calendar export | Webinar integration |
| [ ] | 3.9 | Scheduled reminders | Automated emails |
| [ ] | 3.10 | Podcast series, episodes, transcripts | Podcast publishing |

### FR6 Consulting Management

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 3.11 | Consultant profiles & availability slots | Consultant setup |
| [ ] | 3.12 | Conflict-free booking | Consultation booking |
| [ ] | 3.13 | Client management | Client CRM |
| [ ] | 3.14 | Proposals: Draft → Sent → Accepted / Rejected → Completed | Proposal tracking |

### FR7 Newsletter & Announcements

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 3.15 | Subscribe with double opt-in, one-click unsubscribe | Newsletter subscription |
| [ ] | 3.16 | Campaign builder, scheduling, queued sending | Email campaign management |
| [ ] | 3.17 | Delivery tracking | Campaign reports |
| [ ] | 3.18 | Site announcements & banners | Community updates |

## Phase 4 — Quality

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 4.1 | Feature + unit tests for every module | Test suite |
| [ ] | 4.2 | Performance: indexes, eager loading, caching | Scalability |
| [ ] | 4.3 | WCAG 2.1 AA audit (axe, Lighthouse, WAVE, keyboard, screen reader) | Accessibility evidence |
| [ ] | 4.4 | Usability study (tasks + SUS score) | Testing Report |

## Phase 5 — Deployment & deliverables

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 5.1 | Cloud deployment (cPanel / DigitalOcean / AWS), SMTP, HTTPS, cron, queue worker | Live demo URL |
| [ ] | 5.2 | Final Report (analysis, design, implementation, evaluation) | Report |
| [ ] | 5.3 | User Manual (incl. form creators & respondents) | Manual |
| [ ] | 5.4 | Updated ERD, diagrams, `/schema` SQL | Design artefacts |
| [ ] | 5.5 | README (overview, setup, features, reflection, future work) | Repo docs |
| [ ] | 5.6 | Demo video showing accessibility features | Presentation |

---

## Progress log

| Date | Step | Notes |
|---|---|---|
| 2026-10-25 | 0.1 | This folder is now the git repo (branch `development`, remote `origin` = GitHub `S25-FutureGenius-TitaniaMelody_BC220203981`). Copied in `.git`, `schema/`, `docs/SETUP-GUIDE.md`, diagram sources (`.mmd`/`.png`), `GIT-SETUP.md`, `.gitignore`. Kept this folder's newer code (2 bug fixes). `.env.testing` added to `.gitignore`. Tests switched back to in-memory SQLite (`phpunit.xml`): 75 pass, 6 fail (pre-existing), 1 skipped. Old folder left untouched as a backup. Nothing committed yet. |
| 2026-10-25 | 0.1b | All 6 failures were test bugs, not app bugs: tests stayed signed in via `actingAs()` when they meant to be a guest, and job tests counted the member's seeded applications. Added `signOut()` helper to `AqDeskTestCase`, scoped job counts to the job, removed Laravel's boilerplate `ExampleTest`, fixed PHP 8.4 / PHPUnit 12 deprecations. Result: 81 passed, 0 failed, 1 skipped (manual keyboard check). |
| 2026-10-25 | 0.2 | Wrote `docs/architecture/ARCHITECTURE.md` (modular monolith, 9 modules + platform layer, layers, request lifecycle, data, security, accessibility, async, deployment, 7 ADRs, requirement traceability, 5 Mermaid diagrams) and `docs/architecture/SUPERVISOR-SUMMARY.md` (one page, 7 decisions to approve). Supervisor approval still pending. Note: Node.js is not installed — needed before step 0.5. |
| 2026-10-25 | 0.3 | Code moved into `app/Modules/{Identity, Community, Careers}` (models, controllers, middleware, migrations, seeders, views, routes — 60 files moved with git history). Base `ModuleServiceProvider` auto-loads each module's routes, namespaced views (`community::…`), migrations, morph aliases and cross-module relations. Six skeleton modules registered (Knowledge, Projects, EventsMedia, Consulting, Communications, Governance). Identity no longer depends on feature modules (`resolveRelationUsing`). `ModerationController` split per module. Votes now store aliases `question`/`answer`; migration converted 30 existing votes. Fixed: dependencies required PHP 8.4 but WAMP runs 8.3 → pinned Composer platform to PHP 8.2 and re-resolved (Symfony 8 → 7.4 LTS, no advisories). Same 51 routes; 81 tests pass on PHP 8.3 and 8.4; all pages 200 in WAMP. Found for later (4.3): vote label reads "1 votes". |
| 2026-10-25 | 0.4 | Layered pattern applied to all 12 controllers. Added 4 Policies (Question, Answer, JobPost, JobApplication — registered via `policies()` in each module provider), 11 Form Requests, 8 Services (Question, Answer, Vote, JobPost, Application, Registration, Profile, UserAdministration). Controllers now only call request → policy → service; no inline validation, `abort_*`, DB or Storage calls left. Views use the same policies (`$me->can(...)`) instead of copied rules. Fixed: a poster could apply to their own opportunity by submitting the form directly — now refused (403). Changed: reopening the apply form after applying now redirects to the opportunity with a message instead of a 403 page. 3 new tests (F13–F15) + closed-question POST check in E16: 84 pass on PHP 8.3 and 8.4; pages 200 in WAMP. |
| 2026-10-25 | 0.5 | Installed Node.js 24 LTS (winget). Added Vite 8 + Vue 3.5 (`package.json`, `vite.config.js`, packages pinned to releases ≥ 2 weeks old, 0 vulnerabilities). `resources/js/app.js` mounts Vue "islands" on `data-vue="Name"` placeholders; `@vite` in both layouts. Build output `public/build` is committed so the app runs without Node. First component: `CharacterCounter` (live count on question title/body, answer, cover letter; announces state changes to screen readers, not every keystroke). Bug caught in headless-Edge check and fixed: every island generated the same id (`v-0`) → per-island `idPrefix`. Tests no longer depend on the build (`withoutVite()`): 84 pass on PHP 8.3/8.4, with and without `public/build`; script served correctly from the WAMP sub-folder URL. |
| 2026-10-25 | Process | Self-testing added to every step. New `src/tests/e2e/smoke.sh`: runs the real app (PHP 8.3, same as WAMP) on a throw-away SQLite database and drives 49 checks over HTTP with real sessions and CSRF tokens — guest pages, sign-in/out, ask/edit/answer/vote/accept, publish job/apply/re-apply/shortlist, permission refusals (403), admin pages and moderation; any PHP warning page fails the run. Retro-verified steps 0.3–0.5: 49 passed, 0 failed; MySQL data untouched. |
| 2026-10-25 | 0.6 split | Design system split into 0.6a–0.6d (foundation · data display · feedback · dashboards). |
| 2026-10-25 | 0.6a | Blade component library in `resources/views/components` (page-header with breadcrumbs, card, stat, status, avatar xs–xl, org-mark, empty, vue) + type scale and text utilities in `aqdesk.css`. Inline styles 110 → 0 across 26 views; 14 page headers and 9 empty states moved to components; old avatar/empty partials and `statusColor()` removed. Prototype leftovers removed: "Live modules" footer text, "Planned for Phase 2" cards, "Phase 2 / soon" sidebar labels (now "Coming soon"), "Prototype Phase" on sign-in. Fixed: every `<select>` overlapped its arrow (global padding); plurals ("1 questions", "1 votes", "1 answers", "1 views"); vote counts unreadable to screen readers (aria-label on a div); missing favicon (404). New `tests/e2e/screens.sh` (puppeteer-core + installed Edge): screenshots 42 screens per theme and runs browser checks. It caught 3 bugs, all fixed: **step 0.5's character counter never received its settings on real pages** (`@json` in an HTML attribute → invalid JSON; fixed with `<x-vue>`), a duplicate class attribute on the register role cards, and a duplicated breadcrumb on Applicants. Results: 84 PHPUnit pass (PHP 8.3 + 8.4), e2e smoke 49/49, 6/6 browser checks, 42 screens × light/dark all 200 with no horizontal overflow on phones, 0 browser errors. npm audit 0 (zod pinned to 4.6.6). |
| 2026-10-25 | 0.6b | Responsive data tables (`aq-table` + `data-label`): rows become labelled cards below 768 px; `app.js` restores table roles for screen readers. Applied to Manage users, Moderate questions, Moderate opportunities, My postings, My applications. New `<x-filter-panel>` (active filters as removable chips, "Clear all", result count, inline or stacked) on all 5 filterable lists; new `<x-tabs>` (link navigation with `aria-current` and counts) for Q&A sort, job type, and applicant status. Applicants page gained a validated `?status=` filter with per-status counts. Action buttons got hidden context for screen readers ("Delete “title”"). Fixed: 2 more duplicate `class` attributes (role and status dropdowns) that the 0.6a scan missed because `->` contains `>`; removed unused `.aq-toolbar`. Before: on phones the admin Role/Access columns and application status were cut off. Tests: 87 PHPUnit (3 new: H7 filter chips, H8 tabs, F16 status filter) on PHP 8.3 + 8.4; e2e smoke 49/49; 18/18 browser checks (cards on phone for all 3 admin tables, every cell and button on-screen at 390 px, roles restored, desktop unchanged, chip removal keeps other filters); light + dark screenshots reviewed. |
| 2026-10-25 | 0.6c | All 6 browser `confirm()` pop-ups replaced by one accessible confirmation dialog (`partials/confirm-dialog`, native `<dialog>`, `resources/js/feedback.js`): forms opt in with `data-confirm`, `data-confirm-title`, `data-confirm-button`; specific wording per action (e.g. "Delete “title”?" + how many answers/applications go with it). Focus starts on Cancel, Escape/backdrop cancel and return focus to the triggering button; buttons stack on phones. Added a confirmation to Deactivate user (had none). Success messages are now toasts (`partials/toasts`): pause on hover/focus, dismissible, 8 s, visible without JS; an initially empty live region announces them (pre-filled live regions are not read on page load). Errors stay as in-page alerts. Tests: 89 PHPUnit (H9 confirm markup, H10 toast) on PHP 8.3 + 8.4; e2e smoke 49/49; 28/28 browser checks light + dark (dialog opens, focus on Cancel, Escape cancels and restores focus, cancel deletes nothing, confirm deletes, toast shown + announced + dismissible, dialog fits 360 px); dialog and toast screenshots reviewed in both themes, desktop and phone. |
| 2026-10-25 | 0.6d | Three role dashboards (`dashboard/admin`, `expert`, `member` + shared `_welcome`) fed by a new read-only `App\Services\DashboardMetrics`; `DashboardController` reduced to picking the view. Admin: 4 tiles, 14-day community activity line chart, questions by topic, applications by status, "Needs an answer", newest members. Expert: answers, acceptance rate, open postings, applicants; questions to answer; latest applicants; applicants per opportunity; hiring pipeline. Member: questions, answers, votes received, applications; my questions; community activity; my applications; opportunities not yet applied to. Stat tiles gained "▲ n this week" and a 14-day sparkline. New `<x-bar-list>` (one hue, values at tips, faint track so zero reads as empty) and `<x-line-chart>` + `LineChart.vue` (legend with line keys, collision-aware end labels, hairline grid, whole-number axis, crosshair tooltip for every series, arrow-key reading with live announcements, resizes to container) with a server-rendered "View as table" under every chart. Chart colours validated with the dataviz palette validator (CVD separation, normal-vision floor, contrast, lightness band): light `#0d9488/#eb6834` on #ffffff, dark `#22a896/#dc6a33` on #14202b — the brand teal `#0f766e` failed the chroma floor and was not used for series. Fixed during review: fractional y-axis ticks (0.5) on counts; unbalanced admin layout. Tests: 92 PHPUnit (C6 charts + table view, C7 today's post lands in today's slot, C8 acceptance rate and votes received) on PHP 8.3 + 8.4; e2e smoke 49/49; 36/36 browser checks (2 lines, legend, 14-row table, hover tooltip lists both series, keyboard reads each day, fits 390 px, expert/member tiles); all three dashboards 200 on WAMP; screenshots reviewed light/dark, desktop/phone. |
| 2026-10-25 | Process | User asked for two steps per go to save time. |
| 2026-10-25 | 0.7 | New **Platform** module (`app/Modules/Platform`): (1) notifications — `notifications` table, `AppNotification` base (in-app + email, same wording/link), bell in the top bar with unread count and latest five, Notifications page, open-and-mark-read (only your own; internal links only), mark all read; first notifications: new answer → question author, answer accepted → answerer, new applicant → poster, status change → applicant (never for your own action; no repeat when status unchanged); modules raise domain events (`AnswerPosted`, `AnswerAccepted`, `ApplicationSubmitted`, `ApplicationStatusChanged`) and listen via `listeners()` in their provider. (2) Activity log — `activity_logs` table, `ActivityLogger` service, admin **Activity log** page with action filter; records role changes, (de)activation, question close/re-open/delete, opportunity publish/unpublish/delete, application status changes, with before/after values. (3) Private files — `PrivateFiles` service on the non-public disk; **CVs moved off the public disk** (migration moved existing files) and downloadable only by the applicant, the poster or an admin (part of 1.14 done early). (4) Scheduler — daily prune of activity log (365-day retention) and failed jobs; cron documented. Notifications are sent synchronously until production runs a queue worker (5.1). |
| 2026-10-25 | 1.1 | Google and GitHub sign-in (Laravel Socialite 5.31.0, no advisories). `social_accounts` table, `SocialLoginService`: linked identity → that account; same **verified** email → link existing account; otherwise new Member account with verified email. Unverified Google email refused (prevents account takeover); deactivated accounts stay locked out; cancelled/failed sign-in returns to login with a message. Callback URL built from the route (works in the WAMP sub-folder). Buttons only for providers with keys in `.env`; setup steps in `docs/SETUP-GUIDE.md`. **To use for real, OAuth keys must be created (Google Cloud Console, GitHub Developer settings).** |
| 2026-10-25 | Tests 0.7 + 1.1 | 108 PHPUnit pass on PHP 8.3 + 8.4 (16 new: P1–P8 platform, S1–S8 social login with a faked provider); 2 older tests adjusted because the bell now (correctly) names the people involved; e2e smoke 49/49; browser run: Sara answers → member's bell "1 unread" → click lands on `#answer-8` and clears it; deactivation appears in Activity log; login shows Google/GitHub buttons; no browser errors. |
| 2026-10-25 | 1.2 | Email verification: `User` implements `MustVerifyEmail`; registration sends one confirmation email; signed, throttled verify link; "send again"; changing your email requires confirming the new one. Unconfirmed accounts can browse and use the dashboard but posting routes (Q&A, voting, applying, publishing) carry `verified` → "Confirm your email" page; reminder banner with "Resend link" on every page. Existing and demo accounts marked verified (migration + seeder) so nobody is locked out. Password reset: "Forgot password?" on sign-in → emailed single-use link (60 min) → new password; same reply whether or not the address exists (no account enumeration); throttled. Social-login users can use it to set a password. |
| 2026-10-25 | 1.3 | Fine-grained RBAC: `permissions` + `permission_role` tables, `PermissionCatalog` (7 permissions in Community / Careers / Administration with default roles), `User::hasPermission()`, each slug a Gate ability. All `role:` route guards replaced by `can:<permission>` and the `EnsureUserHasRole` middleware removed; policies use permissions (post, vote, apply, publish, moderate); sidebar/dashboard links follow permissions. New **Roles & permissions** admin screen (matrix of checkboxes per role); Administrators can never lose "Manage users, roles and permissions"; every change logged with what was added/removed. Defaults reproduce the previous access exactly (checked on the WAMP database: admin/expert/member). |
| 2026-10-25 | Tests 1.2 + 1.3 | 123 PHPUnit pass on PHP 8.3 + 8.4 (15 new: V1–V8 verification and reset incl. tampered link and invalid token; R1–R7 permissions incl. revoke publish/vote, grant moderation, lock-out guard, logging); A4 updated (experts must confirm email before publishing). e2e smoke 49/49. Browser: new sign-up → "Ask" lands on /email/verify with banner; Roles & permissions matrix; forgot-password on phone shows toast; no browser errors. Fixed after review: envelope icon on the confirm-email page was unstyled. |
| 2026-10-25 | 1.4 | Sign-in rate limiting in `LoginRequest`: 5 wrong passwords per email + IP → one-minute lockout with countdown (`Lockout` event), cleared on success. New **Account settings** page (`/settings`, linked from the account menu): email-notification preferences per category ("answers", "applications"; in-app always on — `AppNotification::category()` + `User::wantsEmailFor()`), profile privacy (public / signed-in members only — `UserPolicy::viewProfile`, guests get 404), and "Where you're signed in" (device, address, last active from the database session store) with "Sign out everywhere else" confirmed by password (`Auth::logoutOtherDevices` also kills remember-me cookies). Settings stored as JSON on `users.settings` with defaults in `User::DEFAULT_SETTINGS`. |
| 2026-10-25 | 1.5 | Dashboards (charts were already done in 0.6d) gained a **To do** card — actionable, linked items chosen by permissions: confirm email, questions unanswered > 2 days (moderators), pending applicants per posting (publishers), accept an answer on your own questions, complete your profile — and a **Recent activity** feed (audit trail for `activity.view`, otherwise your notifications). |
| 2026-10-25 | Tests 1.4 + 1.5 | 131 PHPUnit pass on PHP 8.3 + 8.4 (8 new: K1 lockout even with the right password, K2 email preference keeps in-app only, K3 members-only profile 404 for guests, K4 device list + sign-out-others with wrong/right password, K5 invalid setting, K6–K8 to-do and feed per role). e2e smoke 49/49. Screenshots on WAMP: settings page lists real sessions ("Edge on Windows — This device"), expert To do links to pending applicant; no browser errors. |
