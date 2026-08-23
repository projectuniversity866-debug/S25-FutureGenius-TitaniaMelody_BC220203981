@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- Header: identity + role. The role badge is the visible proof that
     the dashboard is role-aware (prototype module #2 and #3). --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        @include('partials.avatar', ['user' => $user, 'size' => 56])

        <div class="flex-grow-1 min-w-0">
            <h1 class="h5 fw-bold mb-1">Welcome back, {{ $user->name }}</h1>
            <p class="mb-0 small text-muted">
                <span class="badge text-bg-dark">{{ $user->roleName() }}</span>
                @if ($user->profile?->headline)
                    <span class="ms-1">{{ $user->profile->headline }}</span>
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('questions.create') }}" class="btn btn-info btn-sm fw-semibold">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Ask a question
            </a>
            @if ($user->canPostJobs())
                <a href="{{ route('jobs.create') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-briefcase" aria-hidden="true"></i> Post an opportunity
                </a>
            @endif
            @if ($user->isAdmin())
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i> Admin panel
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Role-specific statistics --}}
<div class="row g-3 mb-4">
    @foreach ($stats as $stat)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon bg-{{ $stat['color'] }}-subtle text-{{ $stat['color'] }}-emphasis">
                        <i class="bi bi-{{ $stat['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="fs-4 fw-bold lh-1">{{ number_format($stat['value']) }}</div>
                        <div class="small text-muted text-truncate">{{ $stat['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    {{-- Left column: community activity --}}
    <div class="col-lg-7">
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="recent-q-heading">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 id="recent-q-heading" class="h6 fw-bold mb-0">Recent community questions</h2>
                <a href="{{ route('questions.index') }}" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($recentQuestions as $question)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('questions.show', $question) }}"
                                   class="fw-semibold text-decoration-none d-block text-truncate">
                                    {{ $question->title }}
                                </a>
                                <span class="small text-muted">
                                    {{ $question->category?->name ?? 'Uncategorised' }}
                                    &middot; by {{ $question->user->name }}
                                    &middot; {{ $question->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <span class="badge {{ $question->answers_count ? 'text-bg-success' : 'text-bg-light border' }} flex-shrink-0">
                                {{ $question->answers_count }} answer(s)
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', ['icon' => 'patch-question', 'title' => 'No questions yet'])
                    </li>
                @endforelse
            </ul>
        </section>

        <section class="card border-0 shadow-sm" aria-labelledby="my-q-heading">
            <div class="card-header bg-white">
                <h2 id="my-q-heading" class="h6 fw-bold mb-0">My questions</h2>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($myQuestions as $question)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <a href="{{ route('questions.show', $question) }}"
                           class="text-decoration-none text-truncate">{{ $question->title }}</a>
                        <span class="badge text-bg-light border flex-shrink-0">{{ $question->answers_count }} answer(s)</span>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', [
                            'icon' => 'pencil',
                            'title' => 'You have not asked anything yet',
                            'message' => 'Your questions will appear here.',
                        ])
                    </li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Right column: opportunities + applications --}}
    <div class="col-lg-5">
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="recent-jobs-heading">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 id="recent-jobs-heading" class="h6 fw-bold mb-0">Latest opportunities</h2>
                <a href="{{ route('jobs.index') }}" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($recentJobs as $job)
                    <li class="list-group-item">
                        <a href="{{ route('jobs.show', $job) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $job->title }}</a>
                        <span class="small text-muted">{{ $job->company }} &middot; {{ ucfirst($job->type) }}</span>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', ['icon' => 'briefcase', 'title' => 'No opportunities yet'])
                    </li>
                @endforelse
            </ul>
        </section>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="my-apps-heading">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 id="my-apps-heading" class="h6 fw-bold mb-0">My applications</h2>
                <a href="{{ route('applications.index') }}" class="small">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($myApplications as $application)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <span class="text-truncate">{{ $application->jobPost?->title ?? 'Removed opportunity' }}</span>
                        <span class="badge text-bg-{{ $application->statusColor() }} flex-shrink-0">
                            {{ ucfirst($application->status) }}
                        </span>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', [
                            'icon' => 'send',
                            'title' => 'No applications yet',
                            'message' => 'Apply from the Jobs board to track them here.',
                        ])
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- Deferred modules, visible from the dashboard too. --}}
        <section class="card border-0 bg-body-secondary" aria-labelledby="phase2-heading">
            <div class="card-body">
                <h2 id="phase2-heading" class="h6 fw-bold mb-2">
                    <i class="bi bi-signpost-split" aria-hidden="true"></i> Planned for Phase 2
                </h2>
                <p class="small text-muted">
                    These AQDesk modules are designed but not implemented in the prototype.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('modules.coming-soon', 'projects') }}" class="badge text-bg-light border text-decoration-none">Projects</a>
                    <a href="{{ route('modules.coming-soon', 'events') }}" class="badge text-bg-light border text-decoration-none">Events &amp; Podcasts</a>
                    <a href="{{ route('modules.coming-soon', 'consulting') }}" class="badge text-bg-light border text-decoration-none">Consulting</a>
                    <a href="{{ route('modules.coming-soon', 'newsletter') }}" class="badge text-bg-light border text-decoration-none">Newsletter</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
