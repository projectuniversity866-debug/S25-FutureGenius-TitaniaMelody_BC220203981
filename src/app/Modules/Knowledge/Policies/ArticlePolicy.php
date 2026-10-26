<?php

namespace App\Modules\Knowledge\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Services\ExpertVerificationService;
use Illuminate\Auth\Access\Response;

class ArticlePolicy
{
    public function __construct(private ExpertVerificationService $verification) {}

    /** Expert knowledge sharing: verified experts write; moderators may too. */
    public function create(User $user): Response
    {
        return $this->verification->isVerified($user) || $user->canModerate()
            ? Response::allow()
            : Response::deny('Only verified experts can write for the Knowledge Hub. Apply for verification first.');
    }

    /** Drafts are visible only to their author and moderators. */
    public function view(?User $user, Article $article): Response
    {
        return $article->isPublished() || ($user && ($user->id === $article->user_id || $user->canModerate()))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Article $article): bool
    {
        return $user->id === $article->user_id || $user->canModerate();
    }

    public function delete(User $user, Article $article): bool
    {
        return $this->update($user, $article);
    }
}
