<?php

namespace App\Modules\Community\Notifications;

use App\Modules\Community\Models\Comment;
use App\Modules\Platform\Notifications\AppNotification;

/** Tells a post's author that someone commented on it. */
class NewComment extends AppNotification
{
    public function __construct(private Comment $comment) {}

    protected function title(): string
    {
        return 'New comment on your '.($this->comment->commentable_type === 'answer' ? 'answer' : 'question');
    }

    protected function body(): string
    {
        return $this->comment->user->name.': “'.\Illuminate\Support\Str::limit($this->comment->body, 90).'”';
    }

    protected function url(): string
    {
        return route('questions.show', $this->comment->question()).'#comment-'.$this->comment->id;
    }

    protected function category(): string
    {
        return 'answers';
    }

    protected function icon(): string
    {
        return 'chat-dots';
    }
}
