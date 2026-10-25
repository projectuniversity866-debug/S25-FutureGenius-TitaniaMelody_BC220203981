@extends('layouts.app')
@section('title', $user->name)
@section('subtitle', 'Member profile')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="d-flex justify-content-center mb-3">
                    <x-avatar :user="$user" size="xl" />
                </div>

                <h1 class="aq-h2">{{ $user->name }}</h1>

                @if ($user->profile?->headline)
                    <p class="aq-text-sm aq-muted mb-2">{{ $user->profile->headline }}</p>
                @endif

                <span class="badge text-bg-dark">{{ $user->roleName() }}</span>

                @if (auth()->id() === $user->id)
                    <a href="{{ route('profile.edit') }}" class="btn btn-quiet btn-sm w-100 mt-3">
                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit my profile
                    </a>
                @endif

                <hr class="aq-divider">

                <ul class="list-unstyled text-start mb-0 d-grid gap-2 aq-text-sm">
                    @if ($user->profile?->organization)
                        <li><i class="bi bi-building me-2 aq-muted" aria-hidden="true"></i>{{ $user->profile->organization }}</li>
                    @endif
                    @if ($user->profile?->location)
                        <li><i class="bi bi-geo-alt me-2 aq-muted" aria-hidden="true"></i>{{ $user->profile->location }}</li>
                    @endif
                    @if ($user->profile?->website)
                        <li><i class="bi bi-globe me-2 aq-muted" aria-hidden="true"></i>
                            <a href="{{ $user->profile->website }}" rel="noopener noreferrer" target="_blank">Website</a></li>
                    @endif
                    @if ($user->profile?->linkedin)
                        <li><i class="bi bi-linkedin me-2 aq-muted" aria-hidden="true"></i>
                            <a href="{{ $user->profile->linkedin }}" rel="noopener noreferrer" target="_blank">LinkedIn</a></li>
                    @endif
                    @if ($user->profile?->github)
                        <li><i class="bi bi-github me-2 aq-muted" aria-hidden="true"></i>
                            <a href="{{ $user->profile->github }}" rel="noopener noreferrer" target="_blank">GitHub</a></li>
                    @endif
                    <li><i class="bi bi-calendar3 me-2 aq-muted" aria-hidden="true"></i>
                        Member since {{ $user->created_at->format('M Y') }}</li>
                </ul>
            </div>
        </div>

        <div class="row g-2 mt-2">
            <div class="col-6">
                <div class="aq-metric text-center">
                    <div class="aq-metric-value">{{ $user->questions_count }}</div>
                    <p class="aq-metric-label">Questions</p>
                </div>
            </div>
            <div class="col-6">
                <div class="aq-metric text-center">
                    <div class="aq-metric-value">{{ $user->answers_count }}</div>
                    <p class="aq-metric-label">Answers</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if ($user->profile?->bio)
            <section class="card mb-4" aria-labelledby="about-heading">
                <div class="card-body">
                    <h2 id="about-heading" class="aq-label mb-2">About</h2>
                    <p class="mb-0 prose">{{ $user->profile->bio }}</p>
                </div>
            </section>
        @endif

        @if ($user->profile && count($user->profile->skillsArray()))
            <section class="card mb-4" aria-labelledby="skills-heading">
                <div class="card-body">
                    <h2 id="skills-heading" class="aq-label mb-3">Skills</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($user->profile->skillsArray() as $skill)
                            <span class="badge text-bg-info">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="card" aria-labelledby="recent-heading">
            <div class="card-header">
                <h2 id="recent-heading" class="aq-h3">Recent questions</h2>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($questions as $question)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('questions.show', $question) }}"
                               class="fw-semibold text-decoration-none d-block text-truncate">{{ $question->title }}</a>
                            <span class="aq-text-xs aq-muted">{{ $question->created_at->diffForHumans(null, true) }} ago</span>
                        </div>
                        <span class="badge text-bg-light flex-shrink-0">{{ $question->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $question->answers_count) }}</span>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="patch-question" title="No questions posted yet" />
                    </li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
