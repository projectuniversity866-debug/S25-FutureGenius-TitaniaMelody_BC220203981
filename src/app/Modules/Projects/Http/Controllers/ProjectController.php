<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Http\Requests\ProjectRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** FR3 "Create project spaces" (roadmap 3.1). */
class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    /** My projects, and projects other members opened to everyone signed in. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'discover' ? 'discover' : 'mine';

        $projects = ($tab === 'mine'
                ? $user->projects()->orderByRaw("status = 'archived'")->latest('projects.updated_at')
                : Project::where('visibility', 'members')->where('status', 'active')
                    ->whereDoesntHave('members', fn ($q) => $q->whereKey($user->id))->latest())
            ->with('owner')->withCount('members')->paginate(12)->withQueryString();

        $counts = [
            'mine'     => $user->projects()->count(),
            'discover' => Project::where('visibility', 'members')->where('status', 'active')
                ->whereDoesntHave('members', fn ($q) => $q->whereKey($user->id))->count(),
        ];

        return view('projects::index', compact('projects', 'tab', 'counts'));
    }

    public function create(): View
    {
        return view('projects::create');
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = $this->projects->create($request->user(), $request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project created. Add the people you work with.');
    }

    public function show(Request $request, Project $project): View
    {
        $project->load(['owner', 'members' => fn ($q) => $q->with('profile')->orderByRaw("role = 'owner' desc")->orderBy('name')]);
        $this->authorize('view', $project);

        $files = $project->files()->with('latestVersion.uploader')->get();
        $invitations = $request->user()->can('manageMembers', $project)
            ? $project->invitations()->where('status', 'pending')->latest()->get()
            : collect();

        $taskCounts = $project->tasks()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $threads = $project->threads()->where('status', 'open')->with('author')->limit(3)->get();

        return view('projects::show', [
            'project' => $project, 'role' => $project->roleOf($request->user()), 'files' => $files, 'invitations' => $invitations,
            'taskCounts' => $taskCounts, 'threads' => $threads,
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects::edit', compact('project'));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->projects->update($project, $request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project settings saved.');
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $this->projects->setArchived($project, $request->boolean('archived'));

        return back()->with('status', $project->isArchived()
            ? 'Project archived: it is read-only until you restore it.' : 'Project restored.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $this->projects->delete($project);

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }
}
