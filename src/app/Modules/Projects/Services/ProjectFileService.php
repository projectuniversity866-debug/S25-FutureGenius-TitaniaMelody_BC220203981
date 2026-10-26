<?php

namespace App\Modules\Projects\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\PrivateFiles;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\Projects\Models\ProjectFileVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FR3 "Share files" (roadmap 3.3). Files live on the private disk and are
 * only sent through a controller after a policy check. A new upload of the
 * same file becomes the next version; earlier versions stay downloadable.
 */
class ProjectFileService
{
    public function __construct(private PrivateFiles $files) {}

    public function upload(Project $project, User $uploader, UploadedFile $upload, ?string $name, ?string $note): ProjectFile
    {
        return DB::transaction(function () use ($project, $uploader, $upload, $name, $note) {
            $file = $project->files()->create([
                'user_id' => $uploader->id,
                'name'    => filled($name) ? $name : $upload->getClientOriginalName(),
            ]);
            $this->addVersion($file, $uploader, $upload, $note);

            return $file;
        });
    }

    public function addVersion(ProjectFile $file, User $uploader, UploadedFile $upload, ?string $note): ProjectFileVersion
    {
        return DB::transaction(function () use ($file, $uploader, $upload, $note) {
            $version = $file->versions()->create([
                'user_id'       => $uploader->id,
                'number'        => $file->versions_count + 1,
                'path'          => $this->files->store($upload, 'projects/'.$file->project_id),
                'original_name' => $upload->getClientOriginalName(),
                'mime_type'     => $upload->getMimeType(),
                'size'          => $upload->getSize(),
                'note'          => $note,
            ]);
            $file->increment('versions_count');
            $file->touch();

            return $version;
        });
    }

    /** Stream one version as "v2 - report.pdf"; authorise before calling. */
    public function download(ProjectFileVersion $version): StreamedResponse
    {
        abort_unless($this->files->exists($version->path), 404);

        return $this->files->download($version->path, 'v'.$version->number.' - '.$version->original_name);
    }

    /** Removes every version's stored file too. */
    public function delete(ProjectFile $file): void
    {
        $file->versions()->get()->each(fn (ProjectFileVersion $v) => $this->files->delete($v->path));
        $file->delete();
    }

    /** Before a project is deleted: its stored files go with it. */
    public function deleteAllFor(Project $project): void
    {
        $project->files()->get()->each(fn (ProjectFile $file) => $this->delete($file));
    }
}
