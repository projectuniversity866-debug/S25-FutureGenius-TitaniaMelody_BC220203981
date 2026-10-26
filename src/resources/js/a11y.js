/*
 * Accessibility toolbar (roadmap 2.7). The toolbar is a plain form that
 * saves the preferences on the server (a cookie); here each change is
 * applied immediately — classes on <html> and the same cookie — so there is
 * no reload. The server reads the cookie on the next page, before drawing.
 */
import { announce } from './feedback.js';

const COOKIE = 'aq_a11y';
const SWITCHES = ['contrast', 'readable', 'spacing', 'motion'];

export function initAccessibilityToolbar() {
    const form = document.querySelector('[data-aq-a11y]');
    if (!form) return;

    form.querySelector('[data-aq-a11y-apply]')?.setAttribute('hidden', '');

    form.addEventListener('change', () => {
        const size = form.querySelector('input[name="text"]:checked')?.value ?? 'default';
        const on = SWITCHES.filter((s) => form.querySelector(`input[name="${s}"]`)?.checked);
        const root = document.documentElement;

        root.classList.remove('a11y-text-large', 'a11y-text-larger', ...SWITCHES.map((s) => `a11y-${s}`));
        if (size !== 'default') root.classList.add(`a11y-text-${size}`);
        on.forEach((s) => root.classList.add(`a11y-${s}`));

        const value = [size !== 'default' ? `text:${size}` : null, ...on].filter(Boolean).join(',');
        document.cookie = `${COOKIE}=${encodeURIComponent(value)}; path=/; max-age=31536000; SameSite=Lax`;
        announce('Display settings updated.');
    });
}
