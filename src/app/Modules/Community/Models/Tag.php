<?php

namespace App\Modules\Community\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    /** Members who get notified about new questions with this tag. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tag_follows')->withTimestamps();
    }

    /** Find a tag by name or create it, keeping slugs unique. */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        return static::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );
    }
}
