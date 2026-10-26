@extends('layouts.app')
@section('title', $article->title)
@section('subtitle', 'Knowledge Hub')

@section('content')
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('articles.index') }}">Knowledge Hub</a></li>
        @if ($article->category)
            <li class="breadcrumb-item"><a href="{{ route('articles.index', ['category' => $article->category->slug]) }}">{{ $article->category->name }}</a></li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">{{ $article->kindLabel() }}</li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-8">
        <article class="card">
            <div class="card-body p-4">
                @unless ($article->isPublished())
                    <div class="alert alert-warning py-2 aq-text-sm" role="status">
                        <i class="bi bi-eye-slash me-1" aria-hidden="true"></i> Draft — only you{{ auth()->id() !== $article->user_id ? ', the author,' : '' }} and moderators can see it.
                    </div>
                @endunless
                <p class="aq-label mb-2">{{ $article->kindLabel() }} · {{ $article->reading_minutes }} min read</p>
                <h1 class="aq-h1 mb-2">{{ $article->title }}</h1>
                <p class="aq-text aq-ink-2 mb-3">{{ $article->summary }}</p>
                <p class="aq-text-xs aq-muted pb-3 mb-4 aq-border-bottom">
                    {{ $article->isPublished() ? 'Published '.$article->published_at->format('d M Y') : 'Not published' }}
                    @if ($article->updated_at->gt($article->published_at ?? $article->created_at)) · updated {{ $article->updated_at->diffForHumans() }} @endif
                    · {{ number_format($article->views_count) }} {{ \Illuminate\Support\Str::plural('view', $article->views_count) }}
                </p>

                <x-markdown :text="$article->body" class="aq-article-body" />

                @if ($article->isPublished())
                    <p class="mt-4 mb-0 text-end"><x-report-link :item="$article" /></p>
                @endif

                @can('update', $article)
                    <div class="d-flex flex-wrap gap-2 pt-3 mt-4 aq-border-top">
                        <a href="{{ route('articles.edit', $article) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('articles.destroy', $article) }}"
                              data-confirm-title="Delete “{{ $article->title }}”?" data-confirm="The article is permanently deleted."
                              data-confirm-button="Delete article">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i> Delete
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </article>
    </div>

    <div class="col-lg-4">
        <aside class="card mb-3">
            <div class="card-body">
                <h2 class="aq-label mb-3">Written by</h2>
                <div class="d-flex align-items-center gap-3">
                    <x-avatar :user="$article->author" size="md" />
                    <div class="min-w-0">
                        <a href="{{ route('profile.show', $article->author) }}" class="fw-semibold text-decoration-none d-block">{{ $article->author->name }}</a>
                        <x-verified-badge :user="$article->author" label class="aq-text-xs" />
                    </div>
                </div>
                @if ($article->author->profile?->bio)
                    <p class="aq-text-sm aq-muted mt-3 mb-0">{{ \Illuminate\Support\Str::limit($article->author->profile->bio, 200) }}</p>
                @endif
            </div>
        </aside>

        @if ($more->isNotEmpty())
            <aside class="card" aria-labelledby="more-heading">
                <div class="card-body">
                    <h2 id="more-heading" class="aq-label mb-2">More {{ $article->category ? 'in '.$article->category->name : 'from the hub' }}</h2>
                    <ul class="aq-related">
                        @foreach ($more as $item)
                            <li>
                                <span class="aq-related-count">{{ $item->reading_minutes }}<span class="visually-hidden"> minute read</span></span>
                                <a href="{{ route('articles.show', $item) }}">{{ $item->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif
    </div>
</div>
@endsection
