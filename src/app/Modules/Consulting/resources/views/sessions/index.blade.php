@extends('layouts.app')
@section('title', 'My sessions')
@section('subtitle', 'Consulting')

@section('content')
@php use App\Modules\Consulting\Models\Consultation; $url = fn ($t) => route('consulting.sessions.index', array_filter(['side' => $side === 'clients' ? 'clients' : null, 'tab' => $t === 'upcoming' ? null : $t])); @endphp
<x-page-header title="My sessions" subtitle="Consulting sessions you booked{{ $profile ? ', and the ones clients booked with you' : '' }}.">
    <x-slot:actions>
        <a href="{{ route('consultants.index') }}" class="btn btn-quiet"><i class="bi bi-person-video3 me-1" aria-hidden="true"></i> Find a consultant</a>
        @if ($profile)
            <a href="{{ route('consulting.clients.index') }}" class="btn btn-quiet"><i class="bi bi-people me-1" aria-hidden="true"></i> My clients</a>
        @endif
    </x-slot:actions>
</x-page-header>

@if ($profile)
    <x-tabs label="Whose sessions" :items="[
        ['label' => 'I booked', 'url' => route('consulting.sessions.index'), 'active' => $side === 'mine'],
        ['label' => 'Booked with me', 'url' => route('consulting.sessions.index', ['side' => 'clients']), 'active' => $side === 'clients'],
    ]" />
@endif
<x-tabs label="Session status" :items="[
    ['label' => 'Upcoming', 'url' => $url('upcoming'), 'active' => $tab === 'upcoming', 'count' => $counts['upcoming']],
    ['label' => 'Past', 'url' => $url('past'), 'active' => $tab === 'past', 'count' => $counts['past']],
    ['label' => 'Cancelled', 'url' => $url('cancelled'), 'active' => $tab === 'cancelled', 'count' => $counts['cancelled']],
]" />

@if ($sessions->isEmpty())
    <div class="card">
        <x-empty icon="calendar-week" :title="$tab === 'upcoming' ? 'No upcoming sessions' : 'Nothing here yet'"
                 :action="$side === 'mine' && $tab === 'upcoming' ? ['url' => route('consultants.index'), 'label' => 'Find a consultant'] : null" />
    </div>
@else
    <div class="card">
        <ul class="list-group list-group-flush">
            @foreach ($sessions as $s)
                @php $other = $side === 'clients' ? $s->client : $s->profile->user; @endphp
                <li class="list-group-item d-flex flex-wrap align-items-center gap-3">
                    @if ($other)<x-avatar :user="$other" size="sm" />@endif
                    <div class="min-w-0 flex-grow-1">
                        <a href="{{ route('consulting.sessions.show', $s) }}" class="fw-semibold text-decoration-none">{{ $s->topic }}</a>
                        <span class="d-block aq-text-xs aq-muted">
                            {{ $side === 'clients' ? 'Client' : 'With' }} {{ $other?->name ?? 'Deleted user' }} · <time datetime="{{ $s->starts_at->toIso8601String() }}">{{ $s->whenLabel() }}</time>
                        </span>
                    </div>
                    <span class="badge {{ ['confirmed' => 'text-bg-primary', 'completed' => 'text-bg-success', 'cancelled' => 'text-bg-secondary'][$s->status] }}">
                        <i class="bi bi-{{ Consultation::STATUSES[$s->status][1] }} me-1" aria-hidden="true"></i>{{ Consultation::STATUSES[$s->status][0] }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    <nav aria-label="Session pages" class="mt-3">{{ $sessions->links() }}</nav>
@endif
@endsection
