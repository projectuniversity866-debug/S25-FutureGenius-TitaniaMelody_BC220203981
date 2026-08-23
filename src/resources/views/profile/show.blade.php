@extends('layouts.app')
@section('title', $user->name)

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-flex justify-content-center mb-3">
                    @include('partials.avatar', ['user' => $user, 'size' => 96])
                </div>

                <h1 class="h5 fw-bold mb-1">{{ $user->name }}</h1>

                @if ($user->profile?->headline)
                    <p class="text-muted small mb-2">{{ $user->profile->headline }}</p>
                @endif

                <span class="badge text-bg-dark mb-3">{{ $user->roleName() }}</span>

                @if (auth()->id() === $user->id)
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm d-block">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit my profile
                    </a>
                @endif

                <ul class="list-unstyled small text-start mt-3 mb-0 d-grid gap-2">
                    @if ($user->profile?->organization)
                        <li><i class="bi bi-building text-muted me-1" aria-hidden="true"></i> {{ $user->profile->organization }}</li>
                    @endif
                    @if ($user->profile?->location)
                        <li><i class="bi bi-geo-alt text-muted me-1" aria-hidden="true"></i> {{ $user->profile->location }}</li>
                    @endif
                    @if ($user->profile?->website)
                        <li><i class="bi bi-globe text-muted me-1" aria-hidden="true"></i>
                            <a href="{{ $user->profile->website }}" rel="noopener noreferrer" target="_blank">Website</a></li>
                    @endif
                    @if ($user->profile?->linkedin)
                        <li><i class="bi bi-linkedin text-muted me-1" aria-hidden="true"></i>
                            <a href="{{ $user->profile->linkedin }}" rel="noopener noreferrer" target="_blank">LinkedIn</a></li>
                    @endif
                    @if ($user->profile?->github)
                        <li><i class="bi bi-github text-muted me-1" aria-hidden="true"></i>
                            <a href="{{ $user->profile->github }}" rel="noopener noreferrer" target="_blank">GitHub</a></li>
                    @endif
                    <li><i class="bi bi-calendar3 text-muted me-1" aria-hidden="true"></i>
                        Member since {{ $user->created_at->format('M Y') }}</li>
                </ul>
            </div>
        </div>

        <div class="row g-2 mt-1">
            <div class="col-6">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <div class="fs-5 fw-bold">{{ $user->questions_count }}</div>
                        <div class="small text-muted">Questions</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <div class="fs-5 fw-bold">{{ $user->answers_count }}</div>
                        <div class="small text-muted">Answers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if ($user->profile?->bio)
            <section class="card border-0 shadow-sm mb-4" aria-labelledby="about-heading">
                <div class="card-body">
                    <h2 id="about-heading" class="h6 fw-bold">About</h2>
                    <p class="mb-0 prose">{{ $user->profile->bio }}</p>
                </div>
            </section>
        @endif

        @if ($user->profile && count($user->profile->skillsArray()))
            <section class="card border-0 shadow-sm mb-4" aria-labelledby="skills-heading">
                <div class="card-body">
                    <h2 id="skills-heading" class="h6 fw-bold mb-3">Skills</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($user->profile->skillsArray() as $skill)
                            <span class="badge text-bg-info-subtle text-info-emphasis border">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="card border-0 shadow-sm" aria-labelledby="recent-heading">
            <div class="card-header bg-white">
                <h2 id="recent-heading" class="h6 fw-bold mb-0">Recent questions</h2>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($questions as $question)
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div class="min-w-0">
                            <a href="{{ route('questions.show', $question) }}"
                               class="fw-semibold text-decoration-none d-block text-truncate">{{ $question->title }}</a>
                            <span class="small text-muted">{{ $question->created_at->diffForHumans() }}</span>
                        </div>
                        <span class="badge text-bg-light border flex-shrink-0">{{ $question->answers_count }} answer(s)</span>
                    </li>
                @empty
                    <li class="list-group-item">
                        @include('partials.empty', ['icon' => 'patch-question', 'title' => 'No questions posted yet'])
                    </li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
