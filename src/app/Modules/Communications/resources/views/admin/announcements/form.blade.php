@extends('layouts.app')
@section('title', $announcement ? 'Edit announcement' : 'New announcement')
@section('subtitle', 'Administration')

@section('content')
@php
    use App\Modules\Communications\Models\Announcement;
    use App\Modules\Communications\Models\Campaign;
    $a = $announcement;
    $tz = old('timezone', $a?->timezone ?? 'Asia/Karachi');
    $local = fn ($t) => $t?->copy()->setTimezone($a->timezone)->format('Y-m-d\TH:i');
@endphp
<x-page-header :title="$a ? 'Edit announcement' : 'New announcement'"
               :breadcrumbs="['Announcements' => route('admin.announcements.index'), ($a ? $a->title : 'New') => null]" />

<div class="row">
    <div class="col-lg-8">
        <x-card>
            <form method="POST" action="{{ $a ? route('admin.announcements.update', $a) : route('admin.announcements.store') }}" novalidate>
                @csrf
                @if ($a) @method('PUT') @endif
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $a?->title) }}" maxlength="120" required
                           class="form-control @error('title') is-invalid @enderror" @error('title') aria-describedby="title_error" @enderror>
                    @error('title')<div id="title_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">Message</label>
                    <textarea id="body" name="body" rows="3" maxlength="500" required aria-describedby="body_hint @error('body') body_error @enderror"
                              class="form-control @error('body') is-invalid @enderror">{{ old('body', $a?->body) }}</textarea>
                    <div id="body_hint" class="form-text">One or two sentences, up to 500 characters.</div>
                    @error('body')<div id="body_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-7">
                        <label for="link_url" class="form-label">Link <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="url" id="link_url" name="link_url" value="{{ old('link_url', $a?->link_url) }}" maxlength="500" placeholder="https://"
                               class="form-control @error('link_url') is-invalid @enderror" @error('link_url') aria-describedby="link_url_error" @enderror>
                        @error('link_url')<div id="link_url_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-5">
                        <label for="link_label" class="form-label">Link text</label>
                        <input type="text" id="link_label" name="link_label" value="{{ old('link_label', $a?->link_label) }}" maxlength="60" placeholder="e.g. Register now"
                               class="form-control @error('link_label') is-invalid @enderror" @error('link_label') aria-describedby="link_label_error" @enderror>
                        @error('link_label')<div id="link_label_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <fieldset class="col-sm-6">
                        <legend class="form-label">Kind</legend>
                        @foreach (Announcement::TONES as $value => [$label, $icon])
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tone" id="tone_{{ $value }}" value="{{ $value }}" @checked(old('tone', $a?->tone ?? 'info') === $value)>
                                <label class="form-check-label" for="tone_{{ $value }}"><i class="bi bi-{{ $icon }} me-1" aria-hidden="true"></i>{{ $label }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    <div class="col-sm-6">
                        <label for="audience" class="form-label">Who sees it</label>
                        <select id="audience" name="audience" class="form-select">
                            @foreach (Announcement::AUDIENCES as $value => $label)
                                <option value="{{ $value }}" @selected(old('audience', $a?->audience ?? 'everyone') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-check mt-3">
                            <input type="hidden" name="is_dismissible" value="0">
                            <input class="form-check-input" type="checkbox" id="is_dismissible" name="is_dismissible" value="1" @checked(old('is_dismissible', $a?->is_dismissible ?? true))>
                            <label class="form-check-label" for="is_dismissible">People can close the banner</label>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label for="starts_at" class="form-label">Show from</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $a ? $local($a->starts_at) : now($tz)->format('Y-m-d\TH:i')) }}" required
                               class="form-control @error('starts_at') is-invalid @enderror" @error('starts_at') aria-describedby="starts_at_error" @enderror>
                        @error('starts_at')<div id="starts_at_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="ends_at" class="form-label">Until <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $a ? $local($a->ends_at) : null) }}"
                               class="form-control @error('ends_at') is-invalid @enderror" aria-describedby="ends_hint @error('ends_at') ends_at_error @enderror">
                        <div id="ends_hint" class="form-text">Empty: until you delete it.</div>
                        @error('ends_at')<div id="ends_at_error" class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4">
                        <label for="timezone" class="form-label">Time zone</label>
                        <select id="timezone" name="timezone" class="form-select">
                            @foreach (Campaign::TIMEZONES as $value => $label)
                                <option value="{{ $value }}" @selected($tz === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-accent">{{ $a ? 'Save announcement' : 'Post announcement' }}</button>
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-quiet">Cancel</a>
            </form>
        </x-card>
    </div>
</div>
@endsection
