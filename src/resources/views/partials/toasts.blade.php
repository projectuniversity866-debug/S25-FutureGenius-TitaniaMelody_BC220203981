{{-- Success messages ("Question deleted.") as a toast that pauses while
     hovered or focused and can be dismissed. Without JavaScript it simply
     stays on screen. Errors are not toasts — they stay in the page
     (partials/alerts) because they need attention.

     Screen readers ignore text that is already in a live region when the
     page loads, so app.js copies the message into the empty announcer
     below a moment after load; that is what gets read out. --}}
@if (session('status'))
    <div class="aq-toasts">
        <div class="aq-toast" data-aq-toast>
            <i class="bi bi-check-circle-fill aq-toast-icon" aria-hidden="true"></i>
            <p class="mb-0" data-aq-toast-message>{{ session('status') }}</p>
            <button type="button" class="aq-toast-close" data-aq-toast-close aria-label="Dismiss message">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
    </div>
@endif

<div class="visually-hidden" role="status" aria-live="polite" data-aq-announcer></div>
