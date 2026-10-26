<?php

namespace App\Modules\EventsMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Models\EventRegistration;
use App\Modules\EventsMedia\Services\EventService;
use App\Modules\EventsMedia\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** FR4 "Event registration": seats, waitlist and registration questions (roadmap 3.6 – 3.7). */
class RegistrationController extends Controller
{
    public function __construct(private RegistrationService $registrations, private EventService $events) {}

    /** The registration questions, when the event has a form. */
    public function create(Request $request, Event $event): View|RedirectResponse
    {
        $this->authorize('view', $event);
        $event->load('form.fields');
        if (! $event->hasQuestions()) {
            return redirect()->route('events.show', $event);
        }

        $mine = $event->registrations()->where('user_id', $request->user()->id)->first();

        return view('events-media::events.register', ['event' => $event, 'already' => $mine?->isActive(), 'full' => $event->isFull()]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('view', $event);
        $this->authorize('register', $event);

        $answers = $request->input('answers', []);
        $registration = $this->registrations->register($event, $request->user(), is_array($answers) ? $answers : []);

        return redirect()->route('events.show', $event)->with('status', $registration->status === 'confirmed'
            ? 'You are registered. See you there!'
            : 'The event is full, so you are number '.$registration->waitlistPosition().' on the waitlist. We will tell you if a seat opens up.');
    }

    /** Cancel your own registration or leave the waitlist. */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $registration = $event->registrations()->where('user_id', $request->user()->id)->where('status', '!=', 'cancelled')->firstOrFail();
        $wasConfirmed = $registration->status === 'confirmed';

        $this->registrations->cancel($registration->setRelation('event', $event));

        return back()->with('status', $wasConfirmed ? 'Registration cancelled. Your seat goes to the next person waiting.' : 'You left the waitlist.');
    }

    /** The organizer's attendee list, with registration answers. */
    public function index(Request $request, Event $event): View
    {
        $this->authorize('update', $event);
        $tab = in_array($request->query('tab'), ['waitlisted', 'cancelled'], true) ? $request->query('tab') : 'confirmed';

        $event->load('form.fields');
        $registrations = $event->registrations()->where('status', $tab)->with('user', 'response.answers')
            ->orderBy('queued_at')->orderBy('id')->paginate(50)->withQueryString();
        $counts = $event->registrations()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('events-media::events.attendees', compact('event', 'registrations', 'tab', 'counts'));
    }

    public function export(Event $event): StreamedResponse
    {
        $this->authorize('update', $event);

        return $this->events->export($event->load('form.fields'));
    }

    public function remove(Event $event, EventRegistration $registration): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($registration->event_id === $event->id, 404);

        $this->registrations->remove($registration->setRelation('event', $event));

        return back()->with('status', 'Registration cancelled; '.($registration->user?->name ?? 'the member').' has been told.');
    }
}
