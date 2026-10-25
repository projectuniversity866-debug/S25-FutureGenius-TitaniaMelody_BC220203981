@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
<h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 .375rem;">Welcome back</h1>
<p style="font-size: .875rem; color: var(--aq-muted); margin: 0 0 1.5rem;">
    Sign in to your AQDesk account.
</p>

<form method="POST" action="{{ route('login.store') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}" required autofocus autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               required autocomplete="current-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
        <label class="form-check-label" for="remember">Keep me signed in</label>
    </div>

    <button type="submit" class="btn btn-accent w-100">Sign in</button>
</form>

<p class="text-center mt-4 mb-0" style="font-size: .8125rem; color: var(--aq-muted);">
    No account yet? <a href="{{ route('register') }}">Create one</a>
</p>

{{-- Demo accounts, seeded by DatabaseSeeder. Remove before production. --}}
<div class="card aq-card-quiet mt-4">
    <div class="card-body">
        <p class="aq-label mb-2">Demo accounts — password: <code>password</code></p>
        <div class="d-grid gap-1" style="font-size: .8125rem;">
            <div class="d-flex justify-content-between gap-2">
                <span style="color: var(--aq-muted);">Administrator</span>
                <code>admin@aqdesk.test</code>
            </div>
            <div class="d-flex justify-content-between gap-2">
                <span style="color: var(--aq-muted);">Expert</span>
                <code>expert@aqdesk.test</code>
            </div>
            <div class="d-flex justify-content-between gap-2">
                <span style="color: var(--aq-muted);">Member</span>
                <code>member@aqdesk.test</code>
            </div>
        </div>
    </div>
</div>
@endsection
