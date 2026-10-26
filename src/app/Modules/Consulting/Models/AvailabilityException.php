<?php

namespace App\Modules\Consulting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A day off: no slots that day, whatever the weekly hours say. */
class AvailabilityException extends Model
{
    protected $fillable = ['date', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'consultant_profile_id');
    }
}
