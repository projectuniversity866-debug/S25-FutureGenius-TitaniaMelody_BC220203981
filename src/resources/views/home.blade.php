@extends('layouts.app')
@section('title', 'Home')

@section('content')

{{-- Hero --}}
<section class="hero rounded-4 p-4 p-md-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h1 class="fw-bold mb-3">One place to ask, answer and grow.</h1>
            <p class="lead mb-4 opacity-75">
                AQDesk is a community and knowledge platform for emerging technologies —
                bringing together students, instructors, researchers, professionals and experts.
            </p>
            <div class="d-flex flex-wrap gap-2">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-info fw-semibold">
                        <i class="bi bi-person-plus" aria-hidden="true"></i> Join the community
                    </a>
                    <a href="{{ route('questions.index') }}" class="btn btn-outline-light">Browse questions</a>
                @else
                    <a href="{{ route('questions.create') }}" class="btn btn-info fw-semibold">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Ask a question
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-light">Go to dashboard</a>
                @endguest
            </div>
        </div>
        <div class="col-lg-5">
            <div class="row g-2 text-center">
                @foreach ([
                    ['label' => 'Members',   'value' => $stats['members'],   'icon' => 'people'],
                    ['label' => 'Questions', 'value' => $stats['questions'], 'icon' => 'patch-question'],
                    ['label' => 'Answers',   'value' => $stats['answers'],   'icon' => 'chat-left-text'],
                    ['label' => 'Openings',  'value' => $stats['jobs'],      'icon' => 'briefcase'],
                ] as $stat)
                    <div class="col-6">
                        <div class="bg-white bg-opacity-10 rounded-3 py-3">
                            <i class="bi bi-{{ $stat['icon'] }} d-block mb-1 fs-5 text-info" aria-hidden="true"></i>
                            <div class="fs-4 fw-bold">{{ number_format($stat['value']) }}</div>
                            <div class="small opacity-75">{{ $stat['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Categories from FR2 --}}
<section class="mb-4" aria-labelledby="categories-heading">
    <h2 id="categories-heading" class="h5 fw-bold mb-3">Explore by topic</h2>
    <div class="row g-3">
        @foreach ($categories as $category)
            <div class="col-6 col-lg-3">
                <a href="{{ route('questions.index', ['category' => $category->slug]) }}"
                   class="card h-100 border-0 shadow-sm text-decoration-none">
                    <div class="card-body">
                        <i class="bi bi-{{ $category->icon ?? 'tag' }} fs-4 text-info" aria-hidden="true"></i>
                        <h3 class="h6 fw-semibold mt-2 mb-1 text-body">{{ $category->name }}</h3>
                        <p class="small text-muted mb-0">{{ $category->questions_count }} question(s)</p>
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
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 id="latest-questions-heading" class="h5 fw-bold mb-0">Latest questions</h2>
                <a href="{{ route('questions.index') }}" class="small">View all</a>
            </div>

            @forelse ($latestQuestions as $question)
                @include('partials.question-card', ['question' => $question])
            @empty
                @include('partials.empty', [
                    'icon' => 'patch-question',
                    'title' => 'No questions yet',
                    'message' => 'Be the first to start a discussion.',
                ])
            @endforelse
        </section>
    </div>

    {{-- Latest opportunities --}}
    <div class="col-lg-5">
        <section aria-labelledby="latest-jobs-heading">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 id="latest-jobs-heading" class="h5 fw-bold mb-0">Latest opportunities</h2>
                <a href="{{ route('jobs.index') }}" class="small">View all</a>
            </div>

            @forelse ($latestJobs as $job)
                <article class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <h3 class="h6 mb-1">
                                    <a href="{{ route('jobs.show', $job) }}" class="text-decoration-none">{{ $job->title }}</a>
                                </h3>
                                <p class="small text-muted mb-0">
                                    {{ $job->company }}@if ($job->location) &middot; {{ $job->location }} @endif
                                </p>
                            </div>
                            <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }}">
                                {{ ucfirst($job->type) }}
                            </span>
                        </div>
                    </div>
                </article>
            @empty
                @include('partials.empty', [
                    'icon' => 'briefcase',
                    'title' => 'No opportunities posted yet',
                ])
            @endforelse
        </section>

        {{-- Roadmap: makes the modular scope of AQDesk visible. --}}
        <section class="card border-0 bg-body-secondary mt-4" aria-labelledby="roadmap-heading">
            <div class="card-body">
                <h2 id="roadmap-heading" class="h6 fw-bold mb-3">
                    <i class="bi bi-signpost-split" aria-hidden="true"></i> Coming in Phase 2
                </h2>
                <ul class="list-unstyled small mb-0 d-grid gap-2">
                    <li><a href="{{ route('modules.coming-soon', 'projects') }}" class="text-decoration-none">
                        <i class="bi bi-kanban me-1" aria-hidden="true"></i> Project Collaboration</a></li>
                    <li><a href="{{ route('modules.coming-soon', 'events') }}" class="text-decoration-none">
                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i> Events &amp; Podcasts</a></li>
                    <li><a href="{{ route('modules.coming-soon', 'consulting') }}" class="text-decoration-none">
                        <i class="bi bi-briefcase me-1" aria-hidden="true"></i> Consulting Management</a></li>
                    <li><a href="{{ route('modules.coming-soon', 'newsletter') }}" class="text-decoration-none">
                        <i class="bi bi-envelope-paper me-1" aria-hidden="true"></i> Newsletter &amp; Announcements</a></li>
                </ul>
            </div>
        </section>
    </div>
</div>
@endsection
