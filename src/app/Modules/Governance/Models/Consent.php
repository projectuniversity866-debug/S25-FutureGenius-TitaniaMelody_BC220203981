<?php

namespace App\Modules\Governance\Models;

use Illuminate\Database\Eloquent\Model;

/** A member's acceptance of one version of the terms and privacy policy (roadmap 2.6). */
class Consent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'version', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
