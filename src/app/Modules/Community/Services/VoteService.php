<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * "Upvote answers" from FR2. Questions and answers share one polymorphic
 * votes table, so there is a single place to reason about voting.
 */
class VoteService
{
    public const UP   = 1;
    public const DOWN = -1;

    /**
     * Voting the same way again removes the vote; voting the other way
     * switches it.
     *
     * @throws ValidationException when the user votes on their own post
     */
    public function cast(User $voter, Question|Answer $votable, int $value): void
    {
        if ($votable->user_id === $voter->id) {
            throw ValidationException::withMessages(['vote' => 'You cannot vote on your own post.']);
        }

        $existing = $votable->votes()->where('user_id', $voter->id)->first();

        if ($existing && $existing->value === $value) {
            $existing->delete();
        } elseif ($existing) {
            $existing->update(['value' => $value]);
        } else {
            $votable->votes()->create([
                'user_id' => $voter->id,
                'value'   => $value,
            ]);
        }
    }
}
