@extends('layouts.app')
@section('title', 'Book a session')
@section('subtitle', 'Consulting')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Book a session" :subtitle="'With '.$profile->user->name"
               :breadcrumbs="['Consultants' => route('consultants.index'), $profile->user->name => route('consultants.show', $profile), 'Book' => null]" />

<div class="card">
    <div class="card-body p-4">
        <ul class="aq-event-facts mb-4">
            <li><i class="bi bi-calendar-event" aria-hidden="true"></i>
                <div><strong class="d-block">When</strong>
                    <time datetime="{{ $start->toIso8601String() }}">{{ $start->format('l j F Y, H:i') }} – {{ $start->addMinutes($profile->session_minutes)->format('H:i T') }}</time></div></li>
            <li><i class="bi bi-camera-video" aria-hidden="true"></i>
                <div><strong class="d-block">Online</strong>A private Jitsi Meet room is made for the session; no account needed.</div></li>
            <li><i class="bi bi-cash-coin" aria-hidden="true"></i>
                <div><strong class="d-block">Price</strong>{{ $profile->priceLabel() }}@if ($profile->price_pkr) — arranged directly with the consultant.@endif</div></li>
        </ul>

        @error('start')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('consultants.book.store', $profile) }}" novalidate>
            @csrf
            <input type="hidden" name="start" value="{{ $start->utc()->toIso8601ZuluString() }}">
            <div class="mb-3">
                <label for="topic" class="form-label">What is the session about?</label>
                <input type="text" id="topic" name="topic" value="{{ old('topic') }}" maxlength="150" required placeholder="e.g. Review of my final-year project's database design"
                       class="form-control @error('topic') is-invalid @enderror" @error('topic') aria-describedby="topic_error" @enderror>
                @error('topic')<div id="topic_error" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Anything to prepare? <span class="aq-muted fw-normal">(optional)</span></label>
                <textarea id="message" name="message" rows="4" maxlength="2000" aria-describedby="message_hint"
                          class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                <div id="message_hint" class="form-text">Links, questions, where you are stuck. Only {{ $profile->user->name }} sees this.</div>
                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-accent">Book this time</button>
            <a href="{{ route('consultants.show', $profile) }}#times" class="btn btn-quiet">Choose another time</a>
        </form>
    </div>
</div>
@endsection
