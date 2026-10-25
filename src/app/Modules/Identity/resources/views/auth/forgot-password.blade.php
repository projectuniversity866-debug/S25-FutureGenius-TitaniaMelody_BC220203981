@extends('layouts.auth')
@section('title', 'Forgot password')

@section('content')
<h1 class="aq-h1 mb-2">Forgot your password?</h1>
<p class="aq-text aq-muted mb-4">
    Enter your email and we will send you a link to choose a new one. The link works for 60 minutes.
</p>

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}" required autofocus autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-accent w-100">Email me a reset link</button>
</form>

<p class="text-center mt-4 mb-0 aq-text-sm aq-muted">
    Remembered it? <a href="{{ route('login') }}">Back to sign in</a>
</p>
@endsection
