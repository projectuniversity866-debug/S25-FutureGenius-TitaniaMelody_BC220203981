<?php

namespace App\Modules\Projects\Notifications;

use App\Modules\Platform\Notifications\AppNotification;
use App\Modules\Projects\Models\ProjectThread;

/** A new feedback thread in one of your projects, or a reply in a thread you take part in. */
class FeedbackPosted extends AppNotification
{
    public function __construct(private ProjectThread $thread, private string $who, private bool $isReply) {}

    protected function title(): string
    {
        return $this->isReply ? 'New reply to “'.$this->thread->title.'”' : 'New feedback in '.$this->thread->project->name;
    }

    protected function body(): string
    {
        return $this->isReply
            ? $this->who.' replied in “'.$this->thread->title.'” ('.$this->thread->project->name.').'
            : $this->who.' started “'.$this->thread->title.'”'.($this->thread->file ? ' about '.$this->thread->file->name : '').'.';
    }

    protected function url(): string
    {
        return route('projects.threads.show', [$this->thread->project, $this->thread]).($this->isReply ? '#replies' : '');
    }

    protected function icon(): string
    {
        return 'chat-left-text';
    }

    protected function category(): string
    {
        return 'projects';
    }
}
