<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Tag;
use App\Modules\Identity\Models\User;

/**
 * Following tags (roadmap 1.10): followers are notified about new questions
 * with the tag and can filter the questions list to their tags.
 */
class TagFollowService
{
    public function follow(User $user, Tag $tag): void
    {
        $tag->followers()->syncWithoutDetaching([$user->id]);
    }

    public function unfollow(User $user, Tag $tag): void
    {
        $tag->followers()->detach($user->id);
    }
}
