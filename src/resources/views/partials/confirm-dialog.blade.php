{{-- One shared confirmation dialog for destructive actions.

     A form opts in with data attributes; resources/js/app.js opens this
     dialog when the form is submitted:

         <form … data-confirm="All of its answers are deleted too."
                 data-confirm-title="Delete this question?"
                 data-confirm-button="Delete question">

     Native <dialog>: focus is trapped while open, Escape cancels, and focus
     returns to the button that opened it. Focus starts on Cancel. --}}
<dialog id="aq-confirm" class="aq-dialog" aria-labelledby="aq-confirm-title" aria-describedby="aq-confirm-body">
    <form method="dialog" class="aq-dialog-inner">
        <div class="aq-dialog-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="min-w-0">
            <h2 id="aq-confirm-title" class="aq-h3 mb-1">Are you sure?</h2>
            <p id="aq-confirm-body" class="aq-text aq-muted mb-0">This cannot be undone.</p>
        </div>
        <div class="aq-dialog-actions">
            <button type="submit" value="cancel" class="btn btn-quiet" autofocus>Cancel</button>
            <button type="submit" value="confirm" class="btn btn-danger" data-confirm-accept>Confirm</button>
        </div>
    </form>
</dialog>
