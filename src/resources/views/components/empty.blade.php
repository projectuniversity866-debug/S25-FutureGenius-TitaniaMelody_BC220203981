{{-- Shown when a list has nothing in it.
     <x-empty icon="inbox" title="No applications yet" message="…"
              :action="['url' => route('jobs.index'), 'label' => 'Browse jobs']" /> --}}
{{-- level: the heading level; 2 by default, because an empty list usually sits
     right under the page title (WCAG 1.3.1, roadmap 4.3). Use 3 inside a titled card. --}}
@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'message' => null, 'action' => null, 'level' => 2])

<div {{ $attributes->class('aq-empty') }}>
    <span class="aq-empty-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
    <h{{ $level }} class="aq-empty-title">{{ $title }}</h{{ $level }}>
    @if ($message)
        <p>{{ $message }}</p>
    @endif
    @if ($action)
        <a href="{{ $action['url'] }}" class="btn btn-soft btn-sm mt-3">{{ $action['label'] }}</a>
    @endif
</div>
