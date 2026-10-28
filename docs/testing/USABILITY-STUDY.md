# AQDesk — Usability study

Roadmap step 4.4: tasks with real users and a System Usability Scale (SUS)
score. This file is the plan, the materials and the results template. The
sessions themselves are run by the student; results go in section 6.

## 1. Plan

| Item | Plan |
|---|---|
| Participants | **5–8** people from the target users: final-year students (members), at least one teacher or professional (expert), at least one person who uses a screen reader, magnification or keyboard only, if available |
| Where | In person or over a video call with screen sharing; the live site or WampServer |
| Time | About 30 minutes each |
| Accounts | A fresh account per participant (they register in task 1), plus `expert@aqdesk.test` for the expert tasks |
| Method | Think-aloud: the participant says what they are looking for and thinking; the facilitator does not help unless they are stuck for 3 minutes |
| Recorded | For each task: completed (yes / with help / no), time, errors, comments. Then the SUS questionnaire. No names in the results — P1, P2 … |

**Before the first session.** On WampServer emails are written to
`src/storage/logs/laravel.log` instead of being sent (`MAIL_MAILER=log`),
so for task 1 open the confirmation link from that file for the participant,
or run the study on the live site with real email (`docs/DEPLOYMENT.md`).
Reset the demo data between participants if you like:
`php artisan migrate:fresh --seed` (this replaces all local data).

## 2. Consent (read out, then ask for a yes)

> Thank you for helping. We are testing the website, not you — if something
> is hard, that is what we want to find. The session takes about 30 minutes.
> I will write down what you do and say, but not your name. You can stop at
> any time. Is that all right?

## 3. Tasks

Read each task out; do not say which menu to use.

| # | Task (as read to the participant) | Done when | Module |
|---|---|---|---|
| 1 | "Create an account and confirm your email." | Signed in with a confirmed account | Identity |
| 2 | "Make the text bigger and turn on high contrast." | Toolbar settings applied | Accessibility |
| 3 | "Ask the community how to choose a database for a final-year project." | Question posted with a topic | Community Q&A |
| 4 | "Find an internship or scholarship you could apply to, and save it for later." | Opportunity in Saved | Careers |
| 5 | "Register for the accessibility workshop." | Registration confirmed (or on the waitlist) | Events |
| 6 | "Book a free session with a consultant next week." | Session in My sessions | Consulting |
| 7 | "Start the learning path about web accessibility and mark the first step done." | Progress shows 1 step | Learning Hub |
| 8 | "Sign up for the newsletter." | Confirmation message shown | Newsletter |
| 9 *(experts)* | "As an organizer, create a feedback form with two questions and open it." | Form open with a link | Form Builder |

## 4. After each task (optional quick question)

"How easy was that?" — 1 (very hard) to 7 (very easy).

## 5. SUS questionnaire (after all tasks)

Each statement: **1 = strongly disagree … 5 = strongly agree.**

| # | Statement | 1 | 2 | 3 | 4 | 5 |
|---|---|---|---|---|---|---|
| 1 | I think that I would like to use this website frequently. | | | | | |
| 2 | I found the website unnecessarily complex. | | | | | |
| 3 | I thought the website was easy to use. | | | | | |
| 4 | I think that I would need the support of a technical person to be able to use this website. | | | | | |
| 5 | I found the various functions in this website were well integrated. | | | | | |
| 6 | I thought there was too much inconsistency in this website. | | | | | |
| 7 | I would imagine that most people would learn to use this website very quickly. | | | | | |
| 8 | I found the website very cumbersome to use. | | | | | |
| 9 | I felt very confident using the website. | | | | | |
| 10 | I needed to learn a lot of things before I could get going with this website. | | | | | |

**Scoring (Brooke, 1996):** for odd items (1, 3, 5, 7, 9) take *answer − 1*;
for even items (2, 4, 6, 8, 10) take *5 − answer*. Add the ten numbers and
multiply by **2.5** → a score from 0 to 100. Average over participants.
**68 is average; 80.3 or more is in the top 10% ("excellent").**

Example: answers 4, 2, 5, 1, 4, 2, 5, 1, 4, 2 → (3+3+4+4+3+3+4+4+3+3) × 2.5 = **85**.

Finally ask: "What did you like most?" and "What would you change first?"

## 6. Results (fill in)

### 6.1 Participants

| ID | Role (student / teacher / professional) | Uses assistive technology? | Device |
|---|---|---|---|
| P1 | | | |
| P2 | | | |
| P3 | | | |
| P4 | | | |
| P5 | | | |

### 6.2 Task results

Mark each cell ✓ (alone), H (with help) or ✗ (not done), with the time in minutes.

| Task | P1 | P2 | P3 | P4 | P5 | Completion rate | Average time |
|---|---|---|---|---|---|---|---|
| 1 Account | | | | | | | |
| 2 Display options | | | | | | | |
| 3 Ask | | | | | | | |
| 4 Save opportunity | | | | | | | |
| 5 Event | | | | | | | |
| 6 Book session | | | | | | | |
| 7 Learning path | | | | | | | |
| 8 Newsletter | | | | | | | |
| 9 Form (experts) | | | | | | | |

### 6.3 SUS scores

| ID | Q1 | Q2 | Q3 | Q4 | Q5 | Q6 | Q7 | Q8 | Q9 | Q10 | SUS |
|---|---|---|---|---|---|---|---|---|---|---|---|
| P1 | | | | | | | | | | | |
| P2 | | | | | | | | | | | |
| P3 | | | | | | | | | | | |
| P4 | | | | | | | | | | | |
| P5 | | | | | | | | | | | |
| **Average** | | | | | | | | | | | |

### 6.4 Problems found and changes made

| # | Problem (what happened, how many participants) | Severity (high / medium / low) | Change made |
|---|---|---|---|
| 1 | | | |
| 2 | | | |

### 6.5 Summary (fill in)

- Average SUS: …… (…… = below average / average / good / excellent)
- Tasks completed without help: ……%
- Main strengths: ……
- Main changes: ……
