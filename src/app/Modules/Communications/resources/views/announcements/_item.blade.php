@php [$toneLabel, $toneIcon] = \App\Modules\Communications\Models\Announcement::TONES[$a->tone]; @endphp
<article class="card mb-3">
    <div class="card-body">
        <p class="aq-text-xs aq-muted mb-1">
            <i class="bi bi-{{ $toneIcon }} me-1" aria-hidden="true"></i>{{ $toneLabel }} ·
            <time datetime="{{ $a->starts_at->toIso8601String() }}">{{ $a->timeLabel($a->starts_at) }}</time>
        </p>
        <h3 class="fs-6 fw-semibold mb-1">{{ $a->title }}</h3>
        <p class="aq-text-sm mb-0">{{ $a->body }}
            @if ($a->link_url) <a href="{{ $a->link_url }}" class="fw-semibold">{{ $a->link_label }}</a>@endif
        </p>
    </div>
</article>
