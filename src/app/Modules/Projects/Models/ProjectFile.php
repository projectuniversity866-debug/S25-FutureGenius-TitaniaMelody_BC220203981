<?php

namespace App\Modules\Projects\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A shared project file; every upload is kept as a version (roadmap 3.3). */
class ProjectFile extends Model
{
    /** Accepted uploads: documents, slides, sheets, images, archives, code and data. */
    public const MIMES = 'pdf,doc,docx,odt,txt,md,ppt,pptx,odp,xls,xlsx,ods,csv,png,jpg,jpeg,gif,svg,zip,json,sql,php,js,py,java,html,css';

    public const MAX_KB = 10240;

    protected $fillable = ['user_id', 'name', 'versions_count'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Newest first. */
    public function versions(): HasMany
    {
        return $this->hasMany(ProjectFileVersion::class)->orderByDesc('number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProjectFileVersion::class)->ofMany('number', 'max');
    }
}
