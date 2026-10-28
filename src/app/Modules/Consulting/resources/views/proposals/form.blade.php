@extends('layouts.app')
@section('title', $proposal ? 'Edit proposal' : 'New proposal')
@section('subtitle', 'Consulting')

@section('content')
<x-page-header :title="$proposal ? 'Edit proposal' : 'Proposal for '.$client->name"
               :breadcrumbs="['My clients' => route('consulting.clients.index'), $client->name => route('consulting.clients.show', $client), ($proposal ? 'Edit proposal' : 'New proposal') => null]" />

<div class="row">
    <div class="col-lg-8">
        <x-card>
            <form method="POST" action="{{ $proposal ? route('consulting.proposals.update', $proposal) : route('consulting.proposals.store', $client) }}" novalidate>
                @csrf
                @if ($proposal) @method('PUT') @endif
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $proposal?->title) }}" maxlength="150" required
                           class="form-control @error('title') is-invalid @enderror" aria-describedby="title_hint @error('title') title_error @enderror">
                    <div id="title_hint" class="form-text">For example “Four review sessions for your final-year project”.</div>
                    @error('title')<div id="title_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">What you will do</label>
                    <x-vue name="MarkdownEditor" :props="['for' => 'body', 'previewUrl' => route('markdown.preview')]" />
                    <textarea id="body" name="body" rows="12" required maxlength="20000" aria-describedby="body_hint @error('body') body_error @enderror"
                              class="form-control @error('body') is-invalid @enderror">{{ old('body', $proposal?->body) }}</textarea>
                    <div id="body_hint" class="form-text">Scope, deliverables, number of sessions and timeline. Markdown works.</div>
                    @error('body')<div id="body_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="amount_pkr" class="form-label">Price in PKR <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="number" id="amount_pkr" name="amount_pkr" value="{{ old('amount_pkr', $proposal?->amount_pkr) }}" min="0" max="10000000" step="1"
                               class="form-control @error('amount_pkr') is-invalid @enderror" aria-describedby="amount_hint @error('amount_pkr') amount_error @enderror">
                        <div id="amount_hint" class="form-text">Leave empty for free work.</div>
                        @error('amount_pkr')<div id="amount_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="valid_until" class="form-label">Valid until <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $proposal?->valid_until?->toDateString()) }}"
                               class="form-control @error('valid_until') is-invalid @enderror" aria-describedby="valid_hint @error('valid_until') valid_error @enderror">
                        <div id="valid_hint" class="form-text">After this day it can no longer be accepted.</div>
                        @error('valid_until')<div id="valid_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-accent">Save draft</button>
                <a href="{{ $proposal ? route('consulting.proposals.show', $proposal) : route('consulting.clients.show', $client) }}" class="btn btn-quiet">Cancel</a>
            </form>
        </x-card>
    </div>
</div>
@endsection
