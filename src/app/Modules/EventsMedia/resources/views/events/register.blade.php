@extends('layouts.app')
@section('title', 'Register · '.$event->title)
@section('subtitle', 'Events')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header :title="'Register for '.$event->title" :subtitle="$event->whenLabel()"
               :breadcrumbs="['Events' => route('events.index'), $event->title => route('events.show', $event), 'Register' => null]" />

<div class="card">
    <div class="card-body p-4">
        @if ($already)
            <x-empty icon="check-circle" title="You are already registered" :action="['url' => route('events.show', $event), 'label' => 'Back to the event']" />
        @elseif (! $event->isRegistrationOpen())
            <x-empty icon="lock" title="Registration for this event is closed" :action="['url' => route('events.show', $event), 'label' => 'Back to the event']" />
        @else
            @if ($full)
                <div class="alert alert-warning py-2 aq-text-sm" role="status">
                    <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i> The event is full. Submitting puts you on the waitlist; you move up as seats free.
                </div>
            @endif
            @if ($errors->has('registration'))
                <div class="alert alert-danger" role="alert">{{ $errors->first('registration') }}</div>
            @elseif ($errors->any())
                <div class="alert alert-danger" role="alert">Please check the {{ $errors->count() === 1 ? 'answer' : $errors->count().' answers' }} marked below.</div>
            @endif

            <form method="POST" action="{{ route('events.register.store', $event) }}" novalidate>
                @csrf
                <p class="aq-text-xs aq-muted"><span class="aq-danger" aria-hidden="true">*</span> marks a required question. Only the organizer sees your answers.</p>
                @include('platform::forms._questions', ['fields' => $event->form->fields])
                <button type="submit" class="btn btn-accent">{{ $full ? 'Join the waitlist' : 'Register' }}</button>
                <a href="{{ route('events.show', $event) }}" class="btn btn-quiet">Cancel</a>
            </form>
        @endif
    </div>
</div>
@endsection
