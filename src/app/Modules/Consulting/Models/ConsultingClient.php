<?php

namespace App\Modules\Consulting\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One of a consultant's clients, with private notes (FR6 "Client CRM", roadmap 3.13). */
class ConsultingClient extends Model
{
    /** status => [label, icon, hint] */
    public const STATUSES = [
        'lead'   => ['Lead', 'lightbulb', 'Interested, no session yet'],
        'active' => ['Active', 'person-check', 'Working together now'],
        'past'   => ['Past', 'archive', 'Finished working together'],
    ];

    protected $fillable = ['user_id', 'name', 'email', 'phone', 'organization', 'status', 'source'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'consultant_profile_id');
    }

    /** The AQDesk member behind this client, if any. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ConsultingClientNote::class)->latest()->orderByDesc('id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class)->orderByDesc('starts_at');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class)->latest('updated_at');
    }
}
