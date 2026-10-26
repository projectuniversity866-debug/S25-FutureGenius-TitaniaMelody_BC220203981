<?php

namespace App\Modules\Consulting\Policies;

use App\Modules\Consulting\Models\Consultation;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** A session belongs to its client and its consultant (FR6, roadmap 3.12). */
class ConsultationPolicy
{
    public function view(User $user, Consultation $consultation): Response
    {
        return $this->isParty($user, $consultation) || $user->canModerate()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function cancel(User $user, Consultation $consultation): bool
    {
        return $this->isParty($user, $consultation) && $consultation->isUpcoming();
    }

    public function complete(User $user, Consultation $consultation): bool
    {
        return $user->id === $consultation->profile->user_id && $consultation->status === 'confirmed' && ! $consultation->starts_at->isFuture();
    }

    private function isParty(User $user, Consultation $consultation): bool
    {
        return $user->id === $consultation->client_id || $user->id === $consultation->profile->user_id;
    }
}
