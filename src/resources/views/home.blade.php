@extends('layouts.app')
@section('title', 'Home')
@section('subtitle', 'Community, learning and knowledge platform')

@section('content')

{{-- Hero --}}
<section class="aq-hero mb-4" aria-labelledby="hero-heading">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <span class="aq-hero-eyebrow">
                <i class="bi bi-broadcast" aria-hidden="true"></i> Emerging technologies
            </span>
            <h1 id="hero-heading">One place to ask, answer and grow.</h1>
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
            <h2 class="visually-hidden">AQDesk in numbers</h2>
            <div class="row g-2">
                <div class="col-6"><x-stat label="Members" :value="$stats['members']" icon="people" /></div>
                <div class="col-6"><x-stat label="Questions" :value="$stats['questions']" icon="patch-question" tone="blue" /></div>
                <div class="col-6"><x-stat label="Answers" :value="$stats['answers']" icon="chat-left-text" tone="green" /></div>
                <div class="col-6"><x-stat label="Open opportunities" :value="$stats['jobs']" icon="briefcase" tone="amber" /></div>
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
                        <p class="mb-0 aq-num aq-text-xs aq-muted">
                            {{ $category->questions_count }} {{ \Illuminate\Support\Str::plural('question', $category->questions_count) }}
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
                @include('community::partials.question-card', ['question' => $question])
            @empty
                <div class="card">
                    <x-empty icon="patch-question" title="No questions yet"
                             message="Be the first to start a discussion." />
                </div>
            @endforelse
        </section>
    </div>

    <div class="col-lg-5">
        <x-card title="Latest opportunities" icon="briefcase" flush class="mb-4">
            <x-slot:actions><a href="{{ route('jobs.index') }}">View all</a></x-slot:actions>
            <ul class="list-group list-group-flush">
                @forelse ($latestJobs as $job)
                    <li class="list-group-item">
                        <div class="d-flex align-items-center gap-3">
                            <x-org-mark :name="$job->company" />
                            <div class="min-w-0 flex-grow-1">
                                <a href="{{ route('jobs.show', $job) }}"
                                   class="d-block text-truncate fw-semibold text-decoration-none">{{ $job->title }}</a>
                                <span class="aq-text-xs aq-muted">
                                    {{ $job->company }}@if ($job->location) · {{ $job->location }}@endif
                                </span>
                            </div>
                            <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }} flex-shrink-0">
                                {{ ucfirst($job->type) }}
                            </span>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="briefcase" title="No opportunities posted yet" />
                    </li>
                @endforelse
            </ul>
        </x-card>

        <x-card title="How AQDesk works" icon="signpost-split" class="aq-card-quiet">
            <ol class="aq-steps">
                <li>
                    <strong>Ask</strong>
                    <span>Post a question in one of four topic tracks and tag it.</span>
                </li>
                <li>
                    <strong>Answer and vote</strong>
                    <span>Members and experts answer; the community votes the best ones up.</span>
                </li>
                <li>
                    <strong>Accept and grow</strong>
                    <span>The asker accepts the answer that solved it — and opportunities follow.</span>
                </li>
            </ol>
        </x-card>
    </div>
</div>
@endsection
