@extends('layouts.app')
@section('title', 'Sign in')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-1">Welcome back</h1>
                <p class="text-muted small mb-4">Sign in to your AQDesk account.</p>

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

                    <button type="submit" class="btn btn-info w-100 fw-semibold">Sign in</button>
                </form>

                <p class="text-center small text-muted mt-4 mb-0">
                    No account yet? <a href="{{ route('register') }}">Create one</a>
                </p>
            </div>
        </div>

        {{-- Demo accounts, seeded by DatabaseSeeder. Remove before production. --}}
        <div class="card border-0 bg-body-secondary mt-3">
            <div class="card-body small">
                <p class="fw-semibold mb-2"><i class="bi bi-info-circle" aria-hidden="true"></i> Demo accounts (password: <code>password</code>)</p>
                <ul class="mb-0 ps-3">
                    <li><strong>Administrator</strong> — admin@aqdesk.test</li>
                    <li><strong>Expert</strong> — expert@aqdesk.test</li>
                    <li><strong>Member</strong> — member@aqdesk.test</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
