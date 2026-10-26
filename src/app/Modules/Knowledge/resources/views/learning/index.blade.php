@extends('layouts.app')
@section('title', 'Learning Hub')
@section('subtitle', 'Learning paths and resources')

@section('content')
<x-page-header title="Learning Hub"
               subtitle="Step-by-step learning paths built by verified experts from free and paid resources. Tick off steps to track your progress.">
    <x-slot:actions>
        <a href="{{ route('learning.resources') }}" class="btn btn-quiet">
            <i class="bi bi-collection me-1" aria-hidden="true"></i> Resource library ({{ $resourceCount }})
        </a>
        @auth
            <a href="{{ route('learning.mine') }}" class="btn btn-quiet">My learning</a>
            @can('create', \App\Modules\Knowledge\Models\LearningPath::class)
                <a href="{{ route('learning.paths.create') }}" class="btn btn-accent">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New path
                </a>
            @endcan
        @endauth
    </x-slot:actions>
</x-page-header>

<div class="aq-chip-row mb-3" role="navigation" aria-label="Topic">
    <a href="{{ route('learning.index') }}" @class(['aq-tag', 'is-active' => ! $category]) @if (! $category) aria-current="page" @endif>All topics</a>
    @foreach ($categories as $cat)
        <a href="{{ route('learning.index', ['category' => $cat->slug]) }}" @class(['aq-tag', 'is-active' => $category === $cat->slug])
           @if ($category === $cat->slug) aria-current="page" @endif>{{ $cat->name }}</a>
    @endforeach
</div>

@if ($paths->isEmpty())
    <div class="card">
        <x-empty icon="signpost-2" title="No learning paths here yet"
                 message="Browse the resource library meanwhile."
                 :action="['url' => route('learning.resources'), 'label' => 'Open the library']" />
    </div>
@else
    <div class="row g-3">
        @foreach ($paths as $path)
            @php $progress = $started[$path->id] ?? null; @endphp
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 aq-article-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge text-bg-primary">{{ \App\Modules\Knowledge\Models\LearningResource::LEVELS[$path->level] ?? $path->level }}</span>
                            @if ($path->category)<span class="badge text-bg-info">{{ $path->category->name }}</span>@endif
                        </div>
                        <h2 class="aq-card-title"><a href="{{ route('learning.paths.show', $path) }}" class="stretched-link text-decoration-none">{{ $path->title }}</a></h2>
                        <p class="aq-text-sm aq-ink-2 flex-grow-1">{{ $path->summary }}</p>
                        @if ($progress)
                            <progress class="aq-progress mb-2" max="{{ max(1, $progress['total']) }}" value="{{ $progress['done'] }}"
                                      aria-label="Your progress on {{ $path->title }}">{{ $progress['done'] }} of {{ $progress['total'] }}</progress>
                        @endif
                        <p class="mb-0 aq-text-xs aq-muted">
                            {{ $path->steps_count }} {{ \Illuminate\Support\Str::plural('step', $path->steps_count) }}
                            · by {{ $path->curator->name }}
                            @if ($progress) · <strong class="aq-accent-ink">{{ $progress['done'] }}/{{ $progress['total'] }} done</strong>@endif
                        </p>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
@endif
@endsection
