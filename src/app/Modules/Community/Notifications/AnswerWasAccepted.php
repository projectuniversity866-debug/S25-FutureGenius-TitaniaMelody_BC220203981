<?php

namespace App\Modules\Community\Notifications;

use App\Modules\Community\Models\Answer;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells an answer's author that the asker accepted it. */
class AnswerWasAccepted extends AppNotification
{
    public function __construct(private Answer $answer) {}

    protected function title(): string
    {
        return 'Your answer was accepted';
    }

    protected function body(): string
    {
        return 'Your answer to “'.$this->answer->question->title.'” was marked as the accepted answer.';
    }

    protected function url(): string
    {
        return route('questions.show', $this->answer->question).'#answer-'.$this->answer->id;
    }

    protected function category(): string
    {
        return 'answers';
    }

    protected function icon(): string
    {
        return 'patch-check';
    }
}
