{{-- Empty state. Every list in the app falls back to this. --}}
<div class="aq-empty">
    <span class="aq-empty-icon" aria-hidden="true">
        <i class="bi bi-{{ $icon ?? 'inbox' }}"></i>
    </span>
    <h3>{{ $title ?? 'Nothing here yet' }}</h3>
    @isset($message)
        <p>{{ $message }}</p>
    @endisset
    @isset($action)
        <a href="{{ $action['url'] }}" class="btn btn-soft btn-sm mt-3">{{ $action['label'] }}</a>
    @endisset
</div>
