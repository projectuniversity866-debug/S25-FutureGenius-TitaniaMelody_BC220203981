@extends('layouts.app')
@section('title', $campaign ? 'Edit campaign' : 'New campaign')
@section('subtitle', 'Administration')

@section('content')
<x-page-header :title="$campaign ? 'Edit campaign' : 'New campaign'"
               :breadcrumbs="['Newsletter' => route('admin.newsletter.index'), ($campaign ? $campaign->subject : 'New campaign') => $campaign ? route('admin.newsletter.show', $campaign) : null]" />

<div class="row">
    <div class="col-lg-9">
        <x-card>
            <form method="POST" action="{{ $campaign ? route('admin.newsletter.update', $campaign) : route('admin.newsletter.store') }}" novalidate>
                @csrf
                @if ($campaign) @method('PUT') @endif
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject', $campaign?->subject) }}" maxlength="150" required
                           class="form-control @error('subject') is-invalid @enderror" aria-describedby="subject_hint @error('subject') subject_error @enderror">
                    <div id="subject_hint" class="form-text">What subscribers see in their inbox, e.g. “November on AQDesk: new learning paths and a hackathon”.</div>
                    @error('subject')<div id="subject_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">Message</label>
                    <x-vue name="MarkdownEditor" :props="['for' => 'body', 'previewUrl' => route('markdown.preview')]" />
                    <textarea id="body" name="body" rows="16" required maxlength="50000" aria-describedby="body_hint @error('body') body_error @enderror"
                              class="form-control @error('body') is-invalid @enderror">{{ old('body', $campaign?->body) }}</textarea>
                    <div id="body_hint" class="form-text">Markdown: headings, lists and links work. The unsubscribe link is added automatically.</div>
                    @error('body')<div id="body_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-accent">Save draft</button>
                <a href="{{ $campaign ? route('admin.newsletter.show', $campaign) : route('admin.newsletter.index') }}" class="btn btn-quiet">Cancel</a>
            </form>
        </x-card>
    </div>
</div>
@endsection
