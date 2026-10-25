// Screenshot every main screen as each role, at desktop and phone width,
// using the Microsoft Edge already installed on Windows (puppeteer-core).
//
//   node tests/e2e/screens.mjs <base-url> <out-dir> [light|dark]
//
// Normally run through tests/e2e/screens.sh, which starts the app on a
// throw-away database first.

import { mkdir } from 'node:fs/promises';
import puppeteer from 'puppeteer-core';

const [base, out, theme = 'light'] = process.argv.slice(2);
const EDGE = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';

const WIDTHS = { desktop: 1440, phone: 390 };

const SCREENS = {
    guest: ['/', '/questions', '/questions?sort=trending', '/tags', '/jobs', '/login', '/register'],
    member: ['/dashboard', '/questions/{question}', '/questions/create', '/questions?tag=laravel', '/saved', '/tags', '/jobs/{job}', '/jobs/{job}/apply', '/applications', '/profile', '/users/{me}'],
    expert: ['/dashboard', '/my-jobs', '/jobs/{job}/applicants'],
    admin: ['/dashboard', '/admin/users', '/admin/questions', '/admin/jobs', '/modules/projects'],
};

const USERS = {
    member: 'member@aqdesk.test',
    expert: 'expert@aqdesk.test',
    admin: 'admin@aqdesk.test',
};

await mkdir(out, { recursive: true });
const browser = await puppeteer.launch({ executablePath: EDGE, headless: true, args: ['--no-first-run'] });
const errors = [];

async function newPage(width, { expectStatus = null } = {}) {
    // A separate context per role, so sessions never leak between users.
    const context = await browser.createBrowserContext();
    const page = await context.newPage();
    await page.setViewport({ width, height: 900 });
    await page.evaluateOnNewDocument((t) => localStorage.setItem('aq-theme', t), theme);
    page.on('pageerror', (e) => errors.push(`${page.url()}: ${e.message}`));
    // A check that provokes a refusal on purpose (e.g. a 422) says so with expectStatus.
    page.on('console', (m) => m.type() === 'error'
        && !(expectStatus && m.text().includes(`status of ${expectStatus}`))
        && errors.push(`${page.url()}: ${m.text()}`));
    return page;
}

async function signIn(page, email) {
    await page.goto(`${base}/login`, { waitUntil: 'networkidle0' });
    await page.type('#email', email);
    await page.type('#password', 'password');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);
}

/** Replace {question}, {job}, {me} with real links found in the app. */
async function resolve(page, path) {
    if (path.includes('{question}')) {
        await page.goto(`${base}/questions`, { waitUntil: 'networkidle0' });
        const href = await page.$eval('a[href*="/questions/"]:not([href$="/create"])', (a) => a.getAttribute('href'));
        path = path.replace('/questions/{question}', new URL(href, base).pathname);
    }
    if (path.includes('{job}')) {
        let href = null;
        if (path.endsWith('/applicants')) {
            await page.goto(`${base}/my-jobs`, { waitUntil: 'networkidle0' });
            href = await page.$eval('a[href*="/applicants"]', (a) => a.getAttribute('href'));
        }
        if (href) {
            path = path.replace('/jobs/{job}/applicants', new URL(href, base).pathname);
        } else {
            await page.goto(`${base}/jobs`, { waitUntil: 'networkidle0' });
            href = await page.$eval('a[href*="/jobs/"]:not([href$="/create"])', (a) => a.getAttribute('href'));
            path = path.replace('/jobs/{job}', new URL(href, base).pathname);
        }
    }
    if (path.includes('{me}')) {
        await page.goto(`${base}/dashboard`, { waitUntil: 'networkidle0' });
        const href = await page.$eval('a[href*="/users/"]', (a) => a.getAttribute('href'));
        path = path.replace('/users/{me}', new URL(href, base).pathname);
    }
    return path;
}

for (const [role, paths] of Object.entries(SCREENS)) {
    for (const [label, width] of Object.entries(WIDTHS)) {
        const page = await newPage(width);
        if (USERS[role]) await signIn(page, USERS[role]);

        for (const raw of paths) {
            const path = await resolve(page, raw);
            const response = await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
            const name = `${role}-${raw.replace(/[{}]/g, '').replace(/\//g, '_').replace(/[?=&]/g, '-').replace(/^_$/, '_home').replace(/^_/, '')}-${label}.png`;
            await page.screenshot({ path: `${out}/${name}`, fullPage: true });
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            console.log(`${response.status()}  ${label.padEnd(7)} ${role.padEnd(6)} ${path}${overflow > 1 ? `  ⚠ horizontal overflow ${overflow}px` : ''}`);
        }
        await page.browserContext().close();
    }
}

// ---------------------------------------------------------------------
// Behaviour checks on the real pages (not hand-written test markup).
// ---------------------------------------------------------------------
const failures = [];
const expect = (label, ok, detail = '') => {
    console.log(`${ok ? '✓' : '✗'} ${label}${ok ? '' : `  — ${detail}`}`);
    if (!ok) failures.push(label);
};

{
    const page = await newPage(1280);
    await signIn(page, USERS.member);
    await page.goto(`${base}/questions/create`, { waitUntil: 'networkidle0' });

    const counter = (id) => page.$eval(`#${id}`, (field) => {
        const ids = (field.getAttribute('aria-describedby') || '').split(' ');
        const p = ids.map((i) => document.getElementById(i)).find((el) => el?.classList.contains('aq-char-counter'));
        return p ? { text: p.textContent.trim(), cls: p.className } : null;
    });

    let c = await counter('title');
    expect('title counter is linked to its field', !!c, 'no aria-describedby counter');
    expect('empty title counter asks for 10 characters', c?.text.includes('10 more needed'), c?.text);

    await page.type('#title', 'How do I');
    c = await counter('title');
    expect('counter updates while typing', c?.text.includes('2 more needed'), c?.text);

    await page.type('#title', ' test Vue islands?');
    c = await counter('title');
    expect('counter turns "ok" at the minimum', c?.cls.includes('is-ok') && c?.text.includes('/ 255'), `${c?.cls} ${c?.text}`);

    const ids = await page.$$eval('.aq-char-counter', (els) => els.map((e) => e.id));
    expect('counter ids are unique', new Set(ids).size === ids.length, ids.join(','));

    await page.browserContext().close();
}

{
    const page = await newPage(1280);
    await page.goto(`${base}/register`, { waitUntil: 'networkidle0' });
    const border = await page.$eval('label.aq-choice', (el) => getComputedStyle(el).borderTopWidth);
    expect('registration role choices are styled as cards', border === '1px', `border ${border}`);
    await page.browserContext().close();
}

{
    // Responsive tables: on a phone every cell — including the action
    // buttons — must be on screen, and table roles must be restored.
    const page = await newPage(390);
    await signIn(page, USERS.admin);
    for (const path of ['/admin/users', '/admin/questions', '/admin/jobs']) {
        await page.goto(`${base}${path}`, { waitUntil: 'networkidle0' });
        const r = await page.evaluate(() => {
            const table = document.querySelector('table.aq-table');
            const cells = [...table.querySelectorAll('tbody td, tbody th')];
            const offscreen = cells.filter((c) => c.getBoundingClientRect().right > window.innerWidth + 1).length;
            const buttons = [...table.querySelectorAll('.aq-cell-actions .btn')];
            const hiddenButtons = buttons.filter((b) => b.getBoundingClientRect().right > window.innerWidth + 1).length;
            return {
                role: table.getAttribute('role'),
                cellRole: table.querySelector('tbody td')?.getAttribute('role'),
                display: getComputedStyle(table.querySelector('tbody td')).display,
                label: getComputedStyle(table.querySelector('tbody td'), '::before').content,
                offscreen, hiddenButtons, buttons: buttons.length,
            };
        });
        expect(`${path}: phone rows stack as labelled cards`, r.display === 'grid' && r.label !== 'none', `${r.display} ${r.label}`);
        expect(`${path}: every cell fits on a phone screen`, r.offscreen === 0 && r.hiddenButtons === 0, `${r.offscreen} cells, ${r.hiddenButtons}/${r.buttons} buttons off-screen`);
        expect(`${path}: table semantics restored`, r.role === 'table' && r.cellRole === 'cell', `${r.role}/${r.cellRole}`);
    }
    await page.browserContext().close();

    const desk = await newPage(1280);
    await signIn(desk, USERS.admin);
    await desk.goto(`${base}/admin/users`, { waitUntil: 'networkidle0' });
    const deskDisplay = await desk.$eval('table.aq-table tbody td', (td) => getComputedStyle(td).display);
    expect('desktop keeps a normal table', deskDisplay === 'table-cell', deskDisplay);

    // Filter chips: removing one filter keeps the others.
    await desk.goto(`${base}/admin/users?role=member&q=a`, { waitUntil: 'networkidle0' });
    const chips = await desk.$$eval('.aq-chip-filter', (els) => els.map((e) => e.textContent.replace(/\s+/g, ' ').trim()));
    expect('active filters shown as chips', chips.length === 2 && chips.some((c) => c.includes('Role: Member')), chips.join(' | '));
    await Promise.all([desk.waitForNavigation({ waitUntil: 'networkidle0' }), desk.click('.aq-chip-filter a[aria-label^="Remove filter Role"]')]);
    const url = new URL(desk.url());
    expect('removing a chip drops only that filter', !url.searchParams.has('role') && url.searchParams.get('q') === 'a', desk.url());
    await desk.browserContext().close();
}

{
    // Confirmation dialog and toast, driven like a keyboard + mouse user.
    const page = await newPage(1280);
    await signIn(page, USERS.admin);
    await page.goto(`${base}/admin/questions`, { waitUntil: 'networkidle0' });
    const rows = () => page.$$eval('table.aq-table tbody tr', (r) => r.length);
    const before = await rows();

    const deleteBtn = 'table.aq-table tbody tr:first-child form[data-confirm] button[type=submit]';
    await page.click(deleteBtn);
    let state = await page.evaluate(() => ({
        open: document.getElementById('aq-confirm').open,
        focus: document.activeElement?.textContent.trim(),
        title: document.getElementById('aq-confirm-title').textContent,
        button: document.querySelector('[data-confirm-accept]').textContent,
    }));
    expect('delete opens the confirmation dialog', state.open, JSON.stringify(state));
    expect('focus starts on Cancel', state.focus === 'Cancel', state.focus);
    expect('dialog names what will be deleted', state.title.startsWith('Delete “') && state.button === 'Delete question', `${state.title} / ${state.button}`);

    await page.keyboard.press('Escape');
    state = await page.evaluate(() => ({
        open: document.getElementById('aq-confirm').open,
        focus: document.activeElement?.closest('form')?.hasAttribute('data-confirm'),
    }));
    expect('Escape cancels and returns focus to the button', !state.open && state.focus === true, JSON.stringify(state));
    expect('cancelling deletes nothing', (await rows()) === before, `${await rows()} rows`);

    await page.click(deleteBtn);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('[data-confirm-accept]')]);
    expect('confirming deletes the question', (await rows()) === before - 1, `${await rows()} of ${before}`);

    const toast = await page.evaluate(() => new Promise((resolve) => setTimeout(() => resolve({
        text: document.querySelector('[data-aq-toast]')?.textContent.trim(),
        announced: document.querySelector('[data-aq-announcer]')?.textContent,
    }), 300)));
    expect('success shows a toast', toast.text?.includes('Question deleted.'), toast.text);
    expect('success is announced to screen readers', toast.announced === 'Question deleted.', toast.announced);

    await page.click('[data-aq-toast-close]');
    await new Promise((r) => setTimeout(r, 350));
    expect('toast can be dismissed', !(await page.$('[data-aq-toast]')));

    // Phone: dialog fits the screen and its buttons stack.
    await page.setViewport({ width: 360, height: 740 });
    await page.click(deleteBtn);
    const fit = await page.$eval('#aq-confirm', (d) => {
        const r = d.getBoundingClientRect();
        return r.left >= 0 && r.right <= window.innerWidth;
    });
    expect('dialog fits a phone screen', fit);
    await page.keyboard.press('Escape');
    await page.browserContext().close();
}

{
    // Dashboard line chart: renders, hover tooltip, keyboard reading, fits a phone.
    const page = await newPage(1280);
    await signIn(page, USERS.admin);
    await page.goto(`${base}/dashboard`, { waitUntil: 'networkidle0' });

    const chart = await page.evaluate(() => ({
        lines: document.querySelectorAll('.aq-linechart polyline.aq-line').length,
        legend: [...document.querySelectorAll('.aq-legend li')].map((li) => li.textContent.trim()),
        tableRows: document.querySelectorAll('.aq-chart-table tbody tr').length,
    }));
    expect('line chart draws both series', chart.lines === 2, `${chart.lines} lines`);
    expect('legend names both series', chart.legend.join() === 'Questions,Answers', chart.legend.join());
    expect('table view has one row per day', chart.tableRows === 14, `${chart.tableRows} rows`);

    const box = await page.$eval('.aq-linechart-plot', (el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; });
    await page.mouse.move(box.x + box.w * 0.5, box.y + box.h * 0.5);
    const tip = await page.$eval('.aq-tooltip', (el) => el.textContent.replace(/\s+/g, ' ').trim()).catch(() => null);
    expect('hover shows one tooltip for every series', !!tip && tip.includes('Questions') && tip.includes('Answers'), tip);

    await page.mouse.move(0, 0);
    await page.focus('.aq-linechart-plot');
    await page.keyboard.press('End');
    const lastDay = await page.$eval('.aq-linechart [role=status]', (el) => el.textContent);
    await page.keyboard.press('ArrowLeft');
    const prevDay = await page.$eval('.aq-linechart [role=status]', (el) => el.textContent);
    expect('keyboard reads each day aloud', /Questions \d+, Answers \d+/.test(lastDay) && prevDay !== lastDay, `${lastDay} → ${prevDay}`);

    await page.setViewport({ width: 390, height: 800 });
    await new Promise((r) => setTimeout(r, 300));
    const fits = await page.$eval('.aq-linechart svg', (svg) => svg.getBoundingClientRect().right <= window.innerWidth);
    expect('chart resizes to a phone screen', fits);
    await page.browserContext().close();

    for (const role of ['expert', 'member']) {
        const p = await newPage(1280);
        await signIn(p, USERS[role]);
        const res = await p.goto(`${base}/dashboard`, { waitUntil: 'networkidle0' });
        const tiles = await p.$$eval('.aq-metric', (els) => els.length);
        expect(`${role} dashboard renders its 4 tiles`, res.status() === 200 && tiles === 4, `${res.status()} / ${tiles}`);
        await p.browserContext().close();
    }
}

{
    // 1.10 / 1.11 — voting, saving and following without a page reload; the
    // plain forms without JavaScript; the bell updating itself.
    const page = await newPage(1280);
    await signIn(page, USERS.expert);
    // Asked by the member (the dialog check above deleted the newest question).
    await page.goto(`${base}/questions?q=role-based`, { waitUntil: 'networkidle0' });
    const question = new URL(await page.$eval('.aq-qrow-title a', (a) => a.getAttribute('href')), base).href;
    await page.goto(question, { waitUntil: 'networkidle0' });
    await page.evaluate(() => { window.aqSamePage = true; });

    const island = '[data-vue="VoteControl"]';
    const vote = (p) => p.$eval(island, (el) => ({
        score: Number(el.querySelector('.aq-vote-score').firstChild.textContent.trim()),
        up: el.querySelector('button[aria-label^="Upvote"]').getAttribute('aria-pressed'),
        down: el.querySelector('button[aria-label^="Downvote"]').getAttribute('aria-pressed'),
        forms: el.querySelectorAll('form').length,
    }));
    const settle = (p) => p.waitForNetworkIdle({ idleTime: 150 });

    // The seed data may already hold this user's upvote, so expect the toggle
    // of whatever the page started with.
    const s0 = await vote(page);
    const upvoted = s0.up === 'true';
    expect('vote island replaced the plain forms', s0.forms === 0, JSON.stringify(s0));

    await page.click(`${island} button[aria-label^="Upvote"]`);
    await settle(page);
    const s1 = await vote(page);
    const announced = await page.$eval('[data-aq-announcer]', (el) => el.textContent);
    expect('upvote changes the score without a reload',
        s1.score === s0.score + (upvoted ? -1 : 1) && s1.up === String(!upvoted) && await page.evaluate(() => window.aqSamePage === true),
        `${JSON.stringify(s0)} → ${JSON.stringify(s1)}`);
    expect('the vote is announced to screen readers', announced.startsWith(upvoted ? 'Vote removed.' : 'Upvoted.'), announced);

    await page.click(`${island} button[aria-label^="Upvote"]`);
    await settle(page);
    const s2 = await vote(page);
    expect('clicking again toggles it back', s2.score === s0.score && s2.up === s0.up, JSON.stringify(s2));
    await page.reload({ waitUntil: 'networkidle0' });
    const s3 = await vote(page);
    expect('the server kept the final vote', s3.score === s0.score && s3.up === s0.up, JSON.stringify(s3));

    // A member without the downvote privilege is told why; nothing changes.
    const low = await newPage(1280, { expectStatus: 422 });
    await signIn(low, 'hr@aqdesk.test');
    await low.goto(question, { waitUntil: 'networkidle0' });
    const lowStart = await vote(low);
    await low.click(`${island} button[aria-label^="Downvote"]`);
    await low.waitForSelector('.aq-toast.is-error');
    const refused = await low.evaluate(() => document.querySelector('.aq-toast.is-error [data-aq-toast-message]').textContent);
    const lowAfter = await vote(low);
    expect('a refused downvote explains why', refused.includes('15 reputation'), refused);
    expect('…and puts the score back', lowAfter.score === lowStart.score && lowAfter.down === 'false', JSON.stringify(lowAfter));
    await new Promise((r) => setTimeout(r, 400)); // let the toast finish fading in
    await low.screenshot({ path: `${out}/behaviour-refused-vote.png` });
    await new Promise((r) => setTimeout(r, 8600));
    expect('error toasts stay until dismissed', !!(await low.$('.aq-toast.is-error')));
    await low.browserContext().close();

    // Save for later.
    await page.click('[data-vue="BookmarkButton"] button');
    await settle(page);
    const saved = await page.evaluate(() => ({
        pressed: document.querySelector('[data-vue="BookmarkButton"] button').getAttribute('aria-pressed'),
        icon: document.querySelector('[data-vue="BookmarkButton"] i').className,
        toast: document.querySelector('.aq-toast.is-success [data-aq-toast-message]')?.textContent,
    }));
    expect('bookmark saves without a reload', saved.pressed === 'true' && saved.icon.includes('bookmark-fill'), JSON.stringify(saved));
    expect('…and says where to find it', saved.toast?.includes('under Saved'), saved.toast);
    await page.goto(`${base}/saved`, { waitUntil: 'networkidle0' });
    const savedTitles = await page.$$eval('.aq-qrow-title a', (as) => as.map((a) => a.textContent.trim()));
    expect('Saved page lists it', savedTitles.some((t) => t.includes('role-based access control')), savedTitles.join(' | '));

    // Follow a tag from the question's sidebar.
    await page.goto(question, { waitUntil: 'networkidle0' });
    const follow = '.aq-follow-list [data-vue="FollowButton"]';
    const readFollow = () => page.$eval(follow, (el) => ({
        text: el.querySelector('button').textContent.trim(),
        label: el.querySelector('button').getAttribute('aria-label'),
        count: parseInt(el.querySelector('.aq-follow-count').textContent, 10),
    }));
    const f0 = await readFollow();
    await page.click(`${follow} button`);
    await settle(page);
    const f1 = await readFollow();
    expect('follow switches to "Following" in place',
        f0.text === 'Follow' && f1.text === 'Following' && f1.label.includes(', select to unfollow'), `${JSON.stringify(f0)} → ${JSON.stringify(f1)}`);
    expect('…and the follower count goes up', f1.count === f0.count + 1, `${f0.count} → ${f1.count}`);

    // Without JavaScript the plain forms inside the islands still work.
    const plain = await newPage(1280);
    await plain.setJavaScriptEnabled(false);
    await signIn(plain, USERS.admin);
    await plain.goto(question, { waitUntil: 'networkidle0' });
    const plainVote = () => plain.$eval(island, (el) => ({
        score: Number(el.querySelector('.aq-vote-score').firstChild.textContent.trim()),
        up: el.querySelector('button[aria-label^="Upvote"]').getAttribute('aria-pressed'),
    }));
    const p0 = await plainVote();
    await Promise.all([plain.waitForNavigation({ waitUntil: 'networkidle0' }), plain.click(`${island} form button[aria-label^="Upvote"]`)]);
    const p1 = await plainVote();
    expect('without JavaScript the plain vote form still works',
        p1.score === p0.score + (p0.up === 'true' ? -1 : 1) && p1.up !== p0.up, `${JSON.stringify(p0)} → ${JSON.stringify(p1)}`);
    await plain.browserContext().close();

    // Live bell: the member keeps the dashboard open while an answer arrives.
    const member = await newPage(1280);
    await signIn(member, USERS.member);
    await member.goto(`${base}/dashboard`, { waitUntil: 'networkidle0' });
    await member.evaluate(() => { window.aqSamePage = true; });
    const badge = () => member.$eval('[data-aq-bell-count]', (b) => (b.hidden ? 0 : Number(b.textContent)));
    const before = await badge();

    await page.goto(question, { waitUntil: 'networkidle0' });
    await page.type('#answer_body', 'Suggest similar titles while people type, and link duplicates instead of deleting them.');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form[action$="/answers"] button[type=submit]')]);

    await new Promise((r) => setTimeout(r, 5200)); // the bell waits 5 s between checks
    await member.bringToFront();
    await member.evaluate(() => window.dispatchEvent(new Event('focus')));
    await member.waitForSelector('.aq-toast.is-info', { timeout: 5000 }).catch(() => null);
    const bell = await member.evaluate(() => ({
        same: window.aqSamePage === true,
        label: document.querySelector('[data-aq-bell-button]').getAttribute('aria-label'),
        toast: document.querySelector('.aq-toast.is-info [data-aq-toast-message]')?.textContent,
        first: document.querySelector('[data-aq-bell-menu] .aq-bell-item strong')?.textContent.trim(),
    }));
    expect('the bell count goes up without a reload', bell.same && (await badge()) === before + 1, `${before} → ${await badge()}`);
    expect('the bell label says how many are unread', bell.label === `Notifications, ${before + 1} unread`, bell.label);
    expect('a new notification shows as a toast', bell.toast?.startsWith('New answer to your question'), bell.toast);
    expect('the dropdown lists it first', bell.first === 'New answer to your question', bell.first);
    await new Promise((r) => setTimeout(r, 400));
    await member.screenshot({ path: `${out}/behaviour-live-bell.png` });

    await member.click('[data-aq-bell-button]');
    await member.waitForSelector('[data-aq-bell-menu].show');
    await member.click('[data-aq-bell-read-all] button');
    await member.waitForFunction(() => document.querySelector('[data-aq-bell-count]').hidden, { timeout: 5000 }).catch(() => null);
    const cleared = await member.evaluate(() => ({
        same: window.aqSamePage === true,
        hidden: document.querySelector('[data-aq-bell-count]').hidden,
        label: document.querySelector('[data-aq-bell-button]').getAttribute('aria-label'),
        unread: document.querySelectorAll('[data-aq-bell-menu] .aq-bell-item.is-unread').length,
        focus: document.activeElement?.closest('[data-aq-bell-menu]') !== null,
    }));
    expect('"Mark all read" clears the bell in place', cleared.same && cleared.hidden && cleared.label === 'Notifications' && cleared.unread === 0, JSON.stringify(cleared));
    expect('…and keeps focus inside the menu', cleared.focus);
    await member.browserContext().close();
    await page.browserContext().close();
}

await browser.close();

if (failures.length) process.exitCode = 1;

if (errors.length) {
    console.log('\nBrowser errors:');
    [...new Set(errors)].forEach((e) => console.log('  ' + e));
    process.exitCode = 1;
}
