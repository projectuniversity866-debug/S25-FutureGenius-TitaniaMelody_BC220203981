{{-- One row in the questions list. --}}
<article class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex gap-3">
            <div class="text-center flex-shrink-0" style="min-width: 68px;">
                <div class="border rounded py-1 mb-1">
                    <div class="fw-bold lh-1">{{ $question->score() }}</div>
                    <div class="small text-muted">votes</div>
                </div>
                <div class="border rounded py-1 {{ $question->answers_count > 0 ? 'border-success text-success' : 'text-muted' }}">
                    <div class="fw-bold lh-1">{{ $question->answers_count ?? 0 }}</div>
                    <div class="small">answers</div>
                </div>
            </div>

            <div class="flex-grow-1 min-w-0">
                <h2 class="h6 mb-1">
                    <a href="{{ route('questions.show', $question) }}" class="stretched-link-none text-decoration-none">
                        {{ $question->title }}
                    </a>
                    @if ($question->is_closed)
                        <span class="badge text-bg-secondary ms-1">Closed</span>
                    @endif
                </h2>

                <p class="text-muted small mb-2">{{ $question->excerpt(24) }}</p>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if ($question->category)
                        <a href="{{ route('questions.index', ['category' => $question->category->slug]) }}"
                           class="badge text-bg-info text-decoration-none">
                            {{ $question->category->name }}
                        </a>
                    @endif

                    @foreach ($question->tags as $tag)
                        <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}"
                           class="badge text-bg-light border text-decoration-none">{{ $tag->name }}</a>
                    @endforeach

                    <span class="ms-auto small text-muted">
                        asked by
                        <a href="{{ route('profile.show', $question->user) }}"
                           class="text-decoration-none">{{ $question->user->name }}</a>
                        {{ $question->created_at->diffForHumans() }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</article>
