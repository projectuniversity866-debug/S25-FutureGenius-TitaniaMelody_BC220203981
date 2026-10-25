@extends('layouts.app')
@section('title', 'Account settings')
@section('subtitle', 'Security, notifications and privacy')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Account settings" subtitle="Control where you are signed in, which emails you receive and who can see your profile.">
    <x-slot:actions>
        <a href="{{ route('profile.edit') }}" class="btn btn-quiet btn-sm">
            <i class="bi bi-person-gear me-1" aria-hidden="true"></i> Edit profile &amp; password
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('settings.update') }}">
    @csrf
    @method('PUT')

    <x-card title="Email notifications" icon="envelope" class="mb-4">
        <p class="aq-text-sm aq-muted">You always see notifications under the bell. Choose which ones are also emailed to {{ $user->email }}.</p>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="email_answers" name="email_answers" value="1"
                   @checked($user->wantsEmailFor('answers'))>
            <label class="form-check-label" for="email_answers">Answers to my questions, and when my answer is accepted</label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="email_applications" name="email_applications" value="1"
                   @checked($user->wantsEmailFor('applications'))>
            <label class="form-check-label" for="email_applications">New applicants and changes to my applications</label>
        </div>
    </x-card>

    <x-card title="Privacy" icon="eye" class="mb-4">
        <fieldset>
            <legend class="aq-text-sm aq-muted mb-2">Who can see my profile page</legend>
            @foreach (['public' => ['Everyone', 'Anyone on the web, including people who are not signed in.'],
                       'members' => ['Signed-in members only', 'Hidden from visitors and search engines.']] as $value => [$label, $hint])
                <label class="d-flex align-items-start gap-2 p-2 mb-2 aq-choice" for="visibility_{{ $value }}">
                    <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="profile_visibility"
                           id="visibility_{{ $value }}" value="{{ $value }}"
                           @checked($user->setting('profile_visibility') === $value)>
                    <span>
                        <span class="d-block fw-semibold aq-text">{{ $label }}</span>
                        <span class="d-block aq-text-xs aq-muted">{{ $hint }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>
    </x-card>

    <div class="d-flex justify-content-end mb-4">
        <button type="submit" class="btn btn-accent">Save settings</button>
    </div>
</form>

<x-card title="Where you're signed in" icon="laptop" flush>
    @if (! $available)
        <div class="card-body aq-text-sm aq-muted">The device list needs the database session driver.</div>
    @else
        <ul class="list-group list-group-flush">
            @foreach ($sessions as $session)
                <li class="list-group-item d-flex align-items-center gap-3">
                    <i class="bi bi-{{ str_contains($session->device, 'Android') || str_contains($session->device, 'iOS') ? 'phone' : 'laptop' }} aq-muted" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <span class="fw-semibold">{{ $session->device }}</span>
                        @if ($session->current)<span class="badge text-bg-success ms-1">This device</span>@endif
                        <span class="d-block aq-text-xs aq-muted">
                            {{ $session->ip ?? 'Unknown address' }} · active {{ $session->last_active->diffForHumans() }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
    <div class="card-body aq-border-top">
        <form method="POST" action="{{ route('settings.sessions.destroy') }}" class="row g-2 align-items-end">
            @csrf
            @method('DELETE')
            <div class="col-sm">
                <label for="password" class="form-label">Confirm with your password</label>
                <input type="password" id="password" name="password" autocomplete="current-password"
                       class="form-control form-control-sm @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Sign out everywhere else</button>
            </div>
        </form>
    </div>
</x-card>
@endsection
