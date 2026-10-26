@extends('layouts.app')
@section('title', 'Expert verification')
@section('subtitle', 'Knowledge sharing')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Expert verification"
               subtitle="Verified experts get a badge next to their name, and can write articles and guides for the Knowledge Hub." />

@if ($latest)
    <x-card title="Your latest request" icon="patch-check" class="mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <x-status :value="$latest->status === 'approved' ? 'accepted' : ($latest->status === 'rejected' ? 'rejected' : 'pending')" />
            <span class="aq-text-sm aq-muted">{{ $latest->field }} · sent {{ $latest->created_at->format('d M Y') }}</span>
        </div>
        @if ($latest->status === 'pending')
            <p class="aq-text-sm mb-0">An administrator is reviewing your request. You will get a notification with the decision.</p>
        @elseif ($latest->status === 'approved')
            <p class="aq-text-sm mb-2">You are a verified expert. <x-verified-badge :user="auth()->user()" label /></p>
            <a href="{{ route('articles.create') }}" class="btn btn-sm btn-accent">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Write for the Knowledge Hub
            </a>
        @else
            <p class="aq-text-sm mb-0">Not approved{{ $latest->reviewed_at ? ' on '.$latest->reviewed_at->format('d M Y') : '' }}. You can apply again below.</p>
        @endif
        @if ($latest->review_note)
            <blockquote class="aq-timeline-message mt-2 mb-0">{{ $latest->review_note }}</blockquote>
        @endif
    </x-card>
@endif

@if (! $latest || $latest->status === 'rejected')
    <x-card title="{{ $latest ? 'Apply again' : 'Apply' }}" icon="send">
        <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="mb-3">
                <label for="field" class="form-label">Area of expertise</label>
                <input type="text" id="field" name="field" class="form-control @error('field') is-invalid @enderror"
                       value="{{ old('field') }}" required maxlength="120" placeholder="e.g. Machine learning in education">
                @error('field')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="credentials" class="form-label">Qualifications and experience</label>
                <textarea id="credentials" name="credentials" rows="6" maxlength="3000" required
                          class="form-control @error('credentials') is-invalid @enderror"
                          aria-describedby="credentials_hint">{{ old('credentials') }}</textarea>
                <div id="credentials_hint" class="form-text">Degrees, roles, years of experience, notable work — at least 30 characters.</div>
                @error('credentials')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="evidence_url" class="form-label">Link to evidence <span class="aq-muted fw-normal">(optional)</span></label>
                <input type="url" id="evidence_url" name="evidence_url" class="form-control @error('evidence_url') is-invalid @enderror"
                       value="{{ old('evidence_url') }}" placeholder="https://www.linkedin.com/in/…">
                @error('evidence_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="document" class="form-label">Supporting document <span class="aq-muted fw-normal">(optional)</span></label>
                <input type="file" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png"
                       class="form-control @error('document') is-invalid @enderror" aria-describedby="document_hint">
                <div id="document_hint" class="form-text">PDF, JPG or PNG up to 5 MB, e.g. a degree certificate. Stored privately; only reviewers can open it.</div>
                @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-accent">Send for review</button>
        </form>
    </x-card>
@endif
@endsection
