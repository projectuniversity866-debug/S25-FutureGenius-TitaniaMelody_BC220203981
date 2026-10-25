<?php

namespace App\Modules\Community\Listeners;

use App\Modules\Community\Events\AnswerAccepted;
use App\Modules\Community\Events\AnswerPosted;
use App\Modules\Community\Notifications\AnswerWasAccepted;
use App\Modules\Community\Notifications\NewAnswer;

/** Nobody is notified about their own action. */
class SendAnswerNotifications
{
    public function handlePosted(AnswerPosted $event): void
    {
        $answer = $event->answer->loadMissing('question.user', 'user');

        if ($answer->question->user_id !== $answer->user_id) {
            $answer->question->user->notify(new NewAnswer($answer));
        }
    }

    public function handleAccepted(AnswerAccepted $event): void
    {
        $answer = $event->answer->loadMissing('question', 'user');

        if ($answer->question->user_id !== $answer->user_id) {
            $answer->user->notify(new AnswerWasAccepted($answer));
        }
    }
}
