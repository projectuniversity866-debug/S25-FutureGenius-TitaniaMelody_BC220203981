<?php

namespace App\Modules\Community\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** A previous version of a question or answer, saved when it was edited. */
class Revision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'title', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisable(): MorphTo
    {
        return $this->morphTo();
    }
}
