<?php

namespace App\Modules\Consulting\Policies;

use App\Modules\Consulting\Models\Proposal;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A proposal belongs to its consultant; the client sees it once it is sent,
 * and decides on it if they are an AQDesk member. For a client outside
 * AQDesk the consultant records their answer (roadmap 3.14).
 */
class ProposalPolicy
{
    public function view(User $user, Proposal $proposal): Response
    {
        return $this->isConsultant($user, $proposal) || ($this->isClient($user, $proposal) && $proposal->status !== 'draft')
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Edit or delete: the consultant, while it is a draft. */
    public function update(User $user, Proposal $proposal): bool
    {
        return $this->isConsultant($user, $proposal) && $proposal->status === 'draft';
    }

    public function send(User $user, Proposal $proposal): bool
    {
        return $this->isConsultant($user, $proposal) && $proposal->canMoveTo('sent');
    }

    /** Accept or reject: the member client, or the consultant for a client outside AQDesk. */
    public function decide(User $user, Proposal $proposal): bool
    {
        if ($proposal->status !== 'sent' || $proposal->isExpired()) {
            return false;
        }

        return $proposal->client->user_id ? $this->isClient($user, $proposal) : $this->isConsultant($user, $proposal);
    }

    public function complete(User $user, Proposal $proposal): bool
    {
        return $this->isConsultant($user, $proposal) && $proposal->canMoveTo('completed');
    }

    private function isConsultant(User $user, Proposal $proposal): bool
    {
        return $user->id === $proposal->profile->user_id;
    }

    private function isClient(User $user, Proposal $proposal): bool
    {
        return $proposal->client->user_id !== null && $user->id === $proposal->client->user_id;
    }
}
