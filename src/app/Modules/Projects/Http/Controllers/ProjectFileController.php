<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Http\Requests\ProjectFileRequest;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\Projects\Models\ProjectFileVersion;
use App\Modules\Projects\Services\ProjectFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** FR3 "Share files" with versions (roadmap 3.3). */
class ProjectFileController extends Controller
{
    public function __construct(private ProjectFileService $files) {}

    public function store(ProjectFileRequest $request, Project $project): RedirectResponse
    {
        $file = $this->files->upload($project, $request->user(), $request->file('file'), $request->validated('name'), $request->validated('note'));

        return redirect()->route('projects.files.show', [$project, $file])->with('status', '“'.$file->name.'” uploaded as version 1.');
    }

    /** A file's version history. */
    public function show(Request $request, Project $project, ProjectFile $file): View
    {
        $this->authorize('view', $project);
        abort_unless($file->project_id === $project->id, 404);

        $file->load('versions.uploader', 'creator');

        return view('projects::file', compact('project', 'file'));
    }

    public function storeVersion(ProjectFileRequest $request, Project $project, ProjectFile $file): RedirectResponse
    {
        abort_unless($file->project_id === $project->id, 404);

        $version = $this->files->addVersion($file, $request->user(), $request->file('file'), $request->validated('note'));

        return redirect()->route('projects.files.show', [$project, $file])->with('status', 'Version '.$version->number.' uploaded.');
    }

    public function download(Project $project, ProjectFile $file, ProjectFileVersion $version): StreamedResponse
    {
        $this->authorize('view', $project);
        abort_unless($file->project_id === $project->id && $version->project_file_id === $file->id, 404);

        return $this->files->download($version);
    }

    public function destroy(Project $project, ProjectFile $file): RedirectResponse
    {
        abort_unless($file->project_id === $project->id, 404);
        $this->authorize('deleteFile', [$project, $file]);

        $this->files->delete($file);

        return redirect()->to(route('projects.show', $project).'#files')->with('status', '“'.$file->name.'” and all its versions were deleted.');
    }
}
