@extends('layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Your activity across AQDesk')

@section('content')

{{-- Identity + role. The role badge is the visible proof that the
     dashboard is role-aware (prototype modules #2 and #3). --}}
<div class="card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        @include('partials.avatar', ['user' => $user, 'size' => 52])

        <div class="flex-grow-1 min-w-0">
            <h1 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 .25rem;">Welcome back, {{ $user->name }}</h1>
            <p class="mb-0 d-flex flex-wrap align-items-center gap-2" style="font-size: .8125rem; color: var(--aq-muted);">
                <span class="badge text-bg-dark">{{ $user->roleName() }}</span>
                @if ($user->profile?->headline)
                    <span>{{ $user->profile->headline }}</span>
                @endif
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('questions.create') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Ask a question
            </a>
            @if ($user->canPostJobs())
                <a href="{{ route('jobs.create') }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-briefcase me-1" aria-hidden="true"></i> Post an opportunity
                </a>
            @endif
            @if ($user->isAdmin())
                <a href="{{ route('admin.users.index') }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Admin panel
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Role-specific statistics --}}
<div class="row g-3 mb-4">
    @foreach ($stats as $i => $stat)
        <div class="col-6 col-lg-3">
            <div class="aq-metric">
                <span class="aq-metric-icon {{ ['', 'is-blue', 'is-green', 'is-amber'][$i % 4] }}" aria-hidden="true">
                    <i class="bi bi-{{ $stat['icon'] }}"></i>
                </span>
                <div class="aq-metric-value">{{ number_format($stat['value']) }}</div>
                <p class="aq-metric-label text-truncate">{{ $stat['label'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    {{-- Community activity --}}
    <div class="col-lg-7">
        <section class="card mb-4" aria-labelledby="recent-q-heading">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 id="recent-q-heading" style="font-size: .9375rem; font-weight: 650; margin: 0;">Recent community questions</h2>
                <a href="{{ route('questions.index') }}" style="font-size: .8125rem; text-decoration: none;">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($recentQuestions as $question)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('questions.show', $question) }}"
                                   class="fw-semibold text-decoration-none d-block text-truncate">{{ $question->title }}</a>
                                <span style="font-size: .75rem; color: var(--aq-muted);">
                                    {{ $question->category?->name ?? 'Uncategorised' }}
                                    · {{ $question->user->name }}
                                    · {{ $question->created_at->diffForHumans(null, true) }} ago
                                </span>
                            </div>
                            <span class="badge {{ $question->answers_count ? 'text-bg-success' : 'text-bg-light' }} flex-shrink-0">
                                {{ $question->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $question->answers_count) }}
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

        <section class="card" aria-labelledby="my-q-heading">
            <div class="card-header">
                <h2 id="my-q-heading" style="font-size: .9375rem; font-weight: 650; margin: 0;">My questions</h2>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($myQuestions as $question)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <a href="{{ route('questions.show', $question) }}"
                           class="text-decoration-none text-truncate">{{ $question->title }}</a>
                        <span class="badge text-bg-light flex-shrink-0">{{ $question->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $question->answers_count) }}</span>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', [
                            'icon' => 'pencil',
                            'title' => 'You have not asked anything yet',
                            'message' => 'Your questions will appear here.',
                            'action' => ['url' => route('questions.create'), 'label' => 'Ask your first question'],
                        ])
                    </li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Opportunities + applications --}}
    <div class="col-lg-5">
        <section class="card mb-4" aria-labelledby="recent-jobs-heading">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 id="recent-jobs-heading" style="font-size: .9375rem; font-weight: 650; margin: 0;">Latest opportunities</h2>
                <a href="{{ route('jobs.index') }}" style="font-size: .8125rem; text-decoration: none;">View all</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($recentJobs as $job)
                    <li class="list-group-item">
                        <a href="{{ route('jobs.show', $job) }}"
                           class="fw-semibold text-decoration-none d-block text-truncate">{{ $job->title }}</a>
                        <span style="font-size: .75rem; color: var(--aq-muted);">{{ $job->company }} · {{ ucfirst($job->type) }}</span>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', ['icon' => 'briefcase', 'title' => 'No opportunities yet'])
                    </li>
                @endforelse
            </ul>
        </section>

        <section class="card mb-4" aria-labelledby="my-apps-heading">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 id="my-apps-heading" style="font-size: .9375rem; font-weight: 650; margin: 0;">My applications</h2>
                <a href="{{ route('applications.index') }}" style="font-size: .8125rem; text-decoration: none;">View all</a>
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
                            'message' => 'Apply from the jobs board to track them here.',
                            'action' => ['url' => route('jobs.index'), 'label' => 'Browse opportunities'],
                        ])
                    </li>
                @endforelse
            </ul>
        </section>

        <section class="card aq-card-quiet" aria-labelledby="phase2-heading">
            <div class="card-body">
                <h2 id="phase2-heading" style="font-size: .9375rem; font-weight: 650; margin: 0 0 .25rem;">
                    <i class="bi bi-signpost-split me-1" style="color: var(--aq-accent);" aria-hidden="true"></i> Planned for Phase 2
                </h2>
                <p style="font-size: .8125rem; color: var(--aq-muted);">
                    These AQDesk modules are designed but intentionally not implemented in the prototype.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('modules.coming-soon', 'projects') }}" class="aq-tag">Projects</a>
                    <a href="{{ route('modules.coming-soon', 'events') }}" class="aq-tag">Events &amp; podcasts</a>
                    <a href="{{ route('modules.coming-soon', 'consulting') }}" class="aq-tag">Consulting</a>
                    <a href="{{ route('modules.coming-soon', 'newsletter') }}" class="aq-tag">Newsletter</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
