@extends('layouts.app')
@section('title', 'My consultant profile')
@section('subtitle', 'Consulting')

@section('content')
@php use App\Modules\Consulting\Models\ConsultantProfile; @endphp
<x-page-header title="My consultant profile" subtitle="What members see before they book a session with you."
               :breadcrumbs="['Consultants' => route('consultants.index'), 'My profile' => null]">
    @if ($profile)
        <x-slot:actions>
            <a href="{{ route('consultants.show', $profile) }}" class="btn btn-quiet btn-sm"><i class="bi bi-eye me-1" aria-hidden="true"></i> View public page</a>
            <a href="{{ route('consulting.availability') }}" class="btn btn-soft btn-sm"><i class="bi bi-calendar-week me-1" aria-hidden="true"></i> Availability</a>
        </x-slot:actions>
    @endif
</x-page-header>

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('consulting.profile.update') }}" novalidate>
            @csrf
            @method('PUT')
            <x-card title="About you" icon="person" class="mb-4">
                <div class="mb-3">
                    <label for="headline" class="form-label">Headline</label>
                    <input type="text" id="headline" name="headline" value="{{ old('headline', $profile?->headline) }}" maxlength="150" required
                           class="form-control @error('headline') is-invalid @enderror" placeholder="e.g. Laravel developer helping final-year students ship their projects"
                           @error('headline') aria-describedby="headline_error" @enderror>
                    @error('headline')<div id="headline_error" class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="expertise" class="form-label">Topics <span class="aq-muted fw-normal">(up to 8, separated by commas)</span></label>
                    <input type="text" id="expertise" name="expertise" value="{{ old('expertise', implode(', ', $profile?->expertise ?? [])) }}"
                           class="form-control @error('expertise') is-invalid @enderror @error('expertise.*') is-invalid @enderror" placeholder="Laravel, Testing, Project reviews">
                    @error('expertise')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('expertise.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="bio" class="form-label">About your sessions <span class="aq-muted fw-normal">(optional, Markdown)</span></label>
                    <textarea id="bio" name="bio" rows="6" maxlength="5000" class="form-control @error('bio') is-invalid @enderror"
                              aria-describedby="bio_hint">{{ old('bio', $profile?->bio) }}</textarea>
                    <div id="bio_hint" class="form-text">Your background, how a session works, what to prepare.</div>
                    @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </x-card>

            <x-card title="Sessions" icon="clock" class="mb-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="session_minutes" class="form-label">Session length</label>
                        <select id="session_minutes" name="session_minutes" class="form-select @error('session_minutes') is-invalid @enderror">
                            @foreach (ConsultantProfile::SESSION_LENGTHS as $m)
                                <option value="{{ $m }}" @selected((int) old('session_minutes', $profile?->session_minutes ?? 60) === $m)>{{ $m }} minutes</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="price_pkr" class="form-label">Price per session in PKR <span class="aq-muted fw-normal">(empty = free)</span></label>
                        <input type="number" id="price_pkr" name="price_pkr" min="0" max="1000000" value="{{ old('price_pkr', $profile?->price_pkr) }}"
                               class="form-control @error('price_pkr') is-invalid @enderror">
                        @error('price_pkr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="timezone" class="form-label">Your time zone</label>
                        <select id="timezone" name="timezone" class="form-select" aria-describedby="timezone_hint">
                            @foreach (ConsultantProfile::TIMEZONES as $value => $label)
                                <option value="{{ $value }}" @selected(old('timezone', $profile?->timezone ?? 'Asia/Karachi') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div id="timezone_hint" class="form-text">Your weekly hours are in this zone.</div>
                    </div>
                    <div class="col-sm-3">
                        <label for="notice_hours" class="form-label">Notice (hours)</label>
                        <input type="number" id="notice_hours" name="notice_hours" min="0" max="336" value="{{ old('notice_hours', $profile?->notice_hours ?? 24) }}"
                               class="form-control @error('notice_hours') is-invalid @enderror" aria-describedby="notice_hint">
                        <div id="notice_hint" class="form-text">Earliest booking.</div>
                        @error('notice_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-3">
                        <label for="window_days" class="form-label">Book ahead (days)</label>
                        <input type="number" id="window_days" name="window_days" min="1" max="90" value="{{ old('window_days', $profile?->window_days ?? 30) }}"
                               class="form-control @error('window_days') is-invalid @enderror" aria-describedby="window_hint">
                        <div id="window_hint" class="form-text">Latest booking.</div>
                        @error('window_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_accepting" name="is_accepting" value="1"
                           @checked(old('is_accepting', $profile?->is_accepting ?? true)) aria-describedby="accepting_hint">
                    <label class="form-check-label" for="is_accepting">Taking bookings</label>
                    <div id="accepting_hint" class="form-text">Switch off to hide your profile from the directory, e.g. during exams.</div>
                </div>
            </x-card>

            <button type="submit" class="btn btn-accent">{{ $profile ? 'Save profile' : 'Create profile' }}</button>
        </form>
    </div>
</div>
@endsection
