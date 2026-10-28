<?php

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Consulting\Models\Proposal;
use App\Modules\Consulting\Notifications\ProposalUpdate;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/** Proposals: Draft → Sent → Accepted / Rejected → Completed (FR6, roadmap 3.14). */
class ProposalService
{
    /** @param  array<string, mixed>  $data  validated ProposalRequest data */
    public function create(ConsultingClient $client, array $data): Proposal
    {
        $proposal = new Proposal($data + ['status' => 'draft']);
        $proposal->consultant_profile_id = $client->consultant_profile_id;
        $proposal->client()->associate($client)->save();

        return $proposal;
    }

    public function update(Proposal $proposal, array $data): Proposal
    {
        $proposal->update($data);

        return $proposal;
    }

    public function delete(Proposal $proposal): void
    {
        $proposal->delete();
    }

    /** A member client is told and can answer in AQDesk; anyone else gets it from the consultant directly. */
    public function send(Proposal $proposal): void
    {
        $this->move($proposal, 'sent', ['sent_at' => now()]);

        $proposal->client->user?->notify(new ProposalUpdate($proposal, 'sent', $proposal->profile->user->name));
    }

    /** Accepting makes a lead an active client; the consultant is told when the client decided. */
    public function decide(Proposal $proposal, User $by, string $decision, ?string $note): void
    {
        if ($proposal->isExpired()) {
            throw ValidationException::withMessages(['decision' => 'This proposal expired on '.$proposal->valid_until->format('d M Y').'.']);
        }

        $this->move($proposal, $decision, ['decided_at' => now(), 'response_note' => $note]);

        if ($decision === 'accepted' && $proposal->client->status === 'lead') {
            $proposal->client->update(['status' => 'active']);
        }
        if ($by->id !== $proposal->profile->user_id) {
            $proposal->profile->user->notify(new ProposalUpdate($proposal, $decision, $by->name));
        }
    }

    public function complete(Proposal $proposal): void
    {
        $this->move($proposal, 'completed', ['completed_at' => now()]);

        $proposal->client->user?->notify(new ProposalUpdate($proposal, 'completed', $proposal->profile->user->name));
    }

    private function move(Proposal $proposal, string $status, array $extra): void
    {
        if (! $proposal->canMoveTo($status)) {
            throw ValidationException::withMessages(['status' => 'A '.strtolower(Proposal::STATUSES[$proposal->status][0]).' proposal cannot become '.strtolower(Proposal::STATUSES[$status][0]).'.']);
        }

        $proposal->update(['status' => $status] + $extra);
    }
}
