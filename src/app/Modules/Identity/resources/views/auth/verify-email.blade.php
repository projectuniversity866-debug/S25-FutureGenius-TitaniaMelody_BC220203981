@extends('layouts.app')
@section('title', 'Confirm your email')
@section('subtitle', 'One last step')
@section('container', 'aq-container-narrow')

@section('content')
<div class="card module-placeholder">
    <div class="card-body p-4 text-center">
        <span class="aq-module-icon" aria-hidden="true"><i class="bi bi-envelope-check"></i></span>
        <h1 class="aq-h2">Confirm your email address</h1>
        <p class="aq-text aq-muted aq-measure mx-auto">
            We sent a link to <strong class="aq-ink">{{ auth()->user()->email }}</strong>.
            Open it to start asking, answering and applying. You can keep browsing in the meantime.
        </p>
        <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-accent">Send the link again</button>
        </form>
        <a href="{{ route('profile.edit') }}" class="btn btn-quiet ms-1">Wrong address? Change it</a>
    </div>
</div>
@endsection
