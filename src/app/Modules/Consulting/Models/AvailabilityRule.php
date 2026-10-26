<?php

namespace App\Modules\Consulting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A weekly block of working hours, e.g. Tuesday 09:00–12:00 in the consultant's time zone. */
class AvailabilityRule extends Model
{
    protected $fillable = ['weekday', 'starts', 'ends'];

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'consultant_profile_id');
    }

    /** Minutes since midnight. */
    public static function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    public function label(): string
    {
        return ConsultantProfile::WEEKDAYS[$this->weekday].' '.$this->starts.'–'.$this->ends;
    }
}
