{{-- One library resource. Optional: $note (why it is a step), $actions slot via $step. --}}
<li class="aq-resource">
    <span class="aq-resource-icon" aria-hidden="true"><i class="bi bi-{{ $resource->kindIcon() }}"></i></span>
    <div class="min-w-0 flex-grow-1">
        <h3 class="aq-resource-title">
            <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $resource->title }}<span class="visually-hidden"> (opens in a new tab)</span></a>
            <i class="bi bi-box-arrow-up-right aq-muted" aria-hidden="true"></i>
        </h3>
        <p class="aq-text-xs aq-muted mb-1">
            {{ $resource->kindLabel() }}
            @if ($resource->provider) · {{ $resource->provider }} @endif
            · {{ \App\Modules\Knowledge\Models\LearningResource::LEVELS[$resource->level] ?? $resource->level }}
            @if ($resource->duration()) · {{ $resource->duration() }} @endif
            · {{ $resource->is_free ? 'Free' : 'Paid' }}
        </p>
        <p class="aq-text-sm aq-ink-2 mb-0">{{ $resource->description }}</p>
        @if (! empty($note))
            <p class="aq-resource-note mb-0"><i class="bi bi-lightbulb me-1" aria-hidden="true"></i>{{ $note }}</p>
        @endif
    </div>
    @if (empty($note))
        @can('update', $resource)
            <a href="{{ route('learning.resources.edit', $resource) }}" class="btn btn-sm btn-quiet align-self-start">
                Edit<span class="visually-hidden"> {{ $resource->title }}</span>
            </a>
        @endcan
    @endif
</li>
