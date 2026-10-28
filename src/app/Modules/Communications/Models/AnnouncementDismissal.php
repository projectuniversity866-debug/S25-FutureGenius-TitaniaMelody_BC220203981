<?php

namespace App\Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;

/** A member closed an announcement banner; it stays closed on every device (roadmap 3.18). */
class AnnouncementDismissal extends Model
{
    protected $fillable = ['announcement_id', 'user_id'];
}
