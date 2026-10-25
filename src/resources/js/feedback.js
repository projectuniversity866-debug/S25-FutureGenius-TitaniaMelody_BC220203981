/*
 * Feedback UI: the confirmation dialog for destructive actions and the
 * success toast. Markup lives in resources/views/partials/confirm-dialog and
 * partials/toasts; see the comments there.
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

/** Auto-dismiss toasts; pause while hovered or focused. */
export function initToasts() {
    document.querySelectorAll('[data-aq-toast]').forEach((toast) => {
        const message = toast.querySelector('[data-aq-toast-message]')?.textContent.trim();
        if (message) announce(message);

        let remaining = TOAST_MS;
        let started = Date.now();
        let timer = setTimeout(dismiss, remaining);

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

        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', resume);
        toast.querySelector('[data-aq-toast-close]')?.addEventListener('click', dismiss);
    });
}
