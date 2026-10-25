{{-- Shown when a list has nothing in it.
     <x-empty icon="inbox" title="No applications yet" message="…"
              :action="['url' => route('jobs.index'), 'label' => 'Browse jobs']" /> --}}
@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'message' => null, 'action' => null])

<div {{ $attributes->class('aq-empty') }}>
    <span class="aq-empty-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
    <h3>{{ $title }}</h3>
    @if ($message)
        <p>{{ $message }}</p>
    @endif
    @if ($action)
        <a href="{{ $action['url'] }}" class="btn btn-soft btn-sm mt-3">{{ $action['label'] }}</a>
    @endif
</div>
