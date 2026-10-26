<?php

namespace App\Modules\EventsMedia\Services;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Models\EventRegistration;
use App\Modules\EventsMedia\Notifications\EventCancelled;
use App\Modules\EventsMedia\Notifications\EventChanged;
use App\Modules\EventsMedia\Notifications\EventReminder;
use App\Modules\EventsMedia\Notifications\OrganizerReminder;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use App\Modules\Platform\Services\FormSubmissionService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Organizing events (FR4, roadmap 3.6). */
class EventService
{
    public function __construct(private RegistrationService $registrations, private ActivityLogger $activity) {}

    /** @param  array<string, mixed>  $data  validated EventRequest data (times already in UTC) */
    public function create(User $organizer, array $data): Event
    {
        return Event::create($data + ['user_id' => $organizer->id, 'slug' => Event::makeSlug($data['title']), 'status' => 'draft']);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when the capacity would drop below the seats already taken
     */
    public function update(Event $event, array $data): Event
    {
        $taken = $event->confirmedCount();
        if (isset($data['capacity']) && $data['capacity'] < $taken) {
            throw ValidationException::withMessages(['capacity' => 'Already '.$taken.' people have a seat. Capacity cannot be lower than that.']);
        }

        $event->fill($data + ['slug' => Event::makeSlug($data['title'], $event->id)]);
        $changed = array_keys(array_filter([
            'time'         => $event->isDirty('starts_at') || $event->isDirty('ends_at') || $event->isDirty('timezone'),
            'venue'        => $event->isDirty('venue') || $event->isDirty('format'),
            'joining link' => $event->isDirty('meeting_url') || $event->isDirty('meeting_notes'),
        ]));
        $event->save();

        if (in_array('time', $changed, true)) {
            // Reminders follow the new time (roadmap 3.9).
            $event->update(['organizer_reminded_at' => null]);
            $event->registrations()->update(['reminded_day_at' => null, 'reminded_hour_at' => null]);
        }
        if ($changed && $event->status === 'published' && ! $event->hasEnded()) {
            $event->registrations()->where('status', '!=', 'cancelled')->with('user')->get()
                ->each(fn (EventRegistration $r) => $r->user?->notify(new EventChanged($event, $changed, $r->status === 'confirmed')));
        }

        $this->registrations->fillSeats($event);   // more seats → the waitlist moves up

        return $event;
    }

    /**
     * Scheduled every 15 minutes (roadmap 3.9). People with a seat get a
     * reminder the day before and an hour before; the organizer gets the
     * head count the day before. Each one goes out once: the time it was
     * sent is stored, and someone who registers late gets only the nearest one.
     *
     * @return array{attendees: int, organizers: int}
     */
    public function sendReminders(): array
    {
        $sent = ['attendees' => 0, 'organizers' => 0];

        Event::published()->where('starts_at', '>', now())->where('starts_at', '<=', now()->addDay())
            ->with('organizer')->each(function (Event $event) use (&$sent) {
                $soon = $event->starts_at->lte(now()->addHour());

                $event->registrations()->where('status', 'confirmed')->whereNull($soon ? 'reminded_hour_at' : 'reminded_day_at')
                    ->with('user')->each(function (EventRegistration $r) use ($event, $soon, &$sent) {
                        // Claim the reminder first, so an overlapping run cannot send it again.
                        $claimed = EventRegistration::whereKey($r->id)->whereNull($soon ? 'reminded_hour_at' : 'reminded_day_at')
                            ->update($soon ? ['reminded_hour_at' => now(), 'reminded_day_at' => $r->reminded_day_at ?? now()] : ['reminded_day_at' => now()]);
                        if ($claimed && $r->user?->is_active) {
                            $r->user->notify(new EventReminder($event, $soon ? 'hour' : 'day'));
                            $sent['attendees']++;
                        }
                    });

                if (! $event->organizer_reminded_at && Event::whereKey($event->id)->whereNull('organizer_reminded_at')->update(['organizer_reminded_at' => now()])) {
                    $event->organizer?->notify(new OrganizerReminder($event, $event->confirmedCount(), $event->waitlistCount()));
                    $sent['organizers'] += $event->organizer ? 1 : 0;
                }
            });

        return $sent;
    }

    public function publish(Event $event): Event
    {
        $event->update(['status' => 'published']);

        return $event;
    }

    /** Everyone with a seat or a waitlist spot is told, whatever their email settings. */
    public function cancel(Event $event, ?string $reason): Event
    {
        $event->update(['status' => 'cancelled', 'cancel_reason' => $reason]);

        $people = $event->registrations()->where('status', '!=', 'cancelled')->with('user')->get()->pluck('user')->filter();
        Notification::send($people, new EventCancelled($event));

        $this->activity->record('event.cancelled', $event, 'Cancelled the event “'.$event->title.'”', ['notified' => $people->count()]);

        return $event;
    }

    public function delete(Event $event): void
    {
        $this->activity->record('event.deleted', $event, 'Deleted the event “'.$event->title.'”');
        $event->delete();
    }

    /** Attendee list with every registration answer, for the organizer. */
    public function export(Event $event): StreamedResponse
    {
        $fields = $event->form?->fields ?? collect();
        $rows = $event->registrations()->with('user', 'response.answers')->orderByRaw("case status when 'confirmed' then 0 when 'waitlisted' then 1 else 2 end")
            ->orderBy('queued_at')->get();

        return response()->streamDownload(function () use ($fields, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Name', 'Email', 'Status', 'Registered at'], $fields->pluck('label')->all()));
            foreach ($rows as $r) {
                $answers = $r->response?->answers->keyBy('form_field_id') ?? collect();
                $row = [$r->user?->name, $r->user?->email, EventRegistration::STATUSES[$r->status][0], $r->queued_at->toDateTimeString()];
                foreach ($fields as $field) {
                    $row[] = $answers->has($field->id) ? $answers[$field->id]->display() : '';
                }
                fputcsv($out, array_map([FormSubmissionService::class, 'csvSafe'], $row));
            }
            fclose($out);
        }, 'attendees-'.$event->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
