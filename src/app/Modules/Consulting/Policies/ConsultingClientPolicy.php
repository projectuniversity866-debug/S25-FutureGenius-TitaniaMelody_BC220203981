<?php

namespace App\Modules\Consulting\Policies;

use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** A client record is its consultant's alone — not even moderators read the private notes (roadmap 3.13). */
class ConsultingClientPolicy
{
    public function manage(User $user, ConsultingClient $client): Response
    {
        return $user->id === $client->profile->user_id ? Response::allow() : Response::denyAsNotFound();
    }
}
