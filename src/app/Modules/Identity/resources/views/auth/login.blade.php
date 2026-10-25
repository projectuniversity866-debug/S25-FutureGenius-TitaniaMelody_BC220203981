@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
<h1 class="aq-h1 mb-2">Welcome back</h1>
<p class="aq-text aq-muted mb-4">
    Sign in to your AQDesk account.
</p>

@include('identity::auth._social', ['verb' => 'sign in'])

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
        <div class="d-flex justify-content-between align-items-baseline">
            <label for="password" class="form-label">Password</label>
            <a href="{{ route('password.request') }}" class="aq-text-sm">Forgot password?</a>
        </div>
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

<p class="text-center mt-4 mb-0 aq-text-sm aq-muted">
    No account yet? <a href="{{ route('register') }}">Create one</a>
</p>

{{-- Demo accounts, seeded by DatabaseSeeder. Remove before production. --}}
<div class="card aq-card-quiet mt-4">
    <div class="card-body">
        <p class="aq-label mb-2">Demo accounts — password: <code>password</code></p>
        <div class="d-grid gap-1 aq-text-sm">
            <div class="d-flex justify-content-between gap-2">
                <span class="aq-muted">Administrator</span>
                <code>admin@aqdesk.test</code>
            </div>
            <div class="d-flex justify-content-between gap-2">
                <span class="aq-muted">Expert</span>
                <code>expert@aqdesk.test</code>
            </div>
            <div class="d-flex justify-content-between gap-2">
                <span class="aq-muted">Member</span>
                <code>member@aqdesk.test</code>
            </div>
        </div>
    </div>
</div>
@endsection
