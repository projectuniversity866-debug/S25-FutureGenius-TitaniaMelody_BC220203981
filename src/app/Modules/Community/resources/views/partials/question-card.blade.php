{{-- One row in the questions list. --}}
<article class="aq-qrow">
    <div class="aq-stat-stack">
        <div class="aq-stat-box">
            <strong>{{ $question->score() }}</strong>
            <span>{{ \Illuminate\Support\Str::plural('vote', abs($question->score())) }}</span>
        </div>
        <div class="aq-stat-box {{ $question->answers_count > 0 ? 'has-answers' : '' }}">
            <strong>{{ $question->answers_count ?? 0 }}</strong>
            <span>{{ \Illuminate\Support\Str::plural('answer', $question->answers_count ?? 0) }}</span>
        </div>
    </div>

    <div class="flex-grow-1 min-w-0">
        <h2 class="aq-qrow-title">
            <a href="{{ route('questions.show', $question) }}">{{ $question->title }}</a>
            @if ($question->is_closed)
                <span class="badge text-bg-secondary ms-1 align-middle">Closed</span>
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
    </div>
</article>
