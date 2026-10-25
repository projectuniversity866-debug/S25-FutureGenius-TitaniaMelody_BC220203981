@extends('layouts.app')
@section('title', 'Community Q&A')
@section('subtitle', 'Ask, answer and upvote across four topic tracks')

@section('content')

<div class="aq-page-head">
    <div>
        <h1>Community Q&amp;A</h1>
        <p>
            <span class="aq-num">{{ $questions->total() }}</span> questions across EdTech,
            Artificial Intelligence, Web Development and Internet Governance.
        </p>
    </div>
    @auth
        <a href="{{ route('questions.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Ask a question
        </a>
    @else
        <a href="{{ route('login') }}" class="btn btn-quiet">Sign in to ask</a>
    @endauth
</div>

{{-- Sort tabs: the three most common views, one click away. --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    @foreach ([['latest', 'Newest'], ['popular', 'Most viewed'], ['unanswered', 'Unanswered']] as [$key, $label])
        <a href="{{ route('questions.index', array_merge(request()->except('page', 'sort'), ['sort' => $key])) }}"
           class="btn btn-sm {{ $sort === $key ? 'btn-soft' : 'btn-quiet' }}"
           @if ($sort === $key) aria-current="true" @endif>{{ $label }}</a>
    @endforeach

    @if (request()->hasAny(['q', 'category', 'tag']))
        <a href="{{ route('questions.index') }}" class="btn btn-link btn-sm ms-auto">
            <i class="bi bi-x-lg me-1" aria-hidden="true"></i> Clear filters
        </a>
    @endif
</div>

<div class="row g-4">
    {{-- Question list --}}
    <div class="col-lg-8 order-lg-1">
        @forelse ($questions as $question)
            @include('partials.question-card', ['question' => $question])
        @empty
            <div class="card">
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

    {{-- Filters --}}
    <div class="col-lg-4 order-lg-2">
        <form method="GET" action="{{ route('questions.index') }}" class="card mb-3">
            <div class="card-body">
                <h2 class="aq-label mb-3">Search &amp; filter</h2>

                <div class="mb-3">
                    <label for="q" class="form-label">Keyword</label>
                    <input type="search" id="q" name="q" class="form-control form-control-sm"
                           value="{{ request('q') }}" placeholder="Search questions…">
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
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
                    <label for="sort" class="form-label">Show</label>
                    <select id="sort" name="sort" class="form-select form-select-sm">
                        <option value="latest" @selected($sort === 'latest')>Newest first</option>
                        <option value="popular" @selected($sort === 'popular')>Most viewed</option>
                        <option value="unanswered" @selected($sort === 'unanswered')>Unanswered only</option>
                    </select>
                </div>

                @if (request('tag'))
                    <input type="hidden" name="tag" value="{{ request('tag') }}">
                    <p class="mb-3" style="font-size: .8125rem;">
                        Tag: <span class="badge text-bg-info">{{ request('tag') }}</span>
                        <a href="{{ route('questions.index', request()->except('tag')) }}" class="ms-1">clear</a>
                    </p>
                @endif

                <div class="d-grid">
                    <button type="submit" class="btn btn-quiet btn-sm">Apply filters</button>
                </div>
            </div>
        </form>

        @if ($popularTags->isNotEmpty())
            <section class="card" aria-labelledby="tags-heading">
                <div class="card-body">
                    <h2 id="tags-heading" class="aq-label mb-3">Popular tags</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($popularTags as $tag)
                            <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag">
                                {{ $tag->name }} <span class="aq-tag-n">{{ $tag->questions_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
