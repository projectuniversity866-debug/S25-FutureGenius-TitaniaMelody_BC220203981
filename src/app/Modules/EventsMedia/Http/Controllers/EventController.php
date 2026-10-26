<?php

namespace App\Modules\EventsMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EventsMedia\Http\Requests\CancelEventRequest;
use App\Modules\EventsMedia\Http\Requests\EventRequest;
use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Services\EventService;
use App\Modules\EventsMedia\Support\CalendarFile;
use App\Modules\Platform\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** FR4 "Event creation" (roadmap 3.6). */
class EventController extends Controller
{
    public function __construct(private EventService $events) {}

    /** Upcoming and past events for everyone; "My registrations" and "Organizing" when signed in. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tabs = ['upcoming', 'past'];
        if ($user) {
            $tabs[] = 'mine';
        }
        if ($user?->can('create', Event::class)) {
            $tabs[] = 'organizing';
        }
        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'upcoming';
        $format = array_key_exists((string) $request->query('format'), Event::FORMATS) ? $request->query('format') : null;

        $queries = [
            'upcoming'   => fn () => Event::published()->upcoming()->orderBy('starts_at'),
            'past'       => fn () => Event::whereIn('status', ['published', 'cancelled'])->where('ends_at', '<', now())->latest('starts_at'),
            'mine'       => fn () => Event::where('status', '!=', 'draft')
                ->whereHas('registrations', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
                ->orderByRaw('ends_at < ?', [now()])->orderBy('starts_at'),
            'organizing' => fn () => Event::where('user_id', $user->id)->latest('starts_at'),
        ];

        $events = $queries[$tab]()->when($format, fn ($q) => $q->where('format', $format))
            ->with('organizer')
            ->withCount(['registrations as confirmed_count' => fn ($q) => $q->where('status', 'confirmed')])
            ->paginate(12)->withQueryString();
        $counts = collect($tabs)->mapWithKeys(fn ($t) => [$t => $queries[$t]()->count()]);

        return view('events-media::events.index', compact('events', 'tab', 'tabs', 'counts', 'format'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Event::class);

        return view('events-media::events.create', ['forms' => $this->formsOf($request->user()->id)]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = $this->events->create($request->user(), $request->eventData());

        return redirect()->route('events.show', $event)->with('status', 'Draft saved. Check the details, then publish it.');
    }

    public function show(Request $request, Event $event): View
    {
        $this->authorize('view', $event);

        $event->load('organizer', 'form.fields');
        $mine = $request->user() ? $event->registrations()->where('user_id', $request->user()->id)->first() : null;

        return view('events-media::events.show', [
            'event'     => $event,
            'mine'      => $mine?->setRelation('event', $event),
            'confirmed' => $event->confirmedCount(),
            'waitlist'  => $event->waitlistCount(),
        ]);
    }

    /** Add to calendar (.ics, roadmap 3.8). The joining link is included only for people with a seat. */
    public function calendar(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);
        abort_if($event->status === 'draft', 404);

        $withLink = (bool) $request->user()?->can('join', $event);

        return response(CalendarFile::make($event->load('organizer'), $withLink), 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.CalendarFile::filename($event).'"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        return view('events-media::events.edit', ['event' => $event, 'forms' => $this->formsOf($event->user_id)]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $this->events->update($event, $request->eventData());

        return redirect()->route('events.show', $event)->with('status', 'Event saved.');
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($event->status === 'draft', 404);

        $this->events->publish($event);

        return back()->with('status', 'Published: members can now register.');
    }

    public function cancel(CancelEventRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->status === 'published', 404);

        $this->events->cancel($event, $request->validated('reason'));

        return back()->with('status', 'Event cancelled. Everyone registered has been told.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $this->events->delete($event);

        return redirect()->route('events.index', ['tab' => 'organizing'])->with('status', 'Event deleted.');
    }

    /** Forms the organizer can use as registration questions (roadmap 3.7). */
    private function formsOf(?int $userId)
    {
        return Form::where('user_id', $userId)->withCount('fields')->orderBy('title')->get(['id', 'title', 'status']);
    }
}
