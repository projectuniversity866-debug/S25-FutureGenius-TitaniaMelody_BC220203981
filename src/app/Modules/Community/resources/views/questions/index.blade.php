@extends('layouts.app')
@section('title', 'Community Q&A')
@section('subtitle', 'Ask, answer and upvote across four topic tracks')

@section('content')
<x-page-header title="Community Q&A">
    <p>
        Questions across EdTech, Artificial Intelligence, Web Development and Internet Governance.
    </p>
    <x-slot:actions>
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
    $sortUrl = fn ($key) => route('questions.index', array_merge(request()->except('page', 'sort'), ['sort' => $key]));
@endphp
<x-tabs label="Sort questions" :items="[
    ['label' => 'Newest', 'url' => $sortUrl('latest'), 'active' => $sort === 'latest'],
    ['label' => 'Most viewed', 'url' => $sortUrl('popular'), 'active' => $sort === 'popular'],
    ['label' => 'Unanswered', 'url' => $sortUrl('unanswered'), 'active' => $sort === 'unanswered'],
]" />

<div class="row g-4">
    {{-- Filters: first on phones, beside the list from lg up. --}}
    <div class="col-lg-4 order-lg-2">
        <x-filter-panel :action="route('questions.index')" label="Filter questions" stacked
                        :keep="['sort', 'tag']"
                        :filters="[
                            'q' => 'Keyword',
                            'category' => ['Category', $categories->pluck('name', 'slug')->all()],
                            'tag' => 'Tag',
                        ]"
                        :total="$questions->total()" noun="question">
            <div>
                <label for="q" class="form-label">Keyword</label>
                <input type="search" id="q" name="q" class="form-control form-control-sm"
                       value="{{ request('q') }}" placeholder="Search questions…">
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
        </x-filter-panel>

        @if ($popularTags->isNotEmpty())
            <x-card title="Popular tags" icon="tags" class="d-none d-lg-block">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($popularTags as $tag)
                        <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag">
                            {{ $tag->name }} <span class="aq-tag-n">{{ $tag->questions_count }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>

    {{-- Question list --}}
    <div class="col-lg-8 order-lg-1">
        @forelse ($questions as $question)
            @include('community::partials.question-card', ['question' => $question])
        @empty
            <div class="card">
                <x-empty icon="search" title="No questions match your filters" message="Try a different keyword or category." />
            </div>
        @endforelse

        <nav aria-label="Question list pages" class="mt-4">
            {{ $questions->links() }}
        </nav>
    </div>
</div>
@endsection
