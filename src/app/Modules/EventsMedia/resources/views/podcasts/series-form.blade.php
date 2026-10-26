@extends('layouts.app')
@section('title', $series ? 'Edit series' : 'New podcast series')
@section('subtitle', 'Podcasts')

@section('content')
<x-page-header :title="$series ? 'Edit series' : 'New podcast series'"
               :breadcrumbs="$series ? ['Podcasts' => route('podcasts.index'), $series->title => route('podcasts.show', $series), 'Edit' => null] : ['Podcasts' => route('podcasts.index'), 'New series' => null]" />

<div class="row">
    <div class="col-lg-8">
        <x-card>
            <form method="POST" action="{{ $series ? route('podcasts.update', $series) : route('podcasts.store') }}" novalidate>
                @csrf
                @if ($series) @method('PUT') @endif
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $series?->title) }}" maxlength="150" required
                           class="form-control @error('title') is-invalid @enderror" @error('title') aria-describedby="title_error" @enderror>
                    @error('title')<div id="title_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="summary" class="form-label">Summary</label>
                    <input type="text" id="summary" name="summary" value="{{ old('summary', $series?->summary) }}" maxlength="255" required
                           class="form-control @error('summary') is-invalid @enderror" aria-describedby="summary_hint @error('summary') summary_error @enderror">
                    <div id="summary_hint" class="form-text">One sentence: what the series is about and who it is for.</div>
                    @error('summary')<div id="summary_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">About <span class="aq-muted fw-normal">(optional, Markdown)</span></label>
                    <textarea id="description" name="description" rows="6" maxlength="5000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $series?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-accent">{{ $series ? 'Save series' : 'Create series' }}</button>
                <a href="{{ $series ? route('podcasts.show', $series) : route('podcasts.index') }}" class="btn btn-quiet">Cancel</a>
            </form>
        </x-card>
    </div>
</div>
@endsection
