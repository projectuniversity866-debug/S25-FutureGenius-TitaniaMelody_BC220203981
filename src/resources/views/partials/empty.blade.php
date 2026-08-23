<div class="text-center text-muted py-5">
    <i class="bi bi-{{ $icon ?? 'inbox' }} d-block mb-2" style="font-size: 2.5rem;" aria-hidden="true"></i>
    <p class="mb-1 fw-semibold">{{ $title ?? 'Nothing here yet' }}</p>
    @isset($message)
        <p class="small mb-0">{{ $message }}</p>
    @endisset
</div>
