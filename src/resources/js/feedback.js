/*
 * Feedback UI: the confirmation dialog for destructive actions and toasts
 * (success, error, new notification). Markup lives in
 * resources/views/partials/confirm-dialog and partials/toasts; see the
 * comments there. Vue islands call showToast() and announce().
 */

const TOAST_MS = 8000;

/** Announce a message to screen readers through the shared live region. */
export function announce(message) {
    const region = document.querySelector('[data-aq-announcer]');
    if (!region) return;
    region.textContent = '';
    // A short delay makes screen readers treat it as a new message.
    setTimeout(() => { region.textContent = message; }, 120);
}

/**
 * Forms with data-confirm open the shared dialog instead of submitting.
 * Confirming re-submits the same form with the same submit button.
 */
export function initConfirmDialogs() {
    const dialog = document.getElementById('aq-confirm');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const title = dialog.querySelector('#aq-confirm-title');
    const body = dialog.querySelector('#aq-confirm-body');
    const accept = dialog.querySelector('[data-confirm-accept]');
    let pending = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        if (form.dataset.confirmed === 'yes') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        pending = { form, submitter: event.submitter ?? null };

        title.textContent = form.dataset.confirmTitle || 'Are you sure?';
        body.textContent = form.dataset.confirm || 'This cannot be undone.';
        accept.textContent = form.dataset.confirmButton || 'Confirm';

        dialog.returnValue = '';
        dialog.showModal();
    });

    dialog.addEventListener('close', () => {
        if (!pending) return;
        const { form, submitter } = pending;
        pending = null;

        if (dialog.returnValue === 'confirm') {
            form.dataset.confirmed = 'yes';
            form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
        } else {
            submitter?.focus();
        }
    });

    // A click on the backdrop (outside the panel) cancels.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close('cancel');
    });
}

/** Auto-dismiss the toasts the server rendered; pause while hovered or focused. */
export function initToasts() {
    document.querySelectorAll('[data-aq-toast]').forEach((toast) => armToast(toast));
}

const TOAST_ICONS = { success: 'check-circle-fill', error: 'exclamation-triangle-fill', info: 'bell-fill' };
const MAX_TOASTS = 3;

/**
 * Show a toast from JavaScript, with the same look and behaviour as
 * partials/toasts. Error toasts stay until dismissed, because they need
 * attention. The message is set as text, never as HTML.
 */
export function showToast(message, tone = 'success') {
    if (!message) return null;

    let stack = document.querySelector('.aq-toasts');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'aq-toasts';
        document.body.append(stack);
    }

    // The same message twice (e.g. repeated clicks) replaces the old toast.
    stack.querySelectorAll('[data-aq-toast]').forEach((old) => {
        if (old.querySelector('[data-aq-toast-message]')?.textContent === message) old.remove();
    });

    const toast = document.createElement('div');
    toast.className = `aq-toast is-${tone}`;
    toast.setAttribute('data-aq-toast', '');
    toast.innerHTML = `
        <i class="bi bi-${TOAST_ICONS[tone] ?? TOAST_ICONS.success} aq-toast-icon" aria-hidden="true"></i>
        <p class="mb-0" data-aq-toast-message></p>
        <button type="button" class="aq-toast-close" data-aq-toast-close aria-label="Dismiss message">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>`;
    toast.querySelector('[data-aq-toast-message]').textContent = message;
    stack.append(toast);

    const all = stack.querySelectorAll('[data-aq-toast]');
    if (all.length > MAX_TOASTS) all[0].remove();

    armToast(toast, { persist: tone === 'error' });

    return toast;
}

function armToast(toast, { persist = false } = {}) {
    const message = toast.querySelector('[data-aq-toast-message]')?.textContent.trim();
    if (message) announce(message);

    let remaining = TOAST_MS;
    let started = Date.now();
    let timer = persist ? null : setTimeout(dismiss, remaining);

    function dismiss() {
        clearTimeout(timer);
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 200);
    }
    function pause() {
        clearTimeout(timer);
        remaining -= Date.now() - started;
    }
    function resume() {
        started = Date.now();
        clearTimeout(timer);
        timer = setTimeout(dismiss, Math.max(remaining, 2000));
    }

    if (!persist) {
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', resume);
    }
    toast.querySelector('[data-aq-toast-close]')?.addEventListener('click', dismiss);
}
