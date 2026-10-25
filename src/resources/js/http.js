/*
 * JSON requests from the page to the same Laravel routes the plain forms
 * use. Laravel answers with JSON when the request says Accept:
 * application/json, so one controller serves both. A failed request comes
 * back as a sentence the user can act on, never as an exception.
 */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * @returns {Promise<{ok: boolean, status: number, data: any, message: string}>}
 */
export async function sendJson(url, { method = 'POST', body } = {}) {
    let response;
    try {
        response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
    } catch {
        return { ok: false, status: 0, data: null, message: 'AQDesk could not be reached. Check your connection and try again.' };
    }

    const data = await response.json().catch(() => null);

    return {
        ok: response.ok,
        status: response.status,
        data,
        message: response.ok ? (data?.message ?? '') : errorMessage(response.status, data),
    };
}

function errorMessage(status, data) {
    // Validation errors (422) carry the reason, e.g. "You cannot vote on your own post."
    const reason = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
    if (reason) return reason;

    switch (status) {
        case 401:
            return 'You have been signed out. Sign in again to continue.';
        case 403:
            return data?.message === 'Your email address is not verified.'
                ? 'Confirm your email address first — use the link we emailed you.'
                : 'You are not allowed to do that.';
        case 404:
            return 'This item no longer exists. Reload the page to see the latest.';
        case 419:
            return 'Your session expired. Reload the page and try again.';
        case 429:
            return 'That was a lot of clicks in a short time. Wait a moment and try again.';
        default:
            return 'Something went wrong on our side. Please try again.';
    }
}
