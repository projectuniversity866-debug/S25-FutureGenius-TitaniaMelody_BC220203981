<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'avatar_path', 'headline', 'bio', 'skills',
        'organization', 'location', 'website', 'linkedin', 'github',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Skills are stored comma separated; expose them as a clean array. */
    public function skillsArray(): array
    {
        if (blank($this->skills)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->skills))));
    }
}
