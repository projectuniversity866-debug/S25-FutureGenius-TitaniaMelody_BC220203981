@extends('layouts.auth')
@section('title', 'Create an account')

@section('content')
<h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 .375rem;">Join AQDesk</h1>
<p style="font-size: .875rem; color: var(--aq-muted); margin: 0 0 1.5rem;">
    Ask questions, share expertise and find opportunities.
</p>

<form method="POST" action="{{ route('register.store') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Full name</label>
        <input type="text" id="name" name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}" required autofocus autocomplete="name">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}" required autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Role choice drives role-based access control (FR1). --}}
    <fieldset class="mb-3">
        <legend class="form-label">I am joining as</legend>
        <div class="d-grid gap-2">
            @foreach ($roles as $role)
                <label class="d-flex align-items-start gap-2 p-2"
                       for="role_{{ $role->slug }}"
                       style="border: 1px solid var(--aq-border); border-radius: var(--aq-radius-sm); cursor: pointer;">
                    <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="role_slug"
                           id="role_{{ $role->slug }}" value="{{ $role->slug }}"
                           @checked(old('role_slug', 'member') === $role->slug) required>
                    <span class="min-w-0">
                        <span class="d-block fw-semibold" style="font-size: .875rem;">{{ $role->name }}</span>
                        <span class="d-block" style="font-size: .75rem; color: var(--aq-muted);">{{ $role->description }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('role_slug')<div class="mt-1" style="font-size: .75rem; color: var(--aq-red-ink);">{{ $message }}</div>@enderror
    </fieldset>

    <div class="mb-3">
        <label for="headline" class="form-label">
            Headline <span style="color: var(--aq-muted); font-weight: 400;">(optional)</span>
        </label>
        <input type="text" id="headline" name="headline"
               class="form-control @error('headline') is-invalid @enderror"
               value="{{ old('headline') }}"
               placeholder="e.g. Final year Computer Science student"
               aria-describedby="headlineHelp">
        <div id="headlineHelp" class="form-text">Shown under your name on your profile.</div>
        @error('headline')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="new-password" aria-describedby="pwHelp">
            <div id="pwHelp" class="form-text">At least 8 characters.</div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-6">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" required autocomplete="new-password">
        </div>
    </div>

    <button type="submit" class="btn btn-accent w-100">Create account</button>
</form>

<p class="text-center mt-4 mb-0" style="font-size: .8125rem; color: var(--aq-muted);">
    Already registered? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
