<?php

namespace App\Modules\Platform\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Files that must never be reachable by URL (CVs, project files). They live
 * on the "local" disk (storage/app/private) and are only ever sent through
 * a controller action that has checked a policy first.
 */
class PrivateFiles
{
    public const DISK = 'local';

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $path !== null && Storage::disk(self::DISK)->exists($path);
    }

    /** Send the file as a download with a friendly name; call only after authorising. */
    public function download(string $path, string $name): StreamedResponse
    {
        return Storage::disk(self::DISK)->download($path, $name);
    }
}
