<?php

namespace App\Modules\Projects\Notifications;

use App\Modules\Platform\Notifications\AppNotification;
use App\Modules\Projects\Models\ProjectTask;

/** Someone gave you a task on a project board. */
class TaskAssigned extends AppNotification
{
    public function __construct(private ProjectTask $task, private string $who) {}

    protected function title(): string
    {
        return 'Task assigned to you';
    }

    protected function body(): string
    {
        return $this->who.' assigned you “'.$this->task->title.'” in '.$this->task->project->name
            .($this->task->due_on ? ', due '.$this->task->due_on->format('d M Y') : '').'.';
    }

    protected function url(): string
    {
        return route('projects.tasks.index', $this->task->project).'#task-'.$this->task->id;
    }

    protected function icon(): string
    {
        return 'kanban';
    }

    protected function category(): string
    {
        return 'projects';
    }
}
