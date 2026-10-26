@extends('layouts.app')
@section('title', $question->title)
@section('subtitle', 'Community Q&A')

@php
    $me = auth()->user();
    $canEditQuestion = (bool) $me?->can('update', $question);
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
                <h1 class="aq-h1 mb-2">
                    {{ $question->title }}
                    @if ($question->is_closed)
                        <span class="badge text-bg-secondary align-middle">Closed</span>
                    @endif
                </h1>

                <p class="aq-label mb-3">
                    Asked {{ $question->created_at->diffForHumans() }}
                    · {{ number_format($question->views_count) }} {{ \Illuminate\Support\Str::plural('view', $question->views_count) }}
                    · {{ $question->answers->count() }} {{ \Illuminate\Support\Str::plural('answer', $question->answers->count()) }}
                </p>

                <div class="d-flex gap-3">
                    <div class="aq-vote-col">
                        @include('community::partials.vote', ['post' => $question, 'url' => route('votes.question', $question), 'noun' => 'question'])
                        @include('community::partials.bookmark', ['question' => $question, 'saved' => $saved])
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <x-markdown :text="$question->body" class="mb-3" />

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @if ($question->category)
                                <span class="badge text-bg-info">{{ $question->category->name }}</span>
                            @endif
                            @foreach ($question->tags as $tag)
                                <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag">{{ $tag->name }}</a>
                            @endforeach
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 aq-border-top">
                            <a href="{{ route('profile.show', $question->user) }}"
                               class="d-flex align-items-center gap-2 text-decoration-none">
                                <x-avatar :user="$question->user" size="sm" />
                                <span class="aq-text-sm">
                                    <span class="fw-semibold d-block aq-ink">{{ $question->user->name }} <x-verified-badge :user="$question->user" /></span>
                                    <span class="aq-muted">{{ $question->user->roleName() }}</span>
                                </span>
                            </a>

                            @if ($canEditQuestion)
                                <div class="d-flex gap-2">
                                    <a href="{{ route('questions.edit', $question) }}" class="btn btn-sm btn-quiet">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                                    </a>
                                    <form method="POST" action="{{ route('questions.destroy', $question) }}"
                                          data-confirm-title="Delete this question?"
                                          data-confirm="Your question and all {{ $question->answers->count() }} {{ \Illuminate\Support\Str::plural('answer', $question->answers->count()) }} are permanently deleted."
                                          data-confirm-button="Delete question">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            @endif
                            <x-report-link :item="$question" />
                        </div>

                        @if ($question->revisions_count)
                            <p class="aq-edited">
                                Edited {{ $question->updated_at->diffForHumans() }} ·
                                <a href="{{ route('questions.history', $question) }}">view {{ $question->revisions_count }} earlier {{ \Illuminate\Support\Str::plural('version', $question->revisions_count) }}</a>
                            </p>
                        @endif

                        @include('community::partials.comments', ['post' => $question, 'storeRoute' => route('comments.question', $question)])
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
                <article id="answer-{{ $answer->id }}" class="card mb-3 {{ $answer->is_accepted ? 'answer-accepted' : '' }}">
                    <div class="card-body">
                        @if ($answer->is_accepted)
                            <p class="d-flex align-items-center gap-2 mb-3 aq-text-xs aq-strong aq-accent-ink">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Accepted answer
                            </p>
                        @endif

                        <div class="d-flex gap-3">
                            <div class="aq-vote-col">
                                @include('community::partials.vote', ['post' => $answer, 'url' => route('votes.answer', $answer), 'noun' => 'answer'])
                            </div>

                            <div class="flex-grow-1 min-w-0">
                                <x-markdown :text="$answer->body" class="mb-3" />

                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 aq-border-top">
                                    <a href="{{ route('profile.show', $answer->user) }}"
                                       class="d-flex align-items-center gap-2 text-decoration-none">
                                        <x-avatar :user="$answer->user" size="xs" />
                                        <span class="aq-text-sm">
                                            <span class="fw-semibold aq-ink">{{ $answer->user->name }}</span> <x-verified-badge :user="$answer->user" />
                                            <span class="aq-muted">· {{ $answer->created_at->diffForHumans(null, true) }} ago</span>
                                        </span>
                                    </a>

                                    <div class="d-flex align-items-center gap-2">
                                        <x-report-link :item="$answer" />
                                        @if ($me?->can('update', $answer))
                                            <a href="#edit-answer-{{ $answer->id }}" class="btn btn-sm btn-quiet"
                                               onclick="document.getElementById('edit-answer-{{ $answer->id }}').open = true">
                                                <i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Edit answer</span>
                                            </a>
                                        @endif
                                        @if (! $answer->is_accepted && $me?->can('accept', $answer))
                                            <form method="POST" action="{{ route('answers.accept', $answer) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i> Accept
                                                </button>
                                            </form>
                                        @endif

                                        @if ($me?->can('delete', $answer))
                                            <form method="POST" action="{{ route('answers.destroy', $answer) }}"
                                                  data-confirm-title="Delete this answer?"
                                                  data-confirm="The answer and its votes are permanently deleted."
                                                  data-confirm-button="Delete answer">
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

                                @if ($me?->can('update', $answer))
                                    <details id="edit-answer-{{ $answer->id }}" class="aq-comment-add mt-2">
                                        <summary class="visually-hidden">Edit this answer</summary>
                                        <form method="POST" action="{{ route('answers.update', $answer) }}">
                                            @csrf
                                            @method('PUT')
                                            <label for="answer-body-{{ $answer->id }}" class="form-label">Edit your answer</label>
                                            <textarea id="answer-body-{{ $answer->id }}" name="body" rows="6" class="form-control"
                                                      required minlength="10">{{ $answer->body }}</textarea>
                                            <div class="d-flex gap-2 justify-content-end mt-2">
                                                <button type="submit" class="btn btn-accent btn-sm">Save changes</button>
                                            </div>
                                        </form>
                                    </details>
                                @endif

                                @if ($answer->revisions_count)
                                    <p class="aq-edited">
                                        Edited {{ $answer->updated_at->diffForHumans() }} ·
                                        <a href="{{ route('answers.history', $answer) }}">view {{ $answer->revisions_count }} earlier {{ \Illuminate\Support\Str::plural('version', $answer->revisions_count) }}</a>
                                    </p>
                                @endif

                                @include('community::partials.comments', ['post' => $answer, 'storeRoute' => route('comments.answer', $answer)])
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card mb-3">
                    <div class="card-body">
                        <x-empty icon="chat-left-text" title="No answers yet" message="Share what you know — be the first to answer." />
                    </div>
                </div>
            @endforelse
        </section>

        {{-- Post an answer --}}
        <section class="card mt-4" aria-labelledby="post-answer-heading">
            <div class="card-body">
                <h2 id="post-answer-heading" class="aq-h3 mb-3">Your answer</h2>

                @auth
                    @if ($question->is_closed)
                        <p class="mb-0 aq-text-sm aq-muted">
                            <i class="bi bi-lock me-1" aria-hidden="true"></i>
                            This question has been closed by a moderator and no longer accepts answers.
                        </p>
                    @else
                        <form method="POST" action="{{ route('answers.store', $question) }}" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="answer_body" class="visually-hidden">Write your answer</label>
                                <x-vue name="MarkdownEditor" :props="['for' => 'answer_body', 'previewUrl' => route('markdown.preview')]" />
                                <textarea id="answer_body" name="body" rows="6"
                                          class="form-control @error('body') is-invalid @enderror"
                                          required minlength="10"
                                          placeholder="Write a helpful, specific answer…">{{ old('body') }}</textarea>
                                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <x-vue name="CharacterCounter" :props="['for' => 'answer_body', 'min' => 10]" />
                            </div>
                            <button type="submit" class="btn btn-accent">Post your answer</button>
                        </form>
                    @endif
                @else
                    <p class="mb-0 aq-text">
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
                    <x-avatar :user="$question->user" size="md" />
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $question->user) }}"
                           class="fw-semibold text-decoration-none d-block">{{ $question->user->name }}</a>
                        <span class="badge text-bg-light">{{ $question->user->roleName() }}</span>
                        @if ($question->user->profile?->headline)
                            <p class="mb-0 mt-2 aq-text-sm aq-muted">{{ $question->user->profile->headline }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </aside>

        @if ($question->tags->isNotEmpty())
            <aside class="card mb-3" aria-labelledby="follow-tags-heading">
                <div class="card-body">
                    <h2 id="follow-tags-heading" class="aq-label mb-1">Follow these tags</h2>
                    <p class="aq-text-xs aq-muted mb-2">Get notified when someone asks a new question with them.</p>
                    <ul class="aq-follow-list">
                        @foreach ($question->tags as $tag)
                            <li>
                                <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag">{{ $tag->name }}</a>
                                @include('community::partials.follow-button', [
                                    'tag' => $tag, 'following' => in_array($tag->id, $followedTagIds, true), 'count' => $tag->followers_count,
                                ])
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif

        @if ($related->isNotEmpty())
            <aside class="card mb-3" aria-labelledby="related-heading">
                <div class="card-body">
                    <h2 id="related-heading" class="aq-label mb-2">Related questions</h2>
                    <ul class="aq-related">
                        @foreach ($related as $item)
                            <li>
                                <span @class(['aq-related-count', 'has-answers' => $item->answers_count && ! $item->has_accepted_answer, 'is-accepted' => $item->has_accepted_answer])>
                                    @if ($item->has_accepted_answer)<i class="bi bi-check-lg" aria-hidden="true"></i>@endif{{ $item->answers_count }}
                                    <span class="visually-hidden">{{ \Illuminate\Support\Str::plural('answer', $item->answers_count) }}{{ $item->has_accepted_answer ? ', one accepted' : '' }}</span>
                                </span>
                                <a href="{{ route('questions.show', $item) }}">{{ $item->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif

        <aside class="card aq-card-quiet">
            <div class="card-body">
                <h2 class="aq-label mb-2">How voting works</h2>
                <ul class="mb-0 ps-3 d-grid gap-1 aq-text-sm aq-muted">
                    <li>Upvote answers that are correct and useful.</li>
                    <li>Click the same arrow again to remove your vote.</li>
                    <li>You cannot vote on your own posts.</li>
                    <li>The question author can accept one answer.</li>
                    <li>Use the bookmark to save a question; find it under Saved.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
