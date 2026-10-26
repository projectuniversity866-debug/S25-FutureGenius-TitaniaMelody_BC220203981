<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Upvote answers" from FR2. Questions and answers share one polymorphic
 * votes table, so there is a single place to reason about voting. Every
 * change is mirrored in the reputation ledger.
 */
class VoteService
{
    public const UP   = 1;
    public const DOWN = -1;

    public function __construct(private ReputationService $reputation) {}

    /**
     * Voting the same way again removes the vote; voting the other way
     * switches it.
     *
     * @throws ValidationException for own posts, or a downvote without the privilege
     */
    public function cast(User $voter, Question|Answer $votable, int $value): void
    {
        if ($votable->user_id === $voter->id) {
            throw ValidationException::withMessages(['vote' => 'You cannot vote on your own post.']);
        }

        $existing = $votable->votes()->where('user_id', $voter->id)->first();
        $removing = $existing && $existing->value === $value;

        if ($value === self::DOWN && ! $removing && ! $this->reputation->has($voter, 'vote.down')) {
            throw ValidationException::withMessages(['vote' => 'You need '
                .ReputationService::PRIVILEGES['vote.down']['points'].' reputation to downvote. Upvoting is open to everyone.']);
        }

        DB::transaction(function () use ($existing, $removing, $votable, $voter, $value) {
            if ($removing) {
                $this->reputation->forgetVote($existing);
                $existing->delete();
            } elseif ($existing) {
                $existing->update(['value' => $value]);
                $this->reputation->recordVote($existing->setRelation('votable', $votable));
            } else {
                $vote = $votable->votes()->create(['user_id' => $voter->id, 'value' => $value]);
                $this->reputation->recordVote($vote->setRelation('votable', $votable));
            }
        });
    }
}
