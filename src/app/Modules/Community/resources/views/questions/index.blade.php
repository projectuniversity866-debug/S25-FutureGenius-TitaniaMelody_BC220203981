@extends('layouts.app')
@section('title', 'Community Q&A')
@section('subtitle', 'Ask, answer and upvote across four topic tracks')

@section('content')
<x-page-header title="Community Q&A">
    <p>
        Questions across EdTech, Artificial Intelligence, Web Development and Internet Governance.
    </p>
    <x-slot:actions>
        <a href="{{ route('tags.index') }}" class="btn btn-quiet">
            <i class="bi bi-tags me-1" aria-hidden="true"></i> Browse tags
        </a>
        @auth
            <a href="{{ route('questions.create') }}" class="btn btn-accent">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Ask a question
            </a>
        @else
            <a href="{{ route('login') }}" class="btn btn-quiet">Sign in to ask</a>
        @endauth
    </x-slot:actions>
</x-page-header>

@php
    $feed = \App\Modules\Community\Services\QuestionFeed::class;
    $sortUrl = fn ($key) => route('questions.index', array_merge(request()->except('page', 'sort'), ['sort' => $key]));
    $filterChips = [
        'q' => 'Keyword',
        'category' => ['Category', $categories->pluck('name', 'slug')->all()],
        'tag' => 'Tag',
    ] + (auth()->check() ? ['following' => ['Showing', ['1' => 'tags I follow']]] : []);
@endphp
<x-tabs label="Sort questions" :items="collect($feed::SORTS)->map(fn ($label, $key) => [
    'label' => $label, 'url' => $sortUrl($key), 'active' => $sort === $key,
])->values()->all()" />

@if ($sort === 'trending')
    <p class="aq-tabs-hint">
        <i class="bi bi-graph-up-arrow me-1" aria-hidden="true"></i>
        Most new answers, votes and comments in the last {{ $feed::TRENDING_DAYS }} days.
    </p>
@endif

<div class="row g-4">
    {{-- Filters: first on phones, beside the list from lg up. --}}
    <div class="col-lg-4 order-lg-2">
        <x-filter-panel :action="route('questions.index')" label="Filter questions" stacked
                        :keep="['sort', 'tag']" :filters="$filterChips"
                        :total="$questions->total()" noun="question">
            <div>
                <label for="q" class="form-label">Keyword</label>
                <input type="search" id="q" name="q" class="form-control form-control-sm"
                       value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Search questions…">
            </div>
            <div>
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
            @if ($followedTags->isNotEmpty())
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="following" name="following" value="1" @checked($following)>
                    <label class="form-check-label" for="following">
                        Only tags I follow ({{ $followedTags->count() }})
                    </label>
                </div>
            @endif
        </x-filter-panel>

        {{-- Beside the list from lg up; on phones "Browse tags" and the
             "Only tags I follow" filter cover it without pushing the list down. --}}
        @auth
            <x-card title="Your tags" icon="bookmark-star" class="mb-3 d-none d-lg-block">
                @if ($followedTags->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($followedTags as $followed)
                            <a href="{{ route('questions.index', ['tag' => $followed->slug]) }}" class="aq-tag">
                                {{ $followed->name }} <span class="aq-tag-n">{{ $followed->questions_count }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-3 aq-text-sm">
                        <a href="{{ route('questions.index', ['following' => 1]) }}">Questions in your tags</a>
                        <a href="{{ route('tags.index') }}">Manage</a>
                    </div>
                @else
                    <p class="aq-text-sm aq-muted mb-2">
                        Follow tags to hear about new questions in them and to filter this list to what you care about.
                    </p>
                    <a href="{{ route('tags.index') }}" class="aq-text-sm">Find tags to follow</a>
                @endif
            </x-card>
        @endauth

        @if ($popularTags->isNotEmpty())
            <x-card title="Popular tags" icon="tags" class="d-none d-lg-block">
                <x-slot:actions><a href="{{ route('tags.index') }}" class="aq-text-sm">All tags</a></x-slot:actions>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($popularTags as $popular)
                        <a href="{{ route('questions.index', ['tag' => $popular->slug]) }}" class="aq-tag">
                            {{ $popular->name }} <span class="aq-tag-n">{{ $popular->questions_count }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>

    {{-- Question list --}}
    <div class="col-lg-8 order-lg-1">
        @if ($currentTag)
            <section class="card aq-tag-head mb-3" aria-labelledby="tag-heading">
                <div class="card-body">
                    <div class="min-w-0">
                        <p class="aq-label mb-1">Tag</p>
                        <h2 id="tag-heading" class="aq-h2 mb-1">
                            <i class="bi bi-tag aq-accent" aria-hidden="true"></i> {{ $currentTag->name }}
                        </h2>
                        <p class="mb-0 aq-text-sm aq-muted">
                            {{ $currentTag->questions_count }} {{ \Illuminate\Support\Str::plural('question', $currentTag->questions_count) }}
                        </p>
                    </div>
                    @include('community::partials.follow-button', [
                        'tag' => $currentTag,
                        'following' => $followedTags->contains('id', $currentTag->id),
                        'count' => $currentTag->followers_count,
                        'signIn' => true,
                    ])
                </div>
            </section>
        @endif

        @forelse ($questions as $question)
            @include('community::partials.question-card', ['question' => $question])
        @empty
            <div class="card">
                @if ($sort === 'trending' && ! request()->anyFilled(['q', 'category', 'tag', 'following']))
                    <x-empty icon="graph-up-arrow" title="Nothing is trending this week"
                             message="Questions appear here when they get new answers, votes or comments in the last {{ $feed::TRENDING_DAYS }} days."
                             :action="['url' => route('questions.index'), 'label' => 'See the newest questions']" />
                @else
                    <x-empty icon="search" title="No questions match your filters" message="Try a different keyword or category." />
                @endif
            </div>
        @endforelse

        <nav aria-label="Question list pages" class="mt-4">
            {{ $questions->links() }}
        </nav>
    </div>
</div>
@endsection
