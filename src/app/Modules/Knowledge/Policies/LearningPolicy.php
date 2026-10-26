<?php

namespace App\Modules\Knowledge\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningResource;
use App\Modules\Knowledge\Services\ExpertVerificationService;
use Illuminate\Auth\Access\Response;

/** Learning resources and paths (roadmap 2.3): curated like Knowledge Hub articles. */
class LearningPolicy
{
    public function __construct(private ExpertVerificationService $verification) {}

    /** Verified experts curate; moderators may too. */
    public function create(User $user): Response
    {
        return $this->verification->isVerified($user) || $user->canModerate()
            ? Response::allow()
            : Response::deny('Only verified experts can curate the Learning Hub. Apply for verification first.');
    }

    /** Draft paths are visible only to their curator and moderators. */
    public function view(?User $user, LearningPath $path): Response
    {
        return $path->isPublished() || ($user && ($user->id === $path->user_id || $user->canModerate()))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, LearningResource|LearningPath $item): bool
    {
        return $user->id === $item->user_id || $user->canModerate();
    }

    public function delete(User $user, LearningResource|LearningPath $item): bool
    {
        return $this->update($user, $item);
    }
}
