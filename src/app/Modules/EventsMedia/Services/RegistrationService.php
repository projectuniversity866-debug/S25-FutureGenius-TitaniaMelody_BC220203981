<?php

namespace App\Modules\EventsMedia\Services;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Models\EventRegistration;
use App\Modules\EventsMedia\Notifications\RegistrationUpdate;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\FormSubmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seats and the waitlist (roadmap 3.6) and registration answers (3.7).
 *
 * Every change that can take or free a seat runs inside a transaction that
 * locks the event row first, so two people registering for the last seat
 * at the same moment cannot both get it (on MySQL; SQLite runs one writer
 * at a time anyway).
 */
class RegistrationService
{
    public function __construct(private FormSubmissionService $forms) {}

    /**
     * @param  array<string, mixed>  $answers  answers[field id] to the event's registration questions
     *
     * @throws ValidationException when registration is closed, you are already in, or an answer is invalid
     */
    public function register(Event $event, User $user, array $answers = []): EventRegistration
    {
        $registration = DB::transaction(function () use ($event, $user, $answers) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            if (! $event->isRegistrationOpen()) {
                throw ValidationException::withMessages(['registration' => 'Registration for this event is closed.']);
            }

            $existing = $event->registrations()->where('user_id', $user->id)->first();
            if ($existing?->isActive()) {
                throw ValidationException::withMessages(['registration' => 'You are already registered for this event.']);
            }

            $event->load('form.fields');
            $response = $event->hasQuestions() ? $this->forms->store($event->form, $user, $answers) : null;
            $existing?->response()->delete();   // re-registering replaces earlier answers

            $registration = $existing ?? $event->registrations()->make(['user_id' => $user->id]);
            $registration->fill([
                'status'           => $event->isFull() ? 'waitlisted' : 'confirmed',
                'form_response_id' => $response?->id,
                'queued_at'        => now(),       // a returning registrant goes to the back of the line
                'promoted_at'      => null,
                'cancelled_at'     => null,
            ])->save();

            return $registration->setRelation('event', $event);
        });

        $user->notify(new RegistrationUpdate($registration, $registration->status));

        return $registration;
    }

    /** The member cancels; a freed seat goes to the first person waiting. */
    public function cancel(EventRegistration $registration): void
    {
        $this->release($registration);
    }

    /** The organizer removes someone (e.g. a duplicate); they are told. */
    public function remove(EventRegistration $registration): void
    {
        if ($this->release($registration)) {
            $registration->user->notify(new RegistrationUpdate($registration, 'removed'));
        }
    }

    /**
     * Give free seats to the waitlist in order, e.g. after a cancellation or
     * when the organizer raises the capacity.
     *
     * @return int how many moved up
     */
    public function fillSeats(Event $event): int
    {
        $promoted = DB::transaction(function () use ($event) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($event->status !== 'published' || $event->hasEnded()) {
                return collect();
            }

            $seats = $event->seatsLeft() ?? PHP_INT_MAX;
            $next = $event->registrations()->where('status', 'waitlisted')->orderBy('queued_at')->orderBy('id')
                ->limit(min($seats, 1000))->get();
            $next->each->update(['status' => 'confirmed', 'promoted_at' => now()]);

            return $next->each->setRelation('event', $event);
        });

        $promoted->each(fn (EventRegistration $r) => $r->user->notify(new RegistrationUpdate($r, 'promoted')));

        return $promoted->count();
    }

    /** @return bool whether anything changed */
    private function release(EventRegistration $registration): bool
    {
        if (! $registration->isActive()) {
            return false;
        }

        $freedSeat = $registration->status === 'confirmed';
        $registration->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        if ($freedSeat) {
            $this->fillSeats($registration->event);
        }

        return true;
    }
}
