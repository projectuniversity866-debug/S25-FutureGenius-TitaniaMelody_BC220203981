@extends('layouts.app')
@section('title', 'Attendees · '.$event->title)
@section('subtitle', 'Events')

@section('content')
@php $fields = $event->form?->fields ?? collect(); @endphp
<x-page-header title="Attendees" :subtitle="$event->title.' · '.$event->whenLabel()"
               :breadcrumbs="['Events' => route('events.index'), $event->title => route('events.show', $event), 'Attendees' => null]">
    <x-slot:actions>
        <a href="{{ route('events.attendees.export', $event) }}" class="btn btn-quiet btn-sm"><i class="bi bi-download me-1" aria-hidden="true"></i> Download CSV</a>
    </x-slot:actions>
</x-page-header>

<x-tabs label="Registration status" :items="[
    ['label' => 'Confirmed', 'url' => route('events.attendees', $event), 'active' => $tab === 'confirmed', 'count' => (int) ($counts['confirmed'] ?? 0)],
    ['label' => 'Waitlist', 'url' => route('events.attendees', [$event, 'tab' => 'waitlisted']), 'active' => $tab === 'waitlisted', 'count' => (int) ($counts['waitlisted'] ?? 0)],
    ['label' => 'Cancelled', 'url' => route('events.attendees', [$event, 'tab' => 'cancelled']), 'active' => $tab === 'cancelled', 'count' => (int) ($counts['cancelled'] ?? 0)],
]" />

@if ($registrations->isEmpty())
    <div class="card"><x-empty icon="people" title="Nobody here yet" /></div>
@else
    <div class="card">
        <ol class="list-group list-group-flush">
            @foreach ($registrations as $registration)
                @php $answers = $registration->response?->answers->keyBy('form_field_id') ?? collect(); @endphp
                <li class="list-group-item">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($tab === 'waitlisted')<span class="badge text-bg-light">#{{ $registrations->firstItem() + $loop->index }}</span>@endif
                        @if ($registration->user)<x-avatar :user="$registration->user" size="sm" />@endif
                        <div class="min-w-0 flex-grow-1">
                            <span class="fw-semibold">{{ $registration->user?->name ?? 'Deleted user' }}</span>
                            <span class="d-block aq-text-xs aq-muted">
                                {{ $registration->user?->email }} · registered {{ $registration->queued_at->diffForHumans() }}
                                @if ($registration->promoted_at) · moved up from the waitlist {{ $registration->promoted_at->diffForHumans() }}@endif
                                @if ($registration->cancelled_at) · cancelled {{ $registration->cancelled_at->diffForHumans() }}@endif
                            </span>
                        </div>
                        @if ($registration->isActive())
                            <form method="POST" action="{{ route('events.attendees.remove', [$event, $registration]) }}"
                                  data-confirm-title="Cancel {{ $registration->user?->name }}’s registration?" data-confirm="They are told; a freed seat goes to the waitlist." data-confirm-button="Cancel registration">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel<span class="visually-hidden"> registration of {{ $registration->user?->name }}</span></button>
                            </form>
                        @endif
                    </div>
                    @if ($fields->isNotEmpty())
                        <dl class="aq-answer-list">
                            @foreach ($fields as $field)
                                <dt>{{ $field->label }}</dt>
                                <dd>{{ $answers->has($field->id) ? $answers[$field->id]->display() : '—' }}</dd>
                            @endforeach
                        </dl>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
    <nav aria-label="Attendee pages" class="mt-3">{{ $registrations->links() }}</nav>
@endif
@endsection
