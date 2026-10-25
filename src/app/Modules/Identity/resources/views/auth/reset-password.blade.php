@extends('layouts.auth')
@section('title', 'Choose a new password')

@section('content')
<h1 class="aq-h1 mb-2">Choose a new password</h1>
<p class="aq-text aq-muted mb-4">Use at least 8 characters.</p>

<form method="POST" action="{{ route('password.store') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $email) }}" required autocomplete="email">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">New password</label>
        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
               required autofocus autocomplete="new-password" aria-describedby="passwordHelp">
        <div id="passwordHelp" class="form-text">At least 8 characters.</div>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
               required autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-accent w-100">Save new password</button>
</form>
@endsection
