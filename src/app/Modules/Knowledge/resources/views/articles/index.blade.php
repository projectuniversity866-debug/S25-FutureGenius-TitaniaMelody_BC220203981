@extends('layouts.app')
@section('title', 'Knowledge Hub')
@section('subtitle', 'Articles and guides by verified experts')

@use('App\Modules\Knowledge\Models\Article')

@section('content')
@php
    $canWrite = auth()->check() && auth()->user()->can('create', Article::class);
    $kindUrl = fn ($k) => route('articles.index', array_filter(array_merge(request()->except('page', 'kind'), ['kind' => $k])));
    $tabs = [['label' => 'All', 'url' => $kindUrl(null), 'active' => ! $kind, 'count' => $counts->sum()]];
    foreach (Article::KINDS as $key => $label) {
        $tabs[] = ['label' => \Illuminate\Support\Str::plural($label), 'url' => $kindUrl($key), 'active' => $kind === $key, 'count' => $counts[$key] ?? 0];
    }
@endphp

<x-page-header title="Knowledge Hub"
               subtitle="In-depth articles and step-by-step guides written by verified experts, across the four topic tracks.">
    <x-slot:actions>
        @auth
            @if ($canWrite)
                <a href="{{ route('articles.mine') }}" class="btn btn-quiet">My articles</a>
                <a href="{{ route('articles.create') }}" class="btn btn-accent">
                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Write
                </a>
            @else
                <a href="{{ route('verification.show') }}" class="btn btn-quiet">
                    <i class="bi bi-patch-check me-1" aria-hidden="true"></i> Become a verified expert
                </a>
            @endif
        @endauth
    </x-slot:actions>
</x-page-header>

<x-tabs label="Kind" :items="$tabs" />

<div class="aq-chip-row mb-3" role="navigation" aria-label="Topic">
    <a href="{{ route('articles.index', array_filter(['kind' => $kind])) }}" @class(['aq-tag', 'is-active' => ! $category]) @if (! $category) aria-current="page" @endif>All topics</a>
    @foreach ($categories as $cat)
        <a href="{{ route('articles.index', array_filter(['kind' => $kind, 'category' => $cat->slug])) }}"
           @class(['aq-tag', 'is-active' => $category === $cat->slug]) @if ($category === $cat->slug) aria-current="page" @endif>
            {{ $cat->name }} <span class="aq-tag-n">{{ $cat->articles_count }}</span>
        </a>
    @endforeach
</div>

@if ($articles->isEmpty())
    <div class="card">
        <x-empty icon="journal-richtext" title="Nothing here yet"
                 message="Verified experts publish articles and guides here. Try another topic or kind." />
    </div>
@else
    <div class="row g-3">
        @foreach ($articles as $article)
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 aq-article-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge {{ $article->kind === 'guide' ? 'text-bg-warning' : 'text-bg-primary' }}">{{ $article->kindLabel() }}</span>
                            @if ($article->category)<span class="badge text-bg-info">{{ $article->category->name }}</span>@endif
                        </div>
                        <h2 class="aq-card-title"><a href="{{ route('articles.show', $article) }}" class="stretched-link text-decoration-none">{{ $article->title }}</a></h2>
                        <p class="aq-text-sm aq-ink-2 flex-grow-1">{{ $article->summary }}</p>
                        <div class="d-flex align-items-center gap-2 aq-text-xs aq-muted">
                            <x-avatar :user="$article->author" size="xs" />
                            <span>{{ $article->author->name }}</span>
                            <x-verified-badge :user="$article->author" />
                            <span class="ms-auto">{{ $article->reading_minutes }} min read</span>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <nav aria-label="Article pages" class="mt-4">{{ $articles->links() }}</nav>
@endif
@endsection
