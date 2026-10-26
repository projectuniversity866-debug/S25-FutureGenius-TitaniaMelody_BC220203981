@extends('layouts.app')
@section('title', 'Events')
@section('subtitle', 'Workshops, webinars and meet-ups')

@section('content')
@php
    use App\Modules\EventsMedia\Models\Event;
    $labels = ['upcoming' => 'Upcoming', 'past' => 'Past', 'mine' => 'My registrations', 'organizing' => 'Organizing'];
    $tabItems = collect($tabs)->map(fn ($t) => [
        'label' => $labels[$t], 'url' => route('events.index', array_filter(['tab' => $t === 'upcoming' ? null : $t, 'format' => $format])),
        'active' => $tab === $t, 'count' => $counts[$t],
    ])->all();
@endphp

<x-page-header title="Events" subtitle="Educational workshops, webinars and meet-ups from the AQDesk community.">
    @can('create', Event::class)
        <x-slot:actions>
            <a href="{{ route('events.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New event</a>
        </x-slot:actions>
    @endcan
</x-page-header>

<x-tabs label="Which events" :items="$tabItems" />

<nav aria-label="Event format" class="aq-chip-row mb-3">
    <a href="{{ route('events.index', array_filter(['tab' => $tab === 'upcoming' ? null : $tab])) }}"
       @class(['aq-tag', 'is-active' => ! $format]) @if (! $format) aria-current="page" @endif>Any format</a>
    @foreach (Event::FORMATS as $key => [$label, $icon])
        <a href="{{ route('events.index', array_filter(['tab' => $tab === 'upcoming' ? null : $tab, 'format' => $key])) }}"
           @class(['aq-tag', 'is-active' => $format === $key]) @if ($format === $key) aria-current="page" @endif>
            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i> {{ $label }}
        </a>
    @endforeach
</nav>

@if ($events->isEmpty())
    <div class="card">
        <x-empty icon="calendar-event" :title="[
            'upcoming' => 'No upcoming events', 'past' => 'No past events', 'mine' => 'You have not registered for any event', 'organizing' => 'You have not created an event yet',
        ][$tab]" :message="$tab === 'mine' ? 'Browse upcoming events and save your seat.' : null"
                 :action="$tab === 'organizing' ? ['url' => route('events.create'), 'label' => 'Create an event'] : null" />
    </div>
@else
    <div class="row g-3">
        @foreach ($events as $event)
            @php $start = $event->local($event->starts_at); $left = $event->seatsLeft($event->confirmed_count); @endphp
            <div class="col-md-6 col-xl-4">
                <article class="card h-100 aq-article-card">
                    <div class="card-body d-flex gap-3">
                        <div class="aq-date-block" aria-hidden="true">
                            <span>{{ $start->format('M') }}</span><strong>{{ $start->format('j') }}</strong>
                        </div>
                        <div class="min-w-0 d-flex flex-column flex-grow-1">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge text-bg-light"><i class="bi bi-{{ Event::FORMATS[$event->format][1] }} me-1" aria-hidden="true"></i>{{ Event::FORMATS[$event->format][0] }}</span>
                                @if ($event->displayStatus() !== 'published')<x-status :value="$event->displayStatus()" />@endif
                            </div>
                            <h2 class="aq-card-title"><a href="{{ route('events.show', $event) }}" class="stretched-link text-decoration-none">{{ $event->title }}</a></h2>
                            <p class="aq-text-xs aq-muted mb-2"><time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->whenLabel() }}</time></p>
                            <p class="aq-text-sm aq-ink-2 flex-grow-1">{{ \Illuminate\Support\Str::limit($event->summary, 120) }}</p>
                            <p class="mb-0 aq-text-xs aq-muted">
                                @if ($event->status === 'published' && ! $event->hasEnded())
                                    @if ($left === null)
                                        <i class="bi bi-people" aria-hidden="true"></i> {{ $event->confirmed_count }} going · open seats
                                    @elseif ($left === 0)
                                        <i class="bi bi-hourglass-split" aria-hidden="true"></i> Full · waitlist open
                                    @else
                                        <i class="bi bi-people" aria-hidden="true"></i> {{ $left }} of {{ $event->capacity }} seats left
                                    @endif
                                    ·
                                @endif
                                by {{ $event->organizer?->name ?? 'Deleted user' }}
                            </p>
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <nav aria-label="Event pages" class="mt-4">{{ $events->links() }}</nav>
@endif
@endsection
