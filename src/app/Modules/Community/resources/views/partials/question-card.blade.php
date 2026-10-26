{{-- One row in a questions list.

     Shows "accepted" and "saved" when the list query loaded them
     (QuestionFeed::listing). On the Saved page, pass `savedAt` to show when
     it was saved and a Remove button. --}}
@php
    $answerCount = $question->answers_count ?? 0;
    $accepted = (bool) ($question->has_accepted_answer ?? false);
@endphp
<article class="aq-qrow">
    <div class="aq-stat-stack">
        <div class="aq-stat-box">
            <strong>{{ $question->score() }}</strong>
            <span>{{ \Illuminate\Support\Str::plural('vote', abs($question->score())) }}</span>
        </div>
        <div @class(['aq-stat-box', 'has-answers' => $answerCount > 0 && ! $accepted, 'is-accepted' => $accepted])>
            <strong>@if ($accepted)<i class="bi bi-check-lg" aria-hidden="true"></i>@endif{{ $answerCount }}</strong>
            <span>{{ \Illuminate\Support\Str::plural('answer', $answerCount) }}</span>
            @if ($accepted)<span class="visually-hidden">, one accepted</span>@endif
        </div>
    </div>

    <div class="flex-grow-1 min-w-0">
        <h2 class="aq-qrow-title">
            <a href="{{ route('questions.show', $question) }}">{{ $question->title }}</a>
            @if ($question->is_closed)
                <span class="badge text-bg-secondary ms-1 align-middle">Closed</span>
            @endif
            @if (! isset($savedAt) && ($question->is_saved ?? false))
                <i class="bi bi-bookmark-fill aq-qrow-saved" aria-hidden="true" title="Saved"></i><span class="visually-hidden">(saved)</span>
            @endif
        </h2>

        <p class="aq-qrow-excerpt">{{ $question->excerpt(24) }}</p>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if ($question->category)
                <a href="{{ route('questions.index', ['category' => $question->category->slug]) }}"
                   class="badge text-bg-info text-decoration-none">{{ $question->category->name }}</a>
            @endif

            @foreach ($question->tags as $tag)
                <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag">{{ $tag->name }}</a>
            @endforeach

            <span class="ms-auto aq-qrow-meta d-flex align-items-center gap-2">
                <x-avatar :user="$question->user" size="xs" />
                <a href="{{ route('profile.show', $question->user) }}">{{ $question->user->name }}</a>
                <span>{{ $question->created_at->diffForHumans(null, true) }} ago</span>
            </span>
        </div>

        @isset($savedAt)
            <div class="aq-qrow-saved-bar">
                <span><i class="bi bi-bookmark-fill me-1" aria-hidden="true"></i>Saved {{ $savedAt->diffForHumans() }}</span>
                <form method="POST" action="{{ route('bookmarks.destroy', $question) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-link btn-sm p-0">
                        Remove<span class="visually-hidden"> “{{ $question->title }}” from saved</span>
                    </button>
                </form>
            </div>
        @endisset
    </div>
</article>
