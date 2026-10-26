@extends('layouts.app')
@section('title', 'Tags')
@section('subtitle', 'Community Q&A')

@section('content')
<x-page-header title="Tags" :breadcrumbs="['Community Q&A' => route('questions.index'), 'Tags' => null]"
               subtitle="A tag groups questions on one topic. Follow tags to hear about new questions in them and to filter the questions list to your tags." />

@php
    $sortUrl = fn ($key) => route('tags.index', array_merge(request()->except('page', 'sort'), ['sort' => $key]));
@endphp

<div class="aq-tags-toolbar">
    <x-tabs label="Sort tags" class="mb-0" :items="collect(\App\Modules\Community\Http\Controllers\TagController::SORTS)->map(fn ($label, $key) => [
        'label' => $label, 'url' => $sortUrl($key), 'active' => $sort === $key,
    ])->values()->all()" />

    <form method="GET" action="{{ route('tags.index') }}" role="search" aria-label="Find a tag" class="aq-tags-search">
        @if ($sort !== 'popular')
            <input type="hidden" name="sort" value="{{ $sort }}">
        @endif
        <label for="tag-search" class="visually-hidden">Find a tag</label>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="tag-search" name="q" value="{{ $search }}" class="form-control form-control-sm"
               placeholder="Find a tag…">
    </form>
</div>

<p class="aq-text-sm aq-muted mb-3" role="status">
    {{ number_format($tags->total()) }} {{ \Illuminate\Support\Str::plural('tag', $tags->total()) }}@if ($search !== '') matching “{{ $search }}” · <a href="{{ route('tags.index', $sort !== 'popular' ? ['sort' => $sort] : []) }}">Clear</a>@endif
</p>

@if ($tags->isEmpty())
    <div class="card">
        <x-empty icon="tags" title="No tags found"
                 message="Tags are created when someone asks a question. Try a shorter search." />
    </div>
@else
    <ul class="aq-tag-grid">
        @foreach ($tags as $tag)
            <li class="aq-tag-card">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <a href="{{ route('questions.index', ['tag' => $tag->slug]) }}" class="aq-tag aq-tag-lg">{{ $tag->name }}</a>
                    @if ($tag->recent_questions_count)
                        <span class="aq-tag-card-new">{{ $tag->recent_questions_count }} this week</span>
                    @endif
                </div>
                <p class="aq-tag-card-stat">
                    {{ number_format($tag->questions_count) }} {{ \Illuminate\Support\Str::plural('question', $tag->questions_count) }}
                </p>
                @include('community::partials.follow-button', [
                    'tag' => $tag, 'following' => (bool) ($tag->is_followed ?? false), 'count' => $tag->followers_count,
                ])
            </li>
        @endforeach
    </ul>

    <nav aria-label="Tag list pages" class="mt-4">{{ $tags->links() }}</nav>
@endif
@endsection
