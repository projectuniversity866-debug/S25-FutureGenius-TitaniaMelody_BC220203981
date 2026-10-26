<?php

namespace App\Modules\Community\Policies;

use App\Modules\Community\Models\Answer;
use App\Modules\Identity\Models\User;

class AnswerPolicy
{
    /** The author, or a moderator, may edit an answer. */
    public function update(User $user, Answer $answer): bool
    {
        return $user->id === $answer->user_id || $user->canModerate();
    }

    public function delete(User $user, Answer $answer): bool
    {
        return $this->update($user, $answer);
    }

    /** The question's author, or a moderator, accepts an answer. */
    public function accept(User $user, Answer $answer): bool
    {
        return $user->id === $answer->question->user_id || $user->canModerate();
    }
}
