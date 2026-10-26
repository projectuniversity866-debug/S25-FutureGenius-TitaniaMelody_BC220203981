<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Http\Requests\ProjectTaskMoveRequest;
use App\Modules\Projects\Http\Requests\ProjectTaskRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectTask;
use App\Modules\Projects\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** FR3 "Project tracking": the task board (roadmap 3.5). */
class ProjectTaskController extends Controller
{
    public function __construct(private TaskService $tasks) {}

    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);
        $mine = $request->query('show') === 'mine';

        $tasks = $project->tasks()->boardOrder()->with('assignee')
            ->when($mine, fn ($q) => $q->where('assignee_id', $request->user()->id))
            ->get()->groupBy('status');
        $counts = ['all' => $project->tasks()->count(), 'mine' => $project->tasks()->where('assignee_id', $request->user()->id)->count()];

        return view('projects::tasks.index', [
            'project' => $project, 'columns' => $tasks, 'mine' => $mine, 'counts' => $counts,
            'workers' => $project->workers()->orderBy('name')->get(),
        ]);
    }

    public function store(ProjectTaskRequest $request, Project $project): RedirectResponse
    {
        $task = $this->tasks->create($project, $request->user(), $request->validated());

        return redirect()->to(route('projects.tasks.index', $project).'#task-'.$task->id)->with('status', 'Task added to To do.');
    }

    public function edit(Request $request, Project $project, ProjectTask $task): View
    {
        abort_unless($task->project_id === $project->id, 404);
        $this->authorize('contribute', $project);

        return view('projects::tasks.edit', ['project' => $project, 'task' => $task, 'workers' => $project->workers()->orderBy('name')->get()]);
    }

    public function update(ProjectTaskRequest $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $this->tasks->update($task, $request->user(), $request->validated());

        return redirect()->to(route('projects.tasks.index', $project).'#task-'.$task->id)->with('status', 'Task saved.');
    }

    public function move(ProjectTaskMoveRequest $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $this->tasks->move($task, $request->validated('status'));

        return redirect()->to(route('projects.tasks.index', array_filter([$project, 'show' => $request->query('show')])).'#task-'.$task->id)
            ->with('status', '“'.$task->title.'” moved to '.ProjectTask::STATUSES[$task->status][0].'.');
    }

    public function destroy(Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);
        $this->authorize('deleteTask', [$project, $task]);

        $this->tasks->delete($task);

        return redirect()->route('projects.tasks.index', $project)->with('status', 'Task deleted.');
    }
}
