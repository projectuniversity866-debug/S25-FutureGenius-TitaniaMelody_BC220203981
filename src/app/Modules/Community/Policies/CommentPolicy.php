<?php

namespace App\Modules\Community\Policies;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\ReputationService;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    public function __construct(private ReputationService $reputation) {}

    /**
     * Anyone may comment on their own post and on answers to their own
     * question (to ask for clarification). Commenting elsewhere is a
     * privilege earned with reputation.
     */
    public function create(User $user, Question|Answer $post): Response
    {
        if (! $user->hasPermission('questions.post')) {
            return Response::deny('Your role cannot comment.');
        }

        $question = $post instanceof Answer ? $post->question : $post;

        if ($post->user_id === $user->id || $question->user_id === $user->id) {
            return Response::allow();
        }

        return $this->reputation->has($user, 'comment.others')
            ? Response::allow()
            : Response::deny('You need '.ReputationService::PRIVILEGES['comment.others']['points']
                .' reputation to comment on other people’s posts. Answering questions is the quickest way to earn it.');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->canModerate();
    }
}
