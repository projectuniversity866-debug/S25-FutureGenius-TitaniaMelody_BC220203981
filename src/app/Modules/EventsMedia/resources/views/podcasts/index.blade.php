@extends('layouts.app')
@section('title', 'Podcasts')
@section('subtitle', 'Community podcasts')

@section('content')
<x-page-header title="Podcasts" subtitle="Conversations from the AQDesk community. Every episode has a full transcript.">
    @can('create', \App\Modules\EventsMedia\Models\PodcastSeries::class)
        <x-slot:actions>
            <a href="{{ route('podcasts.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New series</a>
        </x-slot:actions>
    @endcan
</x-page-header>

<form method="GET" action="{{ route('podcasts.index') }}" class="d-flex gap-2 mb-4" role="search">
    <label for="podcast_q" class="visually-hidden">Search podcasts and transcripts</label>
    <input type="search" id="podcast_q" name="q" value="{{ $q }}" class="form-control" placeholder="Search titles and transcripts…">
    <button type="submit" class="btn btn-soft">Search</button>
</form>

@if ($series->isEmpty())
    <div class="card">
        <x-empty icon="mic" :title="$q !== '' ? 'No podcasts match “'.$q.'”' : 'No podcasts yet'"
                 :message="$q !== '' ? 'Try another word — transcripts are searched too.' : 'Published series appear here.'" />
    </div>
@else
    <div class="row g-3">
        @foreach ($series as $s)
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 aq-article-card">
                    <div class="card-body d-flex gap-3">
                        <span class="aq-podcast-mark" aria-hidden="true"><i class="bi bi-mic"></i></span>
                        <div class="min-w-0 d-flex flex-column flex-grow-1">
                            <h2 class="aq-card-title"><a href="{{ route('podcasts.show', $s) }}" class="stretched-link text-decoration-none">{{ $s->title }}</a></h2>
                            <p class="aq-text-sm aq-ink-2 flex-grow-1">{{ $s->summary }}</p>
                            <p class="mb-0 aq-text-xs aq-muted">
                                {{ $s->published_count }} {{ \Illuminate\Support\Str::plural('episode', $s->published_count) }}
                                @if ($s->latest_at) · latest {{ \Illuminate\Support\Carbon::parse($s->latest_at)->diffForHumans() }}@endif
                                · hosted by {{ $s->host?->name ?? 'Deleted user' }}
                                @if (! $s->published_count) · <span class="badge text-bg-secondary">Not public yet</span>@endif
                            </p>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <nav aria-label="Podcast pages" class="mt-4">{{ $series->links() }}</nav>
@endif
@endsection
