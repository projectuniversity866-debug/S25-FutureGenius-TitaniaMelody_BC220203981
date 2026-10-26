<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;

/**
 * Votes, comments, revisions and bookmarks point at a post through
 * polymorphic columns, which a foreign key cannot cascade. Deleting a post
 * goes through here so nothing is left behind, and the reputation ledger
 * forgets what the post earned (a rebuild would drop it too).
 */
class PostCleaner
{
    public function __construct(private ReputationService $reputation) {}

    public function purge(Question|Answer $post): void
    {
        $this->reputation->forgetPost($post);

        $post->votes()->delete();
        $post->comments()->delete();
        $post->revisions()->delete();

        if ($post instanceof Question) {
            $post->bookmarks()->delete();
        }
    }
}
