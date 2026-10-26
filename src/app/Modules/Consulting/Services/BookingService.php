<?php

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\Consultation;
use App\Modules\Consulting\Notifications\ConsultationUpdate;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * FR6 "Consultation booking" without conflicts (roadmap 3.12). A booking is
 * accepted only if, at that moment:
 *  - the start is one of the consultant's free slots (weekly hours, days off,
 *    notice, booking window, and no other active session overlapping);
 *  - the client has no other session overlapping it, with anyone;
 *  - the client is under the limit of upcoming sessions.
 * The checks run with the consultant's row locked, and the unique slot key
 * makes the database refuse a second booking of the same slot regardless.
 */
class BookingService
{
    public function __construct(private AvailabilityService $availability, private ClientService $clients) {}

    /** @throws ValidationException with a plain-language reason */
    public function book(ConsultantProfile $profile, User $client, CarbonImmutable $start, string $topic, ?string $message): Consultation
    {
        if ($client->id === $profile->user_id) {
            throw ValidationException::withMessages(['start' => 'You cannot book a session with yourself.']);
        }
        $end = $start->addMinutes($profile->session_minutes);

        try {
            $consultation = DB::transaction(function () use ($profile, $client, $start, $end, $topic, $message) {
                $profile = ConsultantProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
                if (! $profile->is_accepting) {
                    throw ValidationException::withMessages(['start' => 'This consultant is not taking bookings right now.']);
                }
                if (! $this->availability->isFree($profile, $start)) {
                    throw ValidationException::withMessages(['start' => 'That time is no longer free — someone may have just booked it. Please pick another.']);
                }

                $mine = Consultation::active()->where('client_id', $client->id);
                if ((clone $mine)->overlapping($start, $end)->exists()) {
                    throw ValidationException::withMessages(['start' => 'You already have a session at that time.']);
                }
                if ((clone $mine)->where('status', 'confirmed')->where('starts_at', '>', now())->count() >= Consultation::MAX_UPCOMING_PER_CLIENT) {
                    throw ValidationException::withMessages(['start' => 'You can hold up to '.Consultation::MAX_UPCOMING_PER_CLIENT.' upcoming sessions. Cancel one you no longer need first.']);
                }

                return $profile->consultations()->create([
                    'client_id'            => $client->id,
                    'consulting_client_id' => $this->clients->forMember($profile, $client)->id,
                    'starts_at'            => $start->utc(),
                    'ends_at'              => $end->utc(),
                    'topic'                => $topic,
                    'message'              => $message,
                    'meeting_url'          => Consultation::jitsiRoom(),
                    'status'               => 'confirmed',
                    'slot_key'             => Consultation::slotKey($profile->id, $start),
                ])->setRelation('profile', $profile);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['start' => 'That time is no longer free — someone may have just booked it. Please pick another.']);
        }

        $client->notify(new ConsultationUpdate($consultation, 'confirmed', $profile->user->name));
        $profile->user->notify(new ConsultationUpdate($consultation, 'booked', $client->name));

        return $consultation;
    }

    /** Either side cancels before the start; the time becomes free again and the other side is told. */
    public function cancel(Consultation $consultation, User $by, ?string $reason): void
    {
        if (! $consultation->isUpcoming()) {
            throw ValidationException::withMessages(['reason' => 'Only upcoming sessions can be cancelled.']);
        }

        $consultation->update([
            'status' => 'cancelled', 'slot_key' => null, 'cancelled_by' => $by->id, 'cancel_reason' => $reason, 'cancelled_at' => now(),
        ]);

        $other = $by->id === $consultation->client_id ? $consultation->profile->user : $consultation->client;
        $other?->notify(new ConsultationUpdate($consultation, 'cancelled', $by->name));
    }

    /** The consultant marks a session that has started as done. */
    public function complete(Consultation $consultation): void
    {
        if ($consultation->status !== 'confirmed' || $consultation->starts_at->isFuture()) {
            throw ValidationException::withMessages(['status' => 'A session can be marked as done once it has started.']);
        }

        $consultation->update(['status' => 'completed', 'slot_key' => null]);
    }
}
