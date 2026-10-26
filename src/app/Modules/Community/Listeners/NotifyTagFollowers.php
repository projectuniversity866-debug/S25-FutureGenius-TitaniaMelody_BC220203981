<?php

namespace App\Modules\Community\Listeners;

use App\Modules\Community\Events\QuestionAsked;
use App\Modules\Community\Notifications\NewQuestionInFollowedTag;

/**
 * Everyone following one of a new question's tags hears about it once,
 * however many of their tags it carries. Never its author; never a
 * deactivated account.
 */
class NotifyTagFollowers
{
    public function handle(QuestionAsked $event): void
    {
        $question = $event->question->loadMissing([
            'user',
            'tags.followers' => fn ($query) => $query->where('users.is_active', true),
        ]);

        $recipients = [];
        foreach ($question->tags as $tag) {
            foreach ($tag->followers as $follower) {
                if ($follower->id !== $question->user_id) {
                    $recipients[$follower->id]['user'] = $follower;
                    $recipients[$follower->id]['tags'][] = $tag->name;
                }
            }
        }

        foreach ($recipients as ['user' => $follower, 'tags' => $tags]) {
            $follower->notify(new NewQuestionInFollowedTag($question, $tags));
        }
    }
}
