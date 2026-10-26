<?php

namespace App\Modules\EventsMedia\Policies;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** Who may see, organize and register for events (FR4, roadmap 3.6 – 3.7). */
class EventPolicy
{
    /** Published and cancelled events are public; drafts only for their organizer and moderators (404 otherwise). */
    public function view(?User $user, Event $event): Response
    {
        return $event->status !== 'draft' || ($user && $this->update($user, $event))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('events.create');
    }

    /** The organizer, or a moderator. Also covers the attendee list and its export. */
    public function update(User $user, Event $event): bool
    {
        return ($user->id === $event->user_id && $user->hasPermission('events.create')) || $user->canModerate();
    }

    /** Deleting is for drafts and events nobody is registered for; others are cancelled instead. */
    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event) && ! $event->registrations()->where('status', '!=', 'cancelled')->exists();
    }

    /** The joining link is for people with a seat (not the waitlist) and the organizer (roadmap 3.8). */
    public function join(User $user, Event $event): bool
    {
        return $this->update($user, $event)
            || $event->registrations()->where('user_id', $user->id)->where('status', 'confirmed')->exists();
    }

    public function register(User $user, Event $event): bool
    {
        return $event->isRegistrationOpen();
    }
}
