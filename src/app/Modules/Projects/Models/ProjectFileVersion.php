<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One uploaded version of a project file, stored on the private disk. */
class ProjectFileVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'number', 'path', 'original_name', 'mime_type', 'size', 'note'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** "2.4 MB", "830 KB". */
    public function readableSize(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1).' MB'
            : max(1, (int) round($this->size / 1024)).' KB';
    }
}
