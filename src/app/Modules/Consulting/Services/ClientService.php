<?php

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Consulting\Models\ConsultingClientNote;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/** A consultant's client list and private notes (FR6 "Client CRM", roadmap 3.13). */
class ClientService
{
    /** The record for a member who books: created on the first booking, a lead becomes active. */
    public function forMember(ConsultantProfile $profile, User $member): ConsultingClient
    {
        $client = $profile->clients()->firstOrCreate(['user_id' => $member->id], [
            'name' => $member->name, 'email' => $member->email, 'status' => 'active', 'source' => 'booking',
        ]);
        if ($client->status !== 'active') {
            $client->update(['status' => 'active']);
        }

        return $client;
    }

    /**
     * Someone added by hand; when the email belongs to an AQDesk member the
     * record is linked to them (so their future bookings land here too).
     *
     * @param  array<string, mixed>  $data  validated ConsultingClientRequest data
     */
    public function add(ConsultantProfile $profile, array $data): ConsultingClient
    {
        $member = filled($data['email'] ?? null) ? User::whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])->first() : null;
        if ($member && $profile->clients()->where('user_id', $member->id)->exists()) {
            throw ValidationException::withMessages(['email' => $member->name.' is already one of your clients.']);
        }
        if ($member?->id === $profile->user_id) {
            throw ValidationException::withMessages(['email' => 'You cannot add yourself as a client.']);
        }

        return $profile->clients()->create($data + ['user_id' => $member?->id, 'source' => 'manual']);
    }

    public function update(ConsultingClient $client, array $data): ConsultingClient
    {
        // A member's name and email come from their account.
        $client->update($client->user_id ? array_diff_key($data, array_flip(['name', 'email'])) : $data);

        return $client;
    }

    /** Sessions stay (the consultant's calendar), they just lose the link to this record. */
    public function delete(ConsultingClient $client): void
    {
        $client->delete();
    }

    public function addNote(ConsultingClient $client, string $body): ConsultingClientNote
    {
        $note = $client->notes()->create(['body' => $body]);
        $client->touch();

        return $note;
    }

    public function deleteNote(ConsultingClientNote $note): void
    {
        $note->delete();
    }
}
