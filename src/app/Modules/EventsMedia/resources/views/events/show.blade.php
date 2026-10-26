@extends('layouts.app')
@section('title', $event->title)
@section('subtitle', 'Event')

@section('content')
@php
    use App\Modules\EventsMedia\Models\Event;
    $left = $event->seatsLeft($confirmed);
    $active = $mine?->isActive();
    $canJoin = (bool) auth()->user()?->can('join', $event);
@endphp
<x-page-header :title="$event->title" :subtitle="$event->summary" :breadcrumbs="['Events' => route('events.index'), $event->title => null]">
    @can('update', $event)
        <x-slot:actions>
            <a href="{{ route('events.attendees', $event) }}" class="btn btn-quiet btn-sm"><i class="bi bi-people me-1" aria-hidden="true"></i> Attendees</a>
            <a href="{{ route('events.edit', $event) }}" class="btn btn-quiet btn-sm"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit</a>
            @if ($event->status === 'draft')
                <form method="POST" action="{{ route('events.publish', $event) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-megaphone me-1" aria-hidden="true"></i> Publish</button>
                </form>
            @endif
            @can('delete', $event)
                <form method="POST" action="{{ route('events.destroy', $event) }}"
                      data-confirm-title="Delete “{{ $event->title }}”?" data-confirm="This cannot be undone." data-confirm-button="Delete event">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
                </form>
            @endcan
        </x-slot:actions>
    @endcan
</x-page-header>

@if ($event->status === 'draft')
    <div class="alert alert-warning py-2 aq-text-sm" role="status"><i class="bi bi-eye me-1" aria-hidden="true"></i> Draft — only you can see it. Publish it to open registration.</div>
@elseif ($event->status === 'cancelled')
    <div class="alert alert-danger py-2 aq-text-sm" role="status">
        <i class="bi bi-calendar-x me-1" aria-hidden="true"></i> <strong>This event was cancelled.</strong>
        @if ($event->cancel_reason) {{ $event->cancel_reason }}@endif
    </div>
@elseif ($event->hasEnded())
    <div class="alert alert-secondary py-2 aq-text-sm" role="status"><i class="bi bi-clock-history me-1" aria-hidden="true"></i> This event has ended.</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <x-card class="mb-4">
            <ul class="aq-event-facts">
                <li><i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <div><strong class="d-block">When</strong><time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->whenLabel() }}</time></div></li>
                <li><i class="bi bi-{{ Event::FORMATS[$event->format][1] }}" aria-hidden="true"></i>
                    <div><strong class="d-block">{{ Event::FORMATS[$event->format][0] }}</strong>
                        {{ $event->venue ?? '' }}@if ($event->isOnline() && ! $canJoin){{ $event->venue ? ' · ' : '' }}Online: the joining link is shared with people who have a seat.@endif</div></li>
                <li><i class="bi bi-person-badge" aria-hidden="true"></i>
                    <div><strong class="d-block">Organizer</strong>
                        @if ($event->organizer)<a href="{{ route('profile.show', $event->organizer) }}">{{ $event->organizer->name }}</a>@else Deleted user @endif</div></li>
            </ul>
            @if ($event->status !== 'draft')
                <div class="d-flex flex-wrap gap-2 aq-border-top pt-3 mt-3">
                    <a href="{{ route('events.calendar', $event) }}" class="btn btn-quiet btn-sm">
                        <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i> Add to calendar (.ics)
                    </a>
                    <a href="{{ \App\Modules\EventsMedia\Support\CalendarFile::googleUrl($event) }}" class="btn btn-quiet btn-sm" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-google me-1" aria-hidden="true"></i> Google Calendar<span class="visually-hidden"> (opens in a new tab)</span>
                    </a>
                </div>
            @endif
        </x-card>

        @if ($event->isOnline() && $canJoin && $event->status !== 'cancelled' && ! $event->hasEnded())
            <x-card title="Join online" icon="camera-video" class="mb-4" id="join">
                @if ($event->meeting_url)
                    <p class="aq-text-sm mb-2">
                        {{ $event->meetingProvider() }} ·
                        @if ($event->isJoinable())
                            <strong>happening now</strong>
                        @else
                            the room opens {{ Event::JOIN_OPENS_MINUTES }} minutes before the start
                        @endif
                    </p>
                    <a href="{{ $event->meeting_url }}" target="_blank" rel="noopener noreferrer"
                       class="btn {{ $event->isJoinable() ? 'btn-accent' : 'btn-soft' }} btn-sm">
                        <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i> {{ $event->isJoinable() ? 'Join now' : 'Open the joining link' }}<span class="visually-hidden"> (opens {{ $event->meetingProvider() }} in a new tab)</span>
                    </a>
                    <p class="aq-text-xs aq-muted mt-2 mb-0 text-break">{{ $event->meeting_url }}</p>
                    @if ($event->meeting_notes)<p class="aq-text-sm mt-2 mb-0">{{ $event->meeting_notes }}</p>@endif
                @else
                    <p class="aq-text-sm aq-muted mb-0">The organizer has not added the joining link yet. You will be told when they do.</p>
                @endif
            </x-card>
        @endif

        <x-card title="About this event" icon="info-circle">
            @if ($event->description)
                <x-markdown :text="$event->description" />
            @else
                <p class="aq-text-sm aq-muted mb-0">{{ $event->summary }}</p>
            @endif
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="Registration" icon="person-check" id="registration">
            @if ($event->capacity)
                <label for="seats" class="aq-text-sm d-block mb-1">{{ $confirmed }} of {{ $event->capacity }} seats taken</label>
                <progress id="seats" class="aq-progress mb-2" max="{{ $event->capacity }}" value="{{ $confirmed }}">{{ $confirmed }} of {{ $event->capacity }}</progress>
                <p class="aq-text-xs aq-muted">
                    @if ($left === 0)<i class="bi bi-hourglass-split" aria-hidden="true"></i> Full · {{ $waitlist }} on the waitlist @else {{ $left }} {{ \Illuminate\Support\Str::plural('seat', $left) }} left @endif
                </p>
            @else
                <p class="aq-text-sm">{{ $confirmed }} {{ \Illuminate\Support\Str::plural('person', $confirmed) }} going · no seat limit</p>
            @endif
            @if ($event->registration_closes_at && $event->isRegistrationOpen())
                <p class="aq-text-xs aq-muted">Registration closes {{ $event->local($event->registration_closes_at)->format('D j M, H:i T') }}.</p>
            @endif

            @error('registration')<div class="alert alert-danger py-2 aq-text-sm" role="alert">{{ $message }}</div>@enderror

            @if ($active)
                <div class="alert {{ $mine->status === 'confirmed' ? 'alert-success' : 'alert-warning' }} py-2 aq-text-sm" role="status">
                    <i class="bi bi-{{ \App\Modules\EventsMedia\Models\EventRegistration::STATUSES[$mine->status][1] }} me-1" aria-hidden="true"></i>
                    @if ($mine->status === 'confirmed')
                        <strong>You have a seat.</strong>
                    @else
                        <strong>You are number {{ $mine->waitlistPosition() }} on the waitlist.</strong> We will tell you if a seat opens up.
                    @endif
                </div>
                @unless ($event->hasEnded() || $event->status === 'cancelled')
                    <form method="POST" action="{{ route('events.register.destroy', $event) }}"
                          data-confirm-title="{{ $mine->status === 'confirmed' ? 'Give up your seat?' : 'Leave the waitlist?' }}"
                          data-confirm="{{ $mine->status === 'confirmed' ? 'Your seat goes to the next person waiting.' : 'You lose your place in the line.' }}"
                          data-confirm-button="{{ $mine->status === 'confirmed' ? 'Cancel registration' : 'Leave waitlist' }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-quiet btn-sm">{{ $mine->status === 'confirmed' ? 'Cancel my registration' : 'Leave the waitlist' }}</button>
                    </form>
                @endunless
            @elseif ($event->isRegistrationOpen())
                @auth
                    @if ($event->hasQuestions())
                        <a href="{{ route('events.register', $event) }}" class="btn btn-accent w-100">
                            {{ $left === 0 ? 'Join the waitlist' : 'Register' }}
                        </a>
                        <p class="aq-text-xs aq-muted mt-2 mb-0">The organizer asks {{ $event->form->fields->count() }} {{ \Illuminate\Support\Str::plural('question', $event->form->fields->count()) }}.</p>
                    @else
                        <form method="POST" action="{{ route('events.register.store', $event) }}">
                            @csrf
                            <button type="submit" class="btn btn-accent w-100">{{ $left === 0 ? 'Join the waitlist' : 'Register' }}</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-accent w-100">Sign in to register</a>
                @endauth
            @else
                <p class="aq-text-sm aq-muted mb-0"><i class="bi bi-lock me-1" aria-hidden="true"></i> Registration is closed.</p>
            @endif

            @can('update', $event)
                @if ($event->status === 'published' && ! $event->hasEnded())
                    <form method="POST" action="{{ route('events.cancel', $event) }}" class="aq-border-top pt-3 mt-3"
                          data-confirm-title="Cancel “{{ $event->title }}”?" data-confirm="Everyone registered or waiting is told by email." data-confirm-button="Cancel event">
                        @csrf
                        @method('PUT')
                        <label for="reason" class="form-label">Cancel the event</label>
                        <input type="text" id="reason" name="reason" maxlength="500" class="form-control form-control-sm mb-2" aria-describedby="reason_hint">
                        <div id="reason_hint" class="form-text mb-2">Optional reason, sent to everyone registered.</div>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel event</button>
                    </form>
                @endif
            @endcan
        </x-card>
    </div>
</div>
@endsection
