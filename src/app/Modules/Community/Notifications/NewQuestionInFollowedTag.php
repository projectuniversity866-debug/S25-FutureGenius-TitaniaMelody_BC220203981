<?php

namespace App\Modules\Community\Notifications;

use App\Modules\Community\Models\Question;
use App\Modules\Platform\Notifications\AppNotification;
use Illuminate\Support\Str;

/** Tells a member about a new question in a tag they follow. */
class NewQuestionInFollowedTag extends AppNotification
{
    /** @param  array<int, string>  $tags  the followed tags the question carries */
    public function __construct(private Question $question, private array $tags) {}

    protected function title(): string
    {
        return 'New question tagged '.implode(', ', array_slice($this->tags, 0, 3));
    }

    protected function body(): string
    {
        return $this->question->user->name.' asked “'.Str::limit($this->question->title, 90).'”';
    }

    protected function url(): string
    {
        return route('questions.show', $this->question);
    }

    protected function category(): string
    {
        return 'follows';
    }

    protected function icon(): string
    {
        return 'tags';
    }
}
