# AQDesk — Test suite and performance

Roadmap steps 4.1 (feature and unit tests for every module) and 4.2
(performance). Figures from 2026-10-28.

## At a glance

| Measure | Result |
|---|---|
| PHPUnit tests | **358** (353 feature, 5 unit), all passing on PHP 8.3 (WampServer) and 8.5; 1 skipped (a manual keyboard check) |
| Assertions | 3,163 |
| Line coverage of `src/app` | **95.0%** (6,697 of 7,051 lines) |
| Method coverage | **91.0%** (1,311 of 1,441) |
| End-to-end checks | **270**, all passing (`src/tests/e2e/smoke.sh`) |
| N+1 queries | 0 — any new one fails the tests |

## How to run

From `src/` in Git Bash:

```bash
php vendor/bin/phpunit                         # everything (in-memory SQLite, ~1.5 min)
php vendor/bin/phpunit --testsuite Unit        # unit tests only (under a second)
php vendor/bin/phpunit --filter ProposalTest   # one suite
bash tests/e2e/smoke.sh                        # the real app over HTTP on a throw-away database
```

Code coverage needs Xdebug, which WampServer's PHP 8.3 has:

```bash
XDEBUG_MODE=coverage /c/wamp64/bin/php/php8.3.28/php.exe vendor/bin/phpunit --coverage-text
```

The tests never touch the MySQL data: PHPUnit uses an in-memory SQLite
database, and `smoke.sh` a throw-away SQLite file.

## Three levels of testing

| Level | Where | What it proves |
|---|---|---|
| **Unit** | `src/tests/Unit` | Rules in plain classes and models, without a database: edit-history diff, the permission catalogue, the proposal lifecycle, newsletter confirmation expiry, announcement phases |
| **Feature** | `src/tests/Feature/AqDesk` | Each requirement through real HTTP requests to the application: validation, permissions (403/404), business rules, notifications, emails, queued jobs, exports |
| **End-to-end** | `src/tests/e2e/smoke.sh` | The running application, signed in as guest, member, expert and administrator with real sessions and CSRF tokens; any PHP warning fails the run |

## Feature and unit tests per module

Every case has an ID (for example BK3) used in the roadmap's progress log.
A few single letters from the prototype's test plan repeat (B, C, D, G);
the file name tells them apart.

| Module | Requirement | Test files (suite IDs) | Cases |
|---|---|---|---|
| Identity | FR1 | AuthenticationTest (A), AccountSecurityTest (V), AccountSettingsTest (K), SocialLoginTest (S), PermissionTest (R), RoleAccessTest (B), ProfileTest (D) | 59 |
| Community | FR2 | QuestionAnswerTest (E), EngagementTest (C), DiscoveryTest (D), InteractiveTest (I), ProfileAndMarkdownTest (M) | 54 |
| Knowledge | Experts, EdTech | KnowledgeTest (X), LearningHubTest (L) | 16 |
| Careers | FR5 | JobOpportunityTest (F), OrganizationTest (O), CareerPipelineTest (W) | 34 |
| Projects | FR3 | ProjectSpaceTest (J), ProjectCollaborationTest (Q), ProjectFeedbackTest (N), ProjectTaskBoardTest (U) | 32 |
| EventsMedia | FR4 | EventsTest (Z), EventWebinarAndRemindersTest (ZW), PodcastTest (PC) | 32 |
| Consulting | FR6 | ConsultantAvailabilityTest (CS), ConsultingBookingTest (BK, CR), ProposalTest (PR) | 25 |
| Communications | FR7 | NewsletterTest (NL, CM, RP), AnnouncementTest (AN) | 15 |
| Governance | Digital governance | GovernanceTest (G), PrivacyTest (Y) | 15 |
| Platform | Shared | PlatformTest (P), FormBuilderTest (B), AccessibilityToolbarTest (T), InterfaceTest (H) | 32 |
| Across modules | — | DashboardTest (C), ModuleNavigationTest (G), SmokeTest, DeploymentTest (DP), DocumentationTest (DC), ModuleGapsTest (GP), PerformanceTest (PF) | 39 |
| Unit | — | DomainRulesTest (UN) | 5 |
| **Total** | | | **358** |

## Coverage per module

Measured with Xdebug on PHP 8.3. Blade views are left out of the figure:
they run as compiled templates and are checked through the feature tests'
page assertions instead.

| Module | Lines | Methods |
|---|---|---|
| App shell (home, dashboard, profile, saved) | 99.1% | 96.2% |
| Careers | 93.0% | 89.8% |
| Communications | 92.6% | 92.8% |
| Community | 95.7% | 89.2% |
| Consulting | 95.8% | 91.7% |
| EventsMedia | 93.8% | 89.0% |
| Governance | 98.7% | 100.0% |
| Identity | 94.8% | 87.9% |
| Knowledge | 96.6% | 93.2% |
| Platform | 93.2% | 86.6% |
| Projects | 96.4% | 93.7% |
| **All** | **95.0%** | **91.0%** |

**How the gaps were closed (4.1).** A first coverage run reported only 44%
of lines, because it counted the Blade views; without them it was 94.1%.
The methods it showed as never called pointed to real untested paths, now
covered by suite GP: editing a learning resource and deleting a learning
path, a moderator removing a reported **article**, changing a form's
settings and deleting a form (only by its creator), the notifications page,
pruning the activity log after its retention period, removing a day off,
deleting draft proposals and campaigns (a sent campaign cannot be deleted),
the newsletter confirmation email's text, and the "confirm your email" and
"forgot password" pages. The unit tests found one small bug: the
edit-history diff treated an empty text as one empty line, so a first
version showed a stray "removed" line; fixed. Still at 0%: three one-line
relation methods on small models (Bookmark, ConsultingClientNote,
SocialAccount).

## Performance (4.2)

| Measure | What was done | How it is checked |
|---|---|---|
| **No N+1 queries** | One test run with Laravel's lazy-loading guard found 115 relations loaded one row at a time: every avatar loaded its person's profile, permission checks loaded each person's role, and the profile page's question list loaded authors and tags per question. People now come with profile and role in one query each, and the profile query loads authors and tags. | The guard stays on whenever the tests run, so any new N+1 fails the suite (PF2). Four list pages make exactly the same number of queries with 4 and 8 extra rows (PF1). |
| **Indexes** | Added: open opportunities by date (`job_posts_board_index`) and by deadline for the daily closing (`job_posts_deadline_index`); unread notifications per person for the bell, which polls every 30 seconds (`notifications_unread_index`). Existing ones cover the other lists (questions by date, events by status and start, sessions by consultant, status and start, campaign recipients by status). | PF5 and an end-to-end check confirm the indexes exist. |
| **Caching** | The home page totals and topic counts are the same for every visitor: counted once per 10 minutes. The live announcement banners, read on every page, are cached for 1 minute per audience and cleared as soon as an announcement is posted, edited or deleted; each member's own dismissals are still read every time. | PF3 (a second home visit makes at least 5 fewer queries), PF4 (no banner query from the cache; changes show at once). |
| **Slow work off the request** | Emails and notifications go through the queue (5.1); campaigns send in batches of 50 (3.16). | DP1, CM4. |
| **Production caches** | `deploy.sh` runs `php artisan optimize` (configuration, routes, views, events). | End-to-end check that routes and views cache. |

**Known limitation.** Searches use `LIKE '%word%'`, which no ordinary index
speeds up. At AQDesk's size this is fast enough; a MySQL FULLTEXT index or a
search engine such as Meilisearch is the next step if the content grows
large (listed under future work in the report).
