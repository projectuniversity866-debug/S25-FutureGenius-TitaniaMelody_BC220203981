@extends('layouts.app')
@section('title', 'Create an account')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-1">Join AQDesk</h1>
                <p class="text-muted small mb-4">Ask questions, share expertise and find opportunities.</p>

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

                    <fieldset class="mb-3">
                        <legend class="form-label fs-6">I am joining as</legend>
                        @foreach ($roles as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role_slug"
                                       id="role_{{ $role->slug }}" value="{{ $role->slug }}"
                                       @checked(old('role_slug', 'member') === $role->slug) required>
                                <label class="form-check-label" for="role_{{ $role->slug }}">
                                    <strong>{{ $role->name }}</strong>
                                    <span class="d-block small text-muted">{{ $role->description }}</span>
                                </label>
                            </div>
                        @endforeach
                        @error('role_slug')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </fieldset>

                    <div class="mb-3">
                        <label for="headline" class="form-label">
                            Headline <span class="text-muted fw-normal">(optional)</span>
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

                    <button type="submit" class="btn btn-info w-100 fw-semibold">Create account</button>
                </form>

                <p class="text-center small text-muted mt-4 mb-0">
                    Already registered? <a href="{{ route('login') }}">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
