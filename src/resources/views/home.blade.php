@extends('layouts.app')
@section('title', 'Home')
@section('subtitle', 'Community, learning and knowledge platform')

@section('content')

{{-- Hero --}}
<section class="aq-hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="aq-hero-eyebrow">
                <i class="bi bi-broadcast" aria-hidden="true"></i> Emerging technologies
            </span>
            <h1>One place to ask, answer and grow.</h1>
            <p class="aq-hero-lead">
                AQDesk brings students, instructors, researchers, professionals and policy
                experts together around four topic tracks — and the opportunities that follow.
            </p>
            <div class="d-flex flex-wrap gap-2">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-accent">
                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i> Join the community
                    </a>
                    <a href="{{ route('questions.index') }}" class="btn btn-quiet">Browse questions</a>
                @else
                    <a href="{{ route('questions.create') }}" class="btn btn-accent">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Ask a question
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-quiet">Go to dashboard</a>
                @endguest
            </div>
        </div>

        <div class="col-lg-5">
            <div class="row g-2">
                @foreach ([
                    ['label' => 'Members',   'value' => $stats['members'],   'icon' => 'people',            'tone' => ''],
                    ['label' => 'Questions', 'value' => $stats['questions'], 'icon' => 'patch-question',    'tone' => 'is-blue'],
                    ['label' => 'Answers',   'value' => $stats['answers'],   'icon' => 'chat-left-text',    'tone' => 'is-green'],
                    ['label' => 'Openings',  'value' => $stats['jobs'],      'icon' => 'briefcase',        'tone' => 'is-amber'],
                ] as $stat)
                    <div class="col-6">
                        <div class="aq-metric">
                            <span class="aq-metric-icon {{ $stat['tone'] }}" aria-hidden="true">
                                <i class="bi bi-{{ $stat['icon'] }}"></i>
                            </span>
                            <div class="aq-metric-value">{{ number_format($stat['value']) }}</div>
                            <p class="aq-metric-label">{{ $stat['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Categories (FR2) --}}
<section class="mb-4" aria-labelledby="categories-heading">
    <div class="aq-section-head">
        <h2 id="categories-heading">Explore by topic</h2>
        <a href="{{ route('questions.index') }}">All questions</a>
    </div>
    <div class="row g-3">
        @foreach ($categories as $category)
            <div class="col-6 col-lg-3">
                <a href="{{ route('questions.index', ['category' => $category->slug]) }}"
                   class="card aq-card-link h-100">
                    <div class="aq-topic">
                        <span class="aq-topic-icon" aria-hidden="true">
                            <i class="bi bi-{{ $category->icon ?? 'tag' }}"></i>
                        </span>
                        <h3 class="aq-card-title">{{ $category->name }}</h3>
                        <p class="mb-0 aq-num" style="font-size: .75rem; color: var(--aq-muted);">
                            {{ $category->questions_count }} questions
                        </p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>

<div class="row g-4">
    {{-- Latest questions --}}
    <div class="col-lg-7">
        <section aria-labelledby="latest-questions-heading">
            <div class="aq-section-head">
                <h2 id="latest-questions-heading">Latest questions</h2>
                <a href="{{ route('questions.index') }}">View all</a>
            </div>

            @forelse ($latestQuestions as $question)
                @include('partials.question-card', ['question' => $question])
            @empty
                <div class="card">
                    <div class="card-body">
                        @include('partials.empty', [
                            'icon' => 'patch-question',
                            'title' => 'No questions yet',
                            'message' => 'Be the first to start a discussion.',
                        ])
                    </div>
                </div>
            @endforelse
        </section>
    </div>

    {{-- Latest opportunities + roadmap --}}
    <div class="col-lg-5">
        <section aria-labelledby="latest-jobs-heading">
            <div class="aq-section-head">
                <h2 id="latest-jobs-heading">Latest opportunities</h2>
                <a href="{{ route('jobs.index') }}">View all</a>
            </div>

            <div class="card mb-4">
                <ul class="list-group list-group-flush">
                    @forelse ($latestJobs as $job)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <a href="{{ route('jobs.show', $job) }}"
                                       class="d-block text-truncate fw-semibold text-decoration-none">{{ $job->title }}</a>
                                    <span style="font-size: .75rem; color: var(--aq-muted);">
                                        {{ $job->company }}@if ($job->location) · {{ $job->location }} @endif
                                    </span>
                                </div>
                                <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }} flex-shrink-0">
                                    {{ ucfirst($job->type) }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item">
                            @include('partials.empty', [
                                'icon' => 'briefcase',
                                'title' => 'No opportunities posted yet',
                            ])
                        </li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Roadmap: keeps the modular scope of AQDesk visible. --}}
        <section class="card aq-card-quiet" aria-labelledby="roadmap-heading">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-signpost-split" style="color: var(--aq-accent);" aria-hidden="true"></i>
                    <h2 id="roadmap-heading" style="font-size: .9375rem; font-weight: 650; margin: 0;">Coming in Phase 2</h2>
                </div>
                <p style="font-size: .8125rem; color: var(--aq-muted);">
                    Designed in the requirements, scheduled after the prototype.
                </p>
                <div class="d-grid gap-1">
                    @foreach ([
                        ['projects', 'kanban', 'Project collaboration'],
                        ['events', 'calendar-event', 'Events & podcasts'],
                        ['consulting', 'clipboard-data', 'Consulting management'],
                        ['newsletter', 'envelope-paper', 'Newsletter & announcements'],
                    ] as [$slug, $icon, $label])
                        <a href="{{ route('modules.coming-soon', $slug) }}"
                           class="d-flex align-items-center gap-2 text-decoration-none py-1"
                           style="font-size: .8125rem; color: var(--aq-ink-2);">
                            <i class="bi bi-{{ $icon }}" style="color: var(--aq-muted);" aria-hidden="true"></i>
                            {{ $label }}
                            <i class="bi bi-arrow-right ms-auto" style="color: var(--aq-muted);" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
