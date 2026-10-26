<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A request to be shown as a verified expert (roadmap 2.1). */
class ExpertVerification extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = ['field', 'credentials', 'evidence_url', 'document_path', 'status', 'review_note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
