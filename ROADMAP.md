# AQDesk — Final Project Roadmap

Step-by-step tracker for taking the AQDesk prototype to the full FYP.
One step is done per session: build → mark done → test → approve → next step.

**Legend:** `[ ]` To do · `[~]` In progress · `[x]` Done & tested

**Sources:** FYP Requirement File (FR1–FR7, Deliverables, Note) · Prototype Assignment (Vision, Deliverables)

| Phase | Title | Steps | Done |
|---|---|---|---|
| 0 | Foundation | 8 | 2 |
| 1 | Rebuild existing modules | 15 | 0 |
| 2 | Missing scope | 7 | 0 |
| 3 | New modules (FR3, FR4, FR6, FR7) | 18 | 0 |
| 4 | Quality | 4 | 0 |
| 5 | Deployment & deliverables | 6 | 0 |
| | **Total** | **58** | **2** |

**Next step:** 0.2

---

## Phase 0 — Foundation

| Status | ID | Step | Output |
|---|---|---|---|
| [x] | 0.1 | Merge this working folder with the `S25-FutureGenius-…` git repository | One repo with `/docs` `/src` `/schema` `README.md` `presentation.pptx` |
| [x] | 0.1b | Fix the 6 pre-existing failing tests (deactivated-user lockout, guest access, job applications) | Green test suite |
| [ ] | 0.2 | Architecture document (modules, layers, data flow) | Diagram + one-page summary for supervisor sign-off |
| [ ] | 0.3 | Modular structure `app/Modules/{Identity, Community, Knowledge, Careers, Projects, Events, Consulting, Communications, Governance}` | Modular folder layout |
| [ ] | 0.4 | Layered pattern: Services, Form Requests, Policies, thin controllers | Convention applied to all modules |
| [ ] | 0.5 | Vue 3 + Vite alongside Bootstrap | Frontend build pipeline |
| [ ] | 0.6 | Design system: multi-column layouts, page header, data table, filter panel, tabs, stat cards + charts, modals, toasts | Reusable component library, redesigned screens |
| [ ] | 0.7 | Shared services: notifications (in-app + email), private file storage, queues, scheduler, activity log | Platform infrastructure |

## Phase 1 — Rebuild existing modules

### FR1 Identity & Access

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 1.1 | Social login (Google, GitHub) | Socialite integration |
| [ ] | 1.2 | Email verification + forgot/reset password | Complete auth flows |
| [ ] | 1.3 | Permissions table, role–permission mapping, Policies | Fine-grained RBAC |
| [ ] | 1.4 | Login rate limiting, active sessions, account & privacy settings | Hardened accounts |

### Dashboard & Profile

| Status | ID | Step | Output |
|---|---|---|---|
| [ ] | 1.5 | Role-specific dashboards (Admin / Expert / Member) with charts, activity feed, pending tasks | Professional dashboards |
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
