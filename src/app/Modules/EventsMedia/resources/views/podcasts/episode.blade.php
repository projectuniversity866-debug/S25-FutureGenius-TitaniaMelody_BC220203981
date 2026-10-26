@extends('layouts.app')
@section('title', $episode->title.' · '.$series->title)
@section('subtitle', 'Podcast')

@section('content')
@php $manage = auth()->user()?->can('update', $series); $missing = $episode->missingForPublishing(); @endphp
<x-page-header :title="$episode->title" :subtitle="'Episode '.$episode->number.' of '.$series->title"
               :breadcrumbs="['Podcasts' => route('podcasts.index'), $series->title => route('podcasts.show', $series), 'Episode '.$episode->number => null]">
    @if ($manage)
        <x-slot:actions>
            <a href="{{ route('podcasts.episodes.edit', [$series, $episode]) }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a>
            <form method="POST" action="{{ route('podcasts.episodes.publish', [$series, $episode]) }}">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-sm {{ $episode->isPublished() ? 'btn-quiet' : 'btn-accent' }}" @disabled(! $episode->isPublished() && $missing)
                        @if (! $episode->isPublished() && $missing) aria-describedby="publish_missing" @endif>
                    <i class="bi bi-{{ $episode->isPublished() ? 'eye-slash' : 'megaphone' }} me-1" aria-hidden="true"></i>{{ $episode->isPublished() ? 'Unpublish' : 'Publish' }}
                </button>
            </form>
            <form method="POST" action="{{ route('podcasts.episodes.destroy', [$series, $episode]) }}"
                  data-confirm-title="Delete episode {{ $episode->number }}?" data-confirm="The episode, its audio and transcript are deleted." data-confirm-button="Delete episode">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
            </form>
        </x-slot:actions>
    @endif
</x-page-header>

@unless ($episode->isPublished())
    <div class="alert alert-warning py-2 aq-text-sm" role="status" id="publish_missing">
        <i class="bi bi-eye me-1" aria-hidden="true"></i> Draft — only you can see it.
        @if ($missing) To publish, add {{ implode(' and ', $missing) }}.@endif
    </div>
@endunless
@error('publish')<div class="alert alert-danger py-2 aq-text-sm" role="alert">{{ $message }}</div>@enderror

<div class="row g-4">
    <div class="col-lg-8">
        <x-card class="mb-4">
            <p class="aq-text-sm aq-ink-2">{{ $episode->summary }}</p>
            <p class="aq-text-xs aq-muted">
                {{ $episode->published_at?->format('d M Y') ?? 'Not published' }}
                @if ($episode->durationLabel()) · {{ $episode->durationLabel() }}@endif
                · hosted by {{ $series->host?->name ?? 'Deleted user' }}
            </p>
            @if ($episode->hasAudio())
                <audio controls preload="metadata" class="aq-audio" src="{{ $episode->audioSource() }}" aria-describedby="audio_help">
                    <a href="{{ $episode->audioSource() }}">Download the audio</a>
                </audio>
                <p id="audio_help" class="aq-text-xs aq-muted mt-2 mb-0">
                    Can't play it? <a href="{{ $episode->audioSource() }}">Open the audio file</a> or read the <a href="#transcript">transcript</a> below.
                </p>
            @else
                <p class="aq-text-sm aq-muted mb-0"><i class="bi bi-soundwave me-1" aria-hidden="true"></i> No audio yet.</p>
            @endif
        </x-card>

        <x-card title="Transcript" icon="file-text" id="transcript">
            @if ($episode->transcript)
                <div class="aq-transcript">
                    @foreach (preg_split('/\R{1,}/', trim($episode->transcript)) as $line)
                        @continue(trim($line) === '')
                        @if (preg_match('/^([^:\n]{1,40}):\s+(.+)$/u', $line, $m))
                            <p><strong>{{ $m[1] }}:</strong> {{ $m[2] }}</p>
                        @else
                            <p>{{ $line }}</p>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="aq-text-sm aq-muted mb-0">No transcript yet.</p>
            @endif
        </x-card>
    </div>

    <div class="col-lg-4">
        @if ($episode->show_notes)
            <x-card title="Show notes" icon="journal-text" class="mb-4">
                <x-markdown :text="$episode->show_notes" />
            </x-card>
        @endif
        <nav aria-label="More episodes" class="d-grid gap-2">
            @if ($next)
                <a href="{{ route('podcasts.episodes.show', [$series, $next]) }}" class="btn btn-quiet text-start">
                    <i class="bi bi-arrow-right me-1" aria-hidden="true"></i> Next: {{ $next->title }}
                </a>
            @endif
            @if ($previous)
                <a href="{{ route('podcasts.episodes.show', [$series, $previous]) }}" class="btn btn-quiet text-start">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Previous: {{ $previous->title }}
                </a>
            @endif
            <a href="{{ route('podcasts.show', $series) }}" class="btn btn-quiet text-start"><i class="bi bi-list-ul me-1" aria-hidden="true"></i> All episodes</a>
        </nav>
    </div>
</div>
@endsection
