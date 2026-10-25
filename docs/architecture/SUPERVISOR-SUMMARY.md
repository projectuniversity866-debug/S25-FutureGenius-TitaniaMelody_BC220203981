# AQDesk — Architecture Summary for Approval

**To:** Mr. Abdullah Qamar · **From:** Titania Melody (BC220203981) · **Course:** CS619, Spring 2026
**Full document:** `docs/architecture/ARCHITECTURE.md`

## Where the project stands

The prototype is complete with the six mandatory modules: authentication,
role-based access, dashboard, profile, community Q&A, and jobs/internships.
81 automated tests pass. The final phase adds FR3, FR4, FR6 and FR7, completes
FR1 (social login, email verification), and covers the vision items
**expert knowledge sharing**, **professional opportunities**, **education
technology** and **digital governance**.

## Proposed architecture

**A modular monolith in Laravel 12.** One application, divided into nine
self-contained modules plus a shared platform layer. Each module owns its own
routes, validation, policies, services, data and tests, and modules
communicate through events. This keeps the system simple to host while
allowing any service to be added or extracted later.

| Module | Covers |
|---|---|
| Identity | FR1 — email and social login, roles, permissions, profiles |
| Community | FR2 — Q&A, comments, votes, tags, reputation |
| Knowledge | Expert knowledge sharing, EdTech learning resources |
| Careers | FR5 — jobs, internships, scholarships, fellowships, grants |
| Projects | FR3 — project spaces, invitations, file sharing, feedback |
| Events & Media | FR4 — events, registration, webinars, podcasts |
| Consulting | FR6 — clients, bookings, proposals |
| Communications | FR7 — newsletter, campaigns, announcements |
| Governance | Digital governance — reporting, moderation log, audit trail, privacy |
| *Platform* | Forms engine, notifications, file storage, search, accessibility preferences |

**Stack:** Laravel 12 · PHP 8.2 · MySQL 8 · Blade with Bootstrap 5 · Vue 3
components · database queue and cron scheduler · SMTP email · Git/GitHub.

**Accessibility:** WCAG 2.1 AA target; every page works without JavaScript; a
user accessibility toolbar (text size, contrast, dyslexia-friendly font,
reduced motion) for the live demonstration.

## Decisions for your approval

| # | Proposal | Approve? |
|---|---|---|
| 1 | Modular monolith rather than microservices | ☐ |
| 2 | Blade pages enhanced with Vue 3 components, rather than a full Vue single-page app | ☐ |
| 3 | Roles: Administrator, Moderator, Expert, Organization, Member | ☐ |
| 4 | Webinars integrated by meeting link and Jitsi embed, not the paid Zoom API | ☐ |
| 5 | "Form creators and respondents" delivered as one shared form builder used by events, consulting and job applications | ☐ |
| 6 | Live demo hosted on: cPanel / DigitalOcean / AWS *(please choose)* | ☐ |
| 7 | Repository name prefix: `S25` (as written) or `S26`; one repository per student | ☐ |

Comments:

&nbsp;
