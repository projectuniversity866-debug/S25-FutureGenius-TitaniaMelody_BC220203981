<?php

namespace App\Modules\Community\Notifications;

use App\Modules\Community\Models\Answer;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells a question's author that someone answered it. */
class NewAnswer extends AppNotification
{
    public function __construct(private Answer $answer) {}

    protected function title(): string
    {
        return 'New answer to your question';
    }

    protected function body(): string
    {
        return $this->answer->user->name.' answered “'.$this->answer->question->title.'”.';
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
        return 'chat-left-text';
    }
}
