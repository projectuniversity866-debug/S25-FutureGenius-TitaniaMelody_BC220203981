<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;

/**
 * Saving questions to read later (roadmap 1.10). Saving twice, or removing
 * something that was never saved, changes nothing.
 */
class BookmarkService
{
    public function save(User $user, Question $question): void
    {
        $question->bookmarks()->firstOrCreate(['user_id' => $user->id]);
    }

    public function remove(User $user, Question $question): void
    {
        $question->bookmarks()->where('user_id', $user->id)->delete();
    }
}
