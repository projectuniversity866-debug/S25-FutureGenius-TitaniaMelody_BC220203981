@extends('layouts.app')
@section('title', $question->title)
@section('subtitle', 'Community Q&A')

@php
    $me = auth()->user();
    $canEditQuestion = $me && ($me->id === $question->user_id || $me->canModerate());
    $myQuestionVote  = $question->voteOf($me)?->value;
@endphp

@section('content')
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('questions.index') }}">Community Q&amp;A</a></li>
        @if ($question->category)
            <li class="breadcrumb-item">
                <a href="{{ route('questions.index', ['category' => $question->category->slug]) }}">
                    {{ $question->category->name }}
                </a>
            </li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">Question</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">

        {{-- The question --}}
        <article class="card mb-4">
            <div class="card-body">
                <h1 style="font-size: 1.375rem; font-weight: 700; margin: 0 0 .5rem;">
                    {{ $question->title }}
                    @if ($question->is_closed)
                        <span class="badge text-bg-secondary align-middle">Closed</span>
                    @endif
                </h1>

                <p class="aq-label mb-3">
                    Asked {{ $question->created_at->diffForHumans(null, true) }} ago
                    · {{ number_format($question->views_count) }} views
                    · {{ $question->answers->count() }} answers
                </p>

                <div class="d-flex gap-3">
                    <div class="aq-vote">
                        @auth
                            <form method="POST" action="{{ route('votes.question', $question) }}">
                                @csrf
                                <input type="hidden" name="value" value="1">
                                <button type="submit" class="vote-btn {{ $myQuestionVote === 1 ? 'is-active' : '' }}"
                                        aria-label="Upvote this question"
                                        aria-pressed="{{ $myQuestionVote === 1 ? 'true' : 'false' }}">
                                    <i class="bi bi-caret-up-fill" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endauth

                        <div class="aq-vote-score" aria-label="{{ $question->score() }} votes">{{ $question->score() }}</div>

                        @auth
                            <form method="POST" action="{{ route('votes.question', $question) }}">
                                @csrf
                                <input type="hidden" name="value" value="-1">
                                <button type="submit" class="vote-btn {{ $myQuestionVote === -1 ? 'is-active' : '' }}"
                                        aria-label="Downvote this question"
                                        aria-pressed="{{ $myQuestionVote === -1 ? 'true' : 'false' }}">
                                    <i class="bi bi-caret-down-fill" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endauth
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <div class="prose mb-3">{{ $question->body }}</div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @if ($question->category)
                                <span class="badge text-bg-info">{{ $question->category->name }}</span>
                            @endif
                            @foreach ($question->tags as $tag)
                                <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag">{{ $tag->name }}</a>
                            @endforeach
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3"
                             style="border-top: 1px solid var(--aq-border);">
                            <a href="{{ route('profile.show', $question->user) }}"
                               class="d-flex align-items-center gap-2 text-decoration-none">
                                @include('partials.avatar', ['user' => $question->user, 'size' => 34])
                                <span style="font-size: .8125rem;">
                                    <span class="fw-semibold d-block" style="color: var(--aq-ink);">{{ $question->user->name }}</span>
                                    <span style="color: var(--aq-muted);">{{ $question->user->roleName() }}</span>
                                </span>
                            </a>

                            @if ($canEditQuestion)
                                <div class="d-flex gap-2">
                                    <a href="{{ route('questions.edit', $question) }}" class="btn btn-sm btn-quiet">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                                    </a>
                                    <form method="POST" action="{{ route('questions.destroy', $question) }}"
                                          onsubmit="return confirm('Delete this question and all of its answers?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </article>

        {{-- Answers --}}
        <section aria-labelledby="answers-heading">
            <div class="aq-section-head">
                <h2 id="answers-heading">
                    {{ $answers->count() }} {{ \Illuminate\Support\Str::plural('answer', $answers->count()) }}
                </h2>
            </div>

            @forelse ($answers as $answer)
                @php $myAnswerVote = $answer->voteOf($me)?->value; @endphp

                <article class="card mb-3 {{ $answer->is_accepted ? 'answer-accepted' : '' }}">
                    <div class="card-body">
                        @if ($answer->is_accepted)
                            <p class="d-flex align-items-center gap-2 mb-3"
                               style="font-size: .75rem; font-weight: 650; color: var(--aq-accent-ink);">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Accepted answer
                            </p>
                        @endif

                        <div class="d-flex gap-3">
                            <div class="aq-vote">
                                @auth
                                    <form method="POST" action="{{ route('votes.answer', $answer) }}">
                                        @csrf
                                        <input type="hidden" name="value" value="1">
                                        <button type="submit" class="vote-btn {{ $myAnswerVote === 1 ? 'is-active' : '' }}"
                                                aria-label="Upvote this answer"
                                                aria-pressed="{{ $myAnswerVote === 1 ? 'true' : 'false' }}">
                                            <i class="bi bi-caret-up-fill" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endauth

                                <div class="aq-vote-score" aria-label="{{ $answer->score() }} votes">{{ $answer->score() }}</div>

                                @auth
                                    <form method="POST" action="{{ route('votes.answer', $answer) }}">
                                        @csrf
                                        <input type="hidden" name="value" value="-1">
                                        <button type="submit" class="vote-btn {{ $myAnswerVote === -1 ? 'is-active' : '' }}"
                                                aria-label="Downvote this answer"
                                                aria-pressed="{{ $myAnswerVote === -1 ? 'true' : 'false' }}">
                                            <i class="bi bi-caret-down-fill" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endauth
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <div class="prose mb-3">{{ $answer->body }}</div>

                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2"
                                     style="border-top: 1px solid var(--aq-border);">
                                    <a href="{{ route('profile.show', $answer->user) }}"
                                       class="d-flex align-items-center gap-2 text-decoration-none">
                                        @include('partials.avatar', ['user' => $answer->user, 'size' => 28])
                                        <span style="font-size: .8125rem;">
                                            <span class="fw-semibold" style="color: var(--aq-ink);">{{ $answer->user->name }}</span>
                                            <span style="color: var(--aq-muted);">· {{ $answer->created_at->diffForHumans(null, true) }} ago</span>
                                        </span>
                                    </a>

                                    <div class="d-flex gap-2">
                                        @if ($canEditQuestion && ! $answer->is_accepted)
                                            <form method="POST" action="{{ route('answers.accept', $answer) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i> Accept
                                                </button>
                                            </form>
                                        @endif

                                        @if ($me && ($me->id === $answer->user_id || $me->canModerate()))
                                            <form method="POST" action="{{ route('answers.destroy', $answer) }}"
                                                  onsubmit="return confirm('Delete this answer?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                    <span class="visually-hidden">Delete answer</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card mb-3">
                    <div class="card-body">
                        @include('partials.empty', [
                            'icon' => 'chat-left-text',
                            'title' => 'No answers yet',
                            'message' => 'Share what you know — be the first to answer.',
                        ])
                    </div>
                </div>
            @endforelse
        </section>

        {{-- Post an answer --}}
        <section class="card mt-4" aria-labelledby="post-answer-heading">
            <div class="card-body">
                <h2 id="post-answer-heading" style="font-size: .9375rem; font-weight: 650; margin: 0 0 .875rem;">Your answer</h2>

                @auth
                    @if ($question->is_closed)
                        <p class="mb-0" style="font-size: .8125rem; color: var(--aq-muted);">
                            <i class="bi bi-lock me-1" aria-hidden="true"></i>
                            This question has been closed by a moderator and no longer accepts answers.
                        </p>
                    @else
                        <form method="POST" action="{{ route('answers.store', $question) }}" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="answer_body" class="visually-hidden">Write your answer</label>
                                <textarea id="answer_body" name="body" rows="6"
                                          class="form-control @error('body') is-invalid @enderror"
                                          required minlength="10"
                                          placeholder="Write a helpful, specific answer…">{{ old('body') }}</textarea>
                                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-accent">Post your answer</button>
                        </form>
                    @endif
                @else
                    <p class="mb-0" style="font-size: .875rem;">
                        <a href="{{ route('login') }}">Sign in</a> or
                        <a href="{{ route('register') }}">create an account</a> to answer this question.
                    </p>
                @endauth
            </div>
        </section>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <aside class="card mb-3">
            <div class="card-body">
                <h2 class="aq-label mb-3">Asked by</h2>
                <div class="d-flex align-items-center gap-3">
                    @include('partials.avatar', ['user' => $question->user, 'size' => 44])
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $question->user) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $question->user->name }}</a>
                        <span class="badge text-bg-light">{{ $question->user->roleName() }}</span>
                        @if ($question->user->profile?->headline)
                            <p class="mb-0 mt-2" style="font-size: .8125rem; color: var(--aq-muted);">{{ $question->user->profile->headline }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </aside>

        <aside class="card aq-card-quiet">
            <div class="card-body">
                <h2 class="aq-label mb-2">How voting works</h2>
                <ul class="mb-0 ps-3 d-grid gap-1" style="font-size: .8125rem; color: var(--aq-muted);">
                    <li>Upvote answers that are correct and useful.</li>
                    <li>Click the same arrow again to remove your vote.</li>
                    <li>You cannot vote on your own posts.</li>
                    <li>The question author can accept one answer.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
