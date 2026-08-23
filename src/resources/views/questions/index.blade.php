@extends('layouts.app')
@section('title', 'Community Q&A')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1">Community Q&amp;A</h1>
        <p class="text-muted small mb-0">
            {{ $questions->total() }} question(s) across EdTech, AI, Web Development and Internet Governance.
        </p>
    </div>
    @auth
        <a href="{{ route('questions.create') }}" class="btn btn-info fw-semibold">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Ask a question
        </a>
    @else
        <a href="{{ route('login') }}" class="btn btn-outline-dark">Sign in to ask</a>
    @endauth
</div>

<div class="row g-4">
    {{-- Filters sidebar --}}
    <div class="col-lg-3 order-lg-2">
        <form method="GET" action="{{ route('questions.index') }}" class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">Search &amp; filter</h2>

                <div class="mb-3">
                    <label for="q" class="form-label small">Keyword</label>
                    <input type="search" id="q" name="q" class="form-control form-control-sm"
                           value="{{ request('q') }}" placeholder="Search questions…">
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label small">Category</label>
                    <select id="category" name="category" class="form-select form-select-sm">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                                {{ $category->name }} ({{ $category->questions_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="sort" class="form-label small">Show</label>
                    <select id="sort" name="sort" class="form-select form-select-sm">
                        <option value="latest" @selected($sort === 'latest')>Newest first</option>
                        <option value="popular" @selected($sort === 'popular')>Most viewed</option>
                        <option value="unanswered" @selected($sort === 'unanswered')>Unanswered only</option>
                    </select>
                </div>

                @if (request('tag'))
                    <input type="hidden" name="tag" value="{{ request('tag') }}">
                    <p class="small mb-3">
                        Tag: <span class="badge text-bg-info">{{ request('tag') }}</span>
                        <a href="{{ route('questions.index', request()->except('tag')) }}" class="ms-1">clear</a>
                    </p>
                @endif

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-dark btn-sm">Apply filters</button>
                    @if (request()->hasAny(['q', 'category', 'tag']) || $sort !== 'latest')
                        <a href="{{ route('questions.index') }}" class="btn btn-link btn-sm">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($popularTags->isNotEmpty())
            <section class="card border-0 shadow-sm" aria-labelledby="tags-heading">
                <div class="card-body">
                    <h2 id="tags-heading" class="h6 fw-bold mb-3">Popular tags</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($popularTags as $tag)
                            <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}"
                               class="badge text-bg-light border text-decoration-none">
                                {{ $tag->name }} <span class="text-muted">{{ $tag->questions_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>

    {{-- Question list --}}
    <div class="col-lg-9 order-lg-1">
        @forelse ($questions as $question)
            @include('partials.question-card', ['question' => $question])
        @empty
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @include('partials.empty', [
                        'icon' => 'search',
                        'title' => 'No questions match your filters',
                        'message' => 'Try a different keyword or category.',
                    ])
                </div>
            </div>
        @endforelse

        <nav aria-label="Question list pages" class="mt-4">
            {{ $questions->links() }}
        </nav>
    </div>
</div>
@endsection
