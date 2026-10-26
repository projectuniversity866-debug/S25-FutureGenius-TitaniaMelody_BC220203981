@extends('layouts.app')
@section('title', $series->title)
@section('subtitle', 'Podcast')

@push('head')
    <link rel="alternate" type="application/rss+xml" title="{{ $series->title }}" href="{{ route('podcasts.feed', $series) }}">
@endpush

@section('content')
<x-page-header :title="$series->title" :subtitle="$series->summary" :breadcrumbs="['Podcasts' => route('podcasts.index'), $series->title => null]">
    <x-slot:actions>
        <a href="{{ route('podcasts.feed', $series) }}" class="btn btn-quiet btn-sm"><i class="bi bi-rss me-1" aria-hidden="true"></i> RSS feed</a>
        @if ($manage)
            <a href="{{ route('podcasts.edit', $series) }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit series</a>
            <a href="{{ route('podcasts.episodes.create', $series) }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New episode</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-4">
    <div class="col-lg-8">
        @if ($episodes->isEmpty())
            <div class="card">
                <x-empty icon="mic" title="No episodes yet" :action="$manage ? ['url' => route('podcasts.episodes.create', $series), 'label' => 'Add the first episode'] : null" />
            </div>
        @else
            <div class="card">
                <ol class="list-group list-group-flush" reversed>
                    @foreach ($episodes as $episode)
                        <li class="list-group-item">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge text-bg-light">Episode {{ $episode->number }}</span>
                                @unless ($episode->isPublished())<span class="badge text-bg-secondary">Draft</span>@endunless
                            </div>
                            <a href="{{ route('podcasts.episodes.show', [$series, $episode]) }}" class="fw-semibold text-decoration-none">{{ $episode->title }}</a>
                            <p class="aq-text-sm aq-ink-2 mb-1">{{ $episode->summary }}</p>
                            <span class="aq-text-xs aq-muted">
                                {{ $episode->published_at?->format('d M Y') ?? 'Not published' }}
                                @if ($episode->durationLabel()) · {{ $episode->durationLabel() }}@endif
                                @if ($episode->transcript) · <i class="bi bi-file-text" aria-hidden="true"></i> transcript @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <nav aria-label="Episode pages" class="mt-3">{{ $episodes->links() }}</nav>
        @endif
    </div>
    <div class="col-lg-4">
        <x-card title="About" icon="info-circle">
            @if ($series->description)
                <x-markdown :text="$series->description" />
            @else
                <p class="aq-text-sm mb-2">{{ $series->summary }}</p>
            @endif
            <p class="aq-text-xs aq-muted mb-0">Hosted by
                @if ($series->host)<a href="{{ route('profile.show', $series->host) }}">{{ $series->host->name }}</a>@else Deleted user @endif
            </p>
            @if ($manage)
                <form method="POST" action="{{ route('podcasts.destroy', $series) }}" class="aq-border-top pt-3 mt-3"
                      data-confirm-title="Delete “{{ $series->title }}”?" data-confirm="Every episode and its audio is deleted." data-confirm-button="Delete series">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete series</button>
                </form>
            @endif
        </x-card>
    </div>
</div>
@endsection
