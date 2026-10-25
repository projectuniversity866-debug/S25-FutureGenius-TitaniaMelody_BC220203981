<?php

namespace App\Modules\Community\Policies;

use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

class QuestionPolicy
{
    /** The author, or a moderator, may edit a question. */
    public function update(User $user, Question $question): bool
    {
        return $user->id === $question->user_id || $user->canModerate();
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }

    /** Anyone with the questions.post permission may answer, unless the question is closed. */
    public function answer(User $user, Question $question): Response
    {
        if (! $user->hasPermission('questions.post')) {
            return Response::deny('Your role cannot answer questions.');
        }

        return $question->is_closed
            ? Response::deny('This question is closed for new answers.')
            : Response::allow();
    }
}
