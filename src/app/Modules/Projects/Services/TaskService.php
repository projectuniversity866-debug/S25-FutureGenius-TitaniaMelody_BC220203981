<?php

namespace App\Modules\Projects\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectTask;
use App\Modules\Projects\Notifications\TaskAssigned;

/**
 * FR3 "Project tracking" (roadmap 3.5): the task board. Whoever is given a
 * task by someone else is notified; finishing a task records when.
 */
class TaskService
{
    /** @param  array<string, mixed>  $data  validated ProjectTaskRequest data */
    public function create(Project $project, User $creator, array $data): ProjectTask
    {
        $task = $project->tasks()->create($data + ['created_by' => $creator->id, 'status' => 'todo']);
        $this->notifyAssignee($task, $creator, null);

        return $task;
    }

    /** @param  array<string, mixed>  $data  validated ProjectTaskRequest data */
    public function update(ProjectTask $task, User $editor, array $data): ProjectTask
    {
        $before = $task->assignee_id;
        $task->update($data);
        $this->notifyAssignee($task, $editor, $before);

        return $task;
    }

    public function move(ProjectTask $task, string $status): ProjectTask
    {
        $task->update([
            'status'       => $status,
            'completed_at' => $status === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        return $task;
    }

    public function delete(ProjectTask $task): void
    {
        $task->delete();
    }

    /** Account deletion and leaving a project: the person's open tasks become unassigned. */
    public function unassign(User $user, ?Project $project = null): void
    {
        ProjectTask::where('assignee_id', $user->id)
            ->when($project, fn ($q) => $q->where('project_id', $project->id))
            ->update(['assignee_id' => null]);
    }

    private function notifyAssignee(ProjectTask $task, User $by, ?int $before): void
    {
        if ($task->assignee_id && $task->assignee_id !== $before && $task->assignee_id !== $by->id) {
            $task->assignee?->notify(new TaskAssigned($task, $by->name));
        }
    }
}
