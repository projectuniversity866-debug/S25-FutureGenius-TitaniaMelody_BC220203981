/*
 * Keeps the notification bell current without reloading the page (roadmap
 * 1.11).
 *
 * The bell is server-rendered (platform::partials.bell). Every 30 seconds
 * this asks /notifications/summary for the unread count and the dropdown's
 * HTML, rendered by the same Blade partial, and swaps them in. It also
 * checks straight away when you come back to the tab. A new notification is
 * shown as a toast, which screen readers announce.
 *
 * It only asks while the tab is visible and someone has used the page in the
 * last 15 minutes, so an idle session can still time out.
 */
import { showToast } from './feedback.js';
import { sendJson } from './http.js';

const INTERVAL_MS = 30_000;
const IDLE_MS = 15 * 60_000;
const MIN_GAP_MS = 5_000;

export function initLiveNotifications() {
    const bell = document.querySelector('[data-aq-bell]');
    if (!bell) return;

    const button = bell.querySelector('[data-aq-bell-button]');
    const badge = bell.querySelector('[data-aq-bell-count]');
    const menu = bell.querySelector('[data-aq-bell-menu]');

    let unread = Number(bell.dataset.unread || 0);
    let lastInput = Date.now();
    let lastCheck = Date.now();
    let stopped = false;

    for (const type of ['pointerdown', 'keydown', 'scroll']) {
        window.addEventListener(type, () => { lastInput = Date.now(); }, { passive: true });
    }

    function render(data, { force = false } = {}) {
        button.setAttribute('aria-label', data.label);
        badge.hidden = data.unread === 0;
        badge.textContent = data.unread > 9 ? '9+' : String(data.unread);

        // Never swap the list under someone who is reading it, unless they
        // asked for the change themselves.
        if (force || !menu.classList.contains('show')) menu.innerHTML = data.menu;

        if (data.unread > unread && data.newest) {
            showToast(data.newest.body ? `${data.newest.title}: ${data.newest.body}` : data.newest.title, 'info');
        }
        unread = data.unread;
    }

    async function refresh() {
        if (stopped || document.hidden) return;
        lastCheck = Date.now();

        const { ok, status, data } = await sendJson(bell.dataset.url, { method: 'GET' });
        if (ok) {
            render(data);
        } else if (status === 401 || status === 419) {
            stopped = true; // signed out or session gone: stop asking
        }
    }

    setInterval(() => {
        if (Date.now() - lastInput < IDLE_MS) refresh();
    }, INTERVAL_MS);

    const refreshSoon = () => {
        lastInput = Date.now();
        if (Date.now() - lastCheck > MIN_GAP_MS) refresh();
    };
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshSoon(); });
    window.addEventListener('focus', refreshSoon);

    // "Mark all read" in the dropdown, without leaving the page.
    menu.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-aq-bell-read-all]');
        if (!form) return;
        event.preventDefault();

        const { ok, data, message } = await sendJson(form.action);
        if (!ok) {
            showToast(message, 'error');
            return;
        }
        render(data, { force: true });
        // The button that had focus is gone with the old list.
        const target = menu.classList.contains('show') ? menu.querySelector('.aq-bell-item, .aq-bell-all') : button;
        target?.focus();
    });
}
