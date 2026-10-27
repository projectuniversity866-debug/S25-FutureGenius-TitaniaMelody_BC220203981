@extends('layouts.app')
@section('title', 'Newsletter')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="AQDesk newsletter" subtitle="New learning paths, events, opportunities and community news — by email, about twice a month." />

@if ($subscriber?->status === 'subscribed')
    <x-card>
        <p class="mb-2"><i class="bi bi-envelope-check me-1" aria-hidden="true"></i> <strong>{{ $subscriber->email }}</strong> is subscribed.</p>
        <p class="aq-text-sm aq-muted mb-3">Every newsletter ends with a one-click unsubscribe link.</p>
        <a href="{{ route('newsletter.unsubscribe', $subscriber->token) }}" class="btn btn-quiet btn-sm">Unsubscribe</a>
    </x-card>
@else
    <x-card>
        @if ($subscriber?->status === 'pending')
            <p class="aq-text-sm" role="status"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i> We sent a confirmation link to {{ $subscriber->email }}. Click it to start receiving the newsletter, or sign up again for a new link.</p>
        @endif
        <form method="POST" action="{{ route('newsletter.subscribe') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" maxlength="255" required autocomplete="email"
                       class="form-control @error('email') is-invalid @enderror" aria-describedby="email_hint @error('email') email_error @enderror">
                <div id="email_hint" class="form-text">
                    @auth Your account email is subscribed at once; any other address gets a confirmation link first.
                    @else We email you a link to confirm — nothing is sent until you click it. @endauth
                </div>
                @error('email')<div id="email_error" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" id="consent" name="consent" value="1" @checked(old('consent'))
                       @error('consent') aria-describedby="consent_error" @enderror>
                <label class="form-check-label" for="consent">I agree to receive the AQDesk newsletter. I can unsubscribe at any time with one click.</label>
                @error('consent')<div id="consent_error" class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-accent"><i class="bi bi-envelope-plus me-1" aria-hidden="true"></i> Subscribe</button>
        </form>
    </x-card>
@endif

<p class="aq-text-xs aq-muted mt-3">
    We use your address only for the newsletter and never share it. Each email contains a tiny image that tells us whether it was opened;
    blocking images in your mail app turns this off. See the <a href="{{ route('privacy') }}">privacy policy</a>.
</p>
@endsection
