@extends('layouts.app')
@section('title', $consultation->topic)
@section('subtitle', 'Consulting session')

@section('content')
@php
    use App\Modules\Consulting\Models\Consultation;
    $c = $consultation;
    $isConsultant = auth()->id() === $c->profile->user_id;
@endphp
<x-page-header :title="$c->topic" :subtitle="$c->whenLabel()" :breadcrumbs="['My sessions' => route('consulting.sessions.index', $isConsultant ? ['side' => 'clients'] : []), $c->topic => null]" />

@if ($c->status === 'cancelled')
    <div class="alert alert-secondary py-2 aq-text-sm" role="status">
        <i class="bi bi-calendar-x me-1" aria-hidden="true"></i> Cancelled by {{ $c->canceller?->name ?? 'Deleted user' }} {{ $c->cancelled_at?->diffForHumans() }}.
        @if ($c->cancel_reason) Reason: {{ $c->cancel_reason }}@endif
    </div>
@elseif ($c->status === 'completed')
    <div class="alert alert-success py-2 aq-text-sm" role="status"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> This session is done.</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <x-card class="mb-4">
            <ul class="aq-event-facts">
                <li><i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <div><strong class="d-block">When</strong><time datetime="{{ $c->starts_at->toIso8601String() }}">{{ $c->whenLabel() }}</time></div></li>
                <li><i class="bi bi-person-badge" aria-hidden="true"></i>
                    <div><strong class="d-block">Consultant</strong>
                        <a href="{{ route('consultants.show', $c->profile) }}">{{ $c->profile->user->name }}</a> · {{ $c->profile->priceLabel() }}</div></li>
                <li><i class="bi bi-person" aria-hidden="true"></i>
                    <div><strong class="d-block">Client</strong>
                        {{ $c->client?->name ?? 'Deleted user' }}
                        @if ($isConsultant && $c->clientRecord) · <a href="{{ route('consulting.clients.show', $c->clientRecord) }}">client record</a>@endif</div></li>
            </ul>
            @if ($c->message)
                <h2 class="aq-label mt-4">What {{ $isConsultant ? 'the client' : 'you' }} asked for help with</h2>
                <p class="aq-text-sm mb-0 aq-prewrap">{{ $c->message }}</p>
            @endif
        </x-card>
    </div>

    <div class="col-lg-5">
        @if ($c->status === 'confirmed' && ! $c->ends_at->isPast())
            <x-card title="Join online" icon="camera-video" class="mb-4">
                <p class="aq-text-sm mb-2">
                    Jitsi Meet ·
                    @if ($c->isJoinable()) <strong>the room is open</strong> @else opens {{ Consultation::JOIN_OPENS_MINUTES }} minutes before the start @endif
                </p>
                <a href="{{ $c->meeting_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm {{ $c->isJoinable() ? 'btn-accent' : 'btn-soft' }}">
                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>{{ $c->isJoinable() ? 'Join now' : 'Open the room link' }}<span class="visually-hidden"> (opens Jitsi Meet in a new tab)</span>
                </a>
                <p class="aq-text-xs aq-muted mt-2 mb-0 text-break">{{ $c->meeting_url }}</p>
            </x-card>
        @endif

        @can('complete', $c)
            <form method="POST" action="{{ route('consulting.sessions.complete', $c) }}" class="mb-3">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-soft btn-sm"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Mark as done</button>
            </form>
        @endcan

        @can('cancel', $c)
            <x-card title="Cancel this session" icon="calendar-x">
                <form method="POST" action="{{ route('consulting.sessions.cancel', $c) }}"
                      data-confirm-title="Cancel this session?" data-confirm="{{ $isConsultant ? 'The client' : $c->profile->user->name }} is told and the time becomes free again." data-confirm-button="Cancel session">
                    @csrf
                    @method('PUT')
                    <label for="reason" class="form-label">Reason <span class="aq-muted fw-normal">(optional, sent to {{ $isConsultant ? 'the client' : 'the consultant' }})</span></label>
                    <input type="text" id="reason" name="reason" maxlength="500" class="form-control form-control-sm mb-2 @error('reason') is-invalid @enderror">
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel session</button>
                </form>
            </x-card>
        @endcan
    </div>
</div>
@endsection
