<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Http\Requests\ProjectReplyRequest;
use App\Modules\Projects\Http\Requests\ProjectThreadRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectThread;
use App\Modules\Projects\Models\ProjectThreadReply;
use App\Modules\Projects\Services\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** FR3 "Provide feedback": threads inside a project (roadmap 3.4). */
class ProjectThreadController extends Controller
{
    public function __construct(private FeedbackService $feedback) {}

    public function index(Request $request, Project $project): View
    {
        $this->authorize('view', $project);
        $tab = $request->query('tab') === 'resolved' ? 'resolved' : 'open';

        $threads = $project->threads()->where('status', $tab)->with('author', 'file')->paginate(15)->withQueryString();
        $counts = $project->threads()->reorder()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $files = $project->files()->reorder()->orderBy('name')->get(['id', 'name']);

        return view('projects::feedback.index', [
            'project' => $project, 'threads' => $threads, 'tab' => $tab, 'counts' => $counts,
            'files' => $files, 'aboutFile' => $request->integer('file') ?: null,
        ]);
    }

    public function store(ProjectThreadRequest $request, Project $project): RedirectResponse
    {
        $thread = $this->feedback->start($project, $request->user(), $request->validated());

        return redirect()->route('projects.threads.show', [$project, $thread])->with('status', 'Feedback posted. The other members have been told.');
    }

    public function show(Project $project, ProjectThread $thread): View
    {
        $this->authorize('view', $project);
        abort_unless($thread->project_id === $project->id, 404);

        $thread->load('author', 'file', 'resolver', 'replies.author');

        return view('projects::feedback.show', compact('project', 'thread'));
    }

    public function reply(ProjectReplyRequest $request, Project $project, ProjectThread $thread): RedirectResponse
    {
        abort_unless($thread->project_id === $project->id, 404);

        $reply = $this->feedback->reply($thread, $request->user(), $request->validated('body'));

        return redirect()->to(route('projects.threads.show', [$project, $thread]).'#reply-'.$reply->id)->with('status', 'Reply posted.');
    }

    public function resolve(Request $request, Project $project, ProjectThread $thread): RedirectResponse
    {
        abort_unless($thread->project_id === $project->id, 404);
        $this->authorize('manageThread', [$project, $thread]);

        $this->feedback->setResolved($thread, $request->user(), $request->boolean('resolved'));

        return back()->with('status', $thread->isResolved() ? 'Marked as resolved.' : 'Thread reopened.');
    }

    public function destroy(Project $project, ProjectThread $thread): RedirectResponse
    {
        abort_unless($thread->project_id === $project->id, 404);
        $this->authorize('manageThread', [$project, $thread]);

        $this->feedback->deleteThread($thread);

        return redirect()->route('projects.threads.index', $project)->with('status', 'Thread deleted.');
    }

    public function destroyReply(Project $project, ProjectThread $thread, ProjectThreadReply $reply): RedirectResponse
    {
        abort_unless($thread->project_id === $project->id && $reply->project_thread_id === $thread->id, 404);
        $this->authorize('deleteReply', [$project, $reply]);

        $this->feedback->deleteReply($reply);

        return redirect()->to(route('projects.threads.show', [$project, $thread]).'#replies')->with('status', 'Reply deleted.');
    }
}
