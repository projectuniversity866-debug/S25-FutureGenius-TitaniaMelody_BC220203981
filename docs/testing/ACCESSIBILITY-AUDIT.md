# AQDesk — WCAG 2.1 AA accessibility audit

Roadmap step 4.3. Target: **WCAG 2.1 level AA** on every page.

| Part | Status |
|---|---|
| A. Automated checks (every page, axe-core, colour contrast) | **Done** 2026-10-28 — 0 open issues |
| B. Manual checks (keyboard, NVDA screen reader, Lighthouse, WAVE) | **To do by the student** — checklist and result tables below |

---

## A. Automated checks

### A1. How it was tested

| Check | Tool | Scope |
|---|---|---|
| Page structure, labels, names, ids, headings | `AccessibilityAuditTest` (PHPUnit, cases WA1–WA3) | **135 page views**: every page without parameters as a visitor (20), as a member and as an administrator (90), plus 25 detail and edit pages of every module opened by their owner |
| WCAG 2.1 A/AA rule set | **axe-core 4.14** (the engine inside Lighthouse's and WAVE-like checkers), `src/tests/a11y/axe.sh` | **52 pages** of the running application: 19 public, 11 member, 8 expert, 8 administrator, 7 detail pages |
| Colour contrast | `AccessibilityAuditTest` WA4 | Every text colour token on every background it is used on, badges, buttons, focus outline and chart colour, in the light **and** dark theme |

Run them again with:

```bash
cd src
php vendor/bin/phpunit --filter AccessibilityAuditTest
bash tests/a11y/axe.sh
```

The PHPUnit audit runs with the rest of the suite, so a page that breaks
one of these rules fails the tests from now on.

### A2. What the page audit checks (per page)

| WCAG | Check |
|---|---|
| 3.1.1 Language of page | `<html lang="en">` |
| 2.4.1 Bypass blocks | "Skip to main content" link and a `<main id="main-content">` landmark |
| 2.4.2 Page titled | a non-empty `<title>` |
| 1.3.1 Info and relationships · 2.4.6 Headings | exactly one `<h1>`; heading levels never skip (h2 → h4) |
| 1.3.1 · 4.1.2 Name, role, value | every input, select and textarea has a label (`<label for>`, wrapping label or `aria-label`) |
| 1.1.1 Non-text content | every image has `alt` |
| 2.4.4 Link purpose · 4.1.2 | every link and button has an accessible name (text, `aria-label` or image alt) |
| 4.1.1 Parsing | no duplicate `id`; every `aria-describedby` / `aria-labelledby` points to an existing element |
| 1.4.4 · 1.4.12 (project rule) | no inline `style` attributes, so the text-size and spacing settings and user style sheets can override everything |

### A3. Results

| Check | Result |
|---|---|
| Page audit (135 page views) | **0 problems** after the fixes below |
| axe-core (52 pages) | **0 violations**, 1,314 rule passes |
| Colour contrast (light and dark) | **all pairs pass** after the fix below; lowest normal-text pair 4.76:1 |

axe in a page-only environment (jsdom) cannot measure rendered colour
contrast or target size; contrast is checked from the colour tokens instead
(WA4), and both are covered again by Lighthouse in part B.

### A4. Issues found and fixed

| # | WCAG | Issue | Pages | Fix |
|---|---|---|---|---|
| 1 | 1.3.1, 2.4.6 | The empty-state message ("No forms yet", "No saved questions" …) was an `h3` directly under the page's `h1`, so heading navigation skipped a level | 17 page views (notifications, forms, saved, projects, sessions, proposals, applications, admin lists …) | `x-empty` takes a heading level, `h2` by default |
| 2 | 1.3.1 | Learning resource cards were `h3` with no `h2` above them | Resource library | A screen-reader heading "N resources" before the list |
| 3 | 1.4.3 Contrast (minimum) | Muted grey text `#5f7186` was 4.36:1 on the tinted page background and 4.49:1 on the light grey panels (needs 4.5:1) | Light theme, everywhere muted text sits on those backgrounds | Darkened to `#596b80`: 4.76–5.04:1 |
| 4 | Project rule (1.4.4, 1.4.12) | Bar charts set their widths with inline `style` attributes | Admin dashboard, form responses | Widths in 5% steps from the style sheet (`data-w`); the exact value is still written beside each bar |

### A5. Accessibility features (what part B should confirm)

| WCAG | Feature | Where |
|---|---|---|
| 1.4.4, 1.4.8, 1.4.12, 2.3.3 | **Accessibility toolbar** ("Display options"): text size, high contrast, readable font, more line spacing, reduced motion; saved per account or browser | Every page |
| 1.4.3, 1.4.11 | Light and dark themes, both checked for contrast; high-contrast mode | Every page |
| 2.1.1 Keyboard · 2.4.7 Focus visible | Everything works with the keyboard; visible focus ring | Every page |
| 2.4.1 | Skip link as the first Tab stop | Every page |
| 4.1.3 Status messages | Toasts and live updates (votes, bell) announced through a live region | Q&A, notifications |
| 3.3.1, 3.3.3 Error identification and suggestion | Errors listed at the top and beside each field, linked with `aria-describedby`; typed input kept | All forms |
| 1.4.1 Use of colour | Statuses use an icon and a word, never colour alone | Applications, events, proposals, campaigns, announcements |
| 1.2.1 Audio-only | An episode cannot be published without a transcript | Podcasts |
| 1.3.2, 1.4.10 Reflow | Tables become labelled cards on phones; no horizontal scrolling at 320 px | Admin and list pages |
| 2.2.1 Timing adjustable | Toasts pause on hover and focus, and error toasts stay until dismissed | Every page |
| 3.2.2 On input | Nothing changes until a button is pressed; confirm dialog before destructive actions (focus starts on Cancel, Escape closes) | Delete, cancel, send |
| 4.1.2 | Vue components keep the plain form underneath; every page works without JavaScript | Voting, bookmarks, follow, Markdown editor |

---

## B. Manual checks (student)

Use the demo accounts (password `password`). Record each finding in the
tables; anything that fails gets a row in A4-style with a fix.

### B1. Keyboard only (no mouse)

Unplug or ignore the mouse. Use Tab / Shift+Tab, Enter, Space, arrow keys, Escape.

| # | Task | Expected | Result | Notes |
|---|---|---|---|---|
| 1 | Home: press Tab once | "Skip to main content" appears; Enter jumps to the content | | |
| 2 | Open **Display options**, set Large text and High contrast, Apply | Every control reachable and labelled; change applies | | |
| 3 | Sign in as `member@aqdesk.test` | Fields and button reachable; errors read beside fields | | |
| 4 | Ask a question with the Markdown toolbar (Ctrl+B, Write/Preview tabs with arrow keys) | Question posted | | |
| 5 | Vote on an answer, bookmark a question | Works with Enter/Space; focus stays on the button | | |
| 6 | Register for an event | Done without the mouse | | |
| 7 | Book a consulting session, then cancel it (confirm dialog) | Focus starts on Cancel in the dialog; Escape closes it; focus returns | | |
| 8 | Fill in a form at `/f/…` (radio buttons with arrow keys) | Submitted; thank-you message reached | | |
| 9 | Phone width (DevTools, 320 px): open the menu (☰) and navigate | Menu opens and closes with keyboard; no sideways scrolling | | |
| 10 | Focus is always visible on every control you reach | Yes on every page | | |

### B2. Screen reader — NVDA (free, nvaccess.org) with Firefox or Chrome

| # | Task | Expected | Result | Notes |
|---|---|---|---|---|
| 1 | H key on any page; Insert+F7 → Headings | One heading level 1; levels in order | | |
| 2 | D key (landmarks) | Navigation, main, footer; announcements as named regions | | |
| 3 | Tab through the sign-up form | Every field has a name; hints and errors read | | |
| 4 | Submit a form with an error | The error is read; focus lands where you can fix it | | |
| 5 | Vote on a question | New score announced ("Score 4") | | |
| 6 | Open a podcast episode | Player controls named; transcript readable | | |
| 7 | Questions list on a narrow window | Cards read as label + value | | |
| 8 | An application's status | Status read as a word, not only a colour | | |

### B3. Lighthouse (Chrome or Edge DevTools → Lighthouse → Accessibility)

Run on the live site (or WAMP), desktop and mobile.

| Page | Desktop score | Mobile score | Issues |
|---|---|---|---|
| Home `/` | | | |
| Questions `/questions` | | | |
| A question page | | | |
| Opportunities `/jobs` | | | |
| An event page | | | |
| A podcast episode | | | |
| Dashboard (signed in) | | | |
| A form `/f/…` | | | |

### B4. WAVE (wave.webaim.org, or the browser extension)

| Page | Errors | Contrast errors | Alerts worth fixing |
|---|---|---|---|
| Home `/` | | | |
| Questions `/questions` | | | |
| Sign up `/register` | | | |
| Events `/events` | | | |
| Newsletter `/newsletter` | | | |

### B5. Conclusion (fill in)

- Overall: ……
- Issues found in part B and how they were fixed: ……
