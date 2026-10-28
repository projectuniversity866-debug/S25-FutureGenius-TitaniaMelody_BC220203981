@extends('layouts.app')
@section('title', 'Unsubscribe')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Unsubscribe from the newsletter" />

<x-card>
    @if ($subscriber->status === 'unsubscribed')
        <p class="mb-0" role="status"><i class="bi bi-envelope-slash me-1" aria-hidden="true"></i> {{ $subscriber->email }} is already unsubscribed.</p>
    @else
        <p>Stop sending the AQDesk newsletter to <strong>{{ $subscriber->email }}</strong>?</p>
        <form method="POST" action="{{ route('newsletter.unsubscribe.store', $subscriber->token) }}">
            @csrf
            <button type="submit" class="btn btn-accent"><i class="bi bi-envelope-slash me-1" aria-hidden="true"></i> Unsubscribe</button>
        </form>
    @endif
</x-card>
@endsection
