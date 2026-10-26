@extends('layouts.app')
@section('title', $episode ? 'Edit episode' : 'New episode')
@section('subtitle', $series->title)

@section('content')
@php use App\Modules\EventsMedia\Models\PodcastEpisode; @endphp
<x-page-header :title="$episode ? 'Edit episode '.$episode->number : 'New episode'" :subtitle="$series->title"
               :breadcrumbs="['Podcasts' => route('podcasts.index'), $series->title => route('podcasts.show', $series), ($episode ? 'Edit' : 'New episode') => null]" />

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ $episode ? route('podcasts.episodes.update', [$series, $episode]) : route('podcasts.episodes.store', $series) }}"
              enctype="multipart/form-data" novalidate>
            @csrf
            @if ($episode) @method('PUT') @endif

            <x-card title="Episode" icon="mic" class="mb-4">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $episode?->title) }}" maxlength="150" required
                           class="form-control @error('title') is-invalid @enderror" @error('title') aria-describedby="title_error" @enderror>
                    @error('title')<div id="title_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="summary" class="form-label">Summary</label>
                    <input type="text" id="summary" name="summary" value="{{ old('summary', $episode?->summary) }}" maxlength="255" required
                           class="form-control @error('summary') is-invalid @enderror" @error('summary') aria-describedby="summary_error" @enderror>
                    @error('summary')<div id="summary_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="show_notes" class="form-label">Show notes <span class="aq-muted fw-normal">(optional, Markdown)</span></label>
                    <textarea id="show_notes" name="show_notes" rows="5" maxlength="10000" aria-describedby="show_notes_hint"
                              class="form-control @error('show_notes') is-invalid @enderror">{{ old('show_notes', $episode?->show_notes) }}</textarea>
                    <div id="show_notes_hint" class="form-text">Guests, links and resources mentioned in the episode.</div>
                    @error('show_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </x-card>

            <x-card title="Audio" icon="soundwave" class="mb-4">
                @error('audio')<div class="alert alert-danger py-2 aq-text-sm" role="alert">{{ $message }}</div>@enderror
                @if ($episode?->hasAudio())
                    <p class="aq-text-sm">
                        Current audio: {{ $episode->audio_path ? 'uploaded file ('.number_format($episode->audio_size / 1048576, 1).' MB)' : $episode->audio_url }}
                    </p>
                @endif
                <div class="mb-3">
                    <label for="audio" class="form-label">Upload audio <span class="aq-muted fw-normal">(MP3, M4A, OGG, WAV or WebM; up to 50 MB)</span></label>
                    <input type="file" id="audio" name="audio" accept="audio/*" class="form-control @error('audio') is-invalid @enderror" aria-describedby="audio_hint">
                    <div id="audio_hint" class="form-text">The server's own upload limit may be lower; for long episodes, use a link instead.</div>
                </div>
                <div class="mb-3">
                    <label for="audio_url" class="form-label">…or link to audio hosted elsewhere</label>
                    <input type="url" id="audio_url" name="audio_url" value="{{ old('audio_url', $episode?->audio_url) }}" maxlength="500" inputmode="url"
                           class="form-control @error('audio_url') is-invalid @enderror" placeholder="https://archive.org/download/…/episode.mp3"
                           aria-describedby="audio_url_hint @error('audio_url') audio_url_error @enderror">
                    <div id="audio_url_hint" class="form-text">A direct link to the audio file (it must play in the browser). An uploaded file replaces the link.</div>
                    @error('audio_url')<div id="audio_url_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="duration_minutes" class="form-label">Length in minutes <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="600"
                               value="{{ old('duration_minutes', $episode?->duration_seconds ? (int) round($episode->duration_seconds / 60) : null) }}"
                               class="form-control @error('duration_minutes') is-invalid @enderror">
                        @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if ($episode?->hasAudio())
                        <div class="col-sm-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_audio" name="remove_audio" value="1">
                                <label class="form-check-label" for="remove_audio">Remove the current audio</label>
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="Transcript" icon="file-text" class="mb-4">
                <label for="transcript" class="form-label">Full transcript</label>
                <textarea id="transcript" name="transcript" rows="14" class="form-control @error('transcript') is-invalid @enderror"
                          aria-describedby="transcript_hint">{{ old('transcript', $episode?->transcript) }}</textarea>
                <div id="transcript_hint" class="form-text">
                    Required before publishing, so people who are deaf, hard of hearing or cannot play audio get the whole episode (WCAG 1.2.1).
                    Start a line with a speaker's name and a colon, e.g. “Sara: Welcome back.”
                </div>
                @error('transcript')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </x-card>

            <button type="submit" class="btn btn-accent">{{ $episode ? 'Save episode' : 'Save draft' }}</button>
            <a href="{{ $episode ? route('podcasts.episodes.show', [$series, $episode]) : route('podcasts.show', $series) }}" class="btn btn-quiet">Cancel</a>
        </form>
    </div>
</div>
@endsection
