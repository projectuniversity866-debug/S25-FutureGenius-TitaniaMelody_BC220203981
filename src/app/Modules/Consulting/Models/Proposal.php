<?php

namespace App\Modules\Consulting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A consultant's proposal to a client (FR6 "Proposal tracking", roadmap 3.14). */
class Proposal extends Model
{
    protected $table = 'consulting_proposals';

    /** status => [label, icon] */
    public const STATUSES = [
        'draft'     => ['Draft', 'pencil-square'],
        'sent'      => ['Sent', 'send'],
        'accepted'  => ['Accepted', 'hand-thumbs-up'],
        'rejected'  => ['Rejected', 'hand-thumbs-down'],
        'completed' => ['Completed', 'check2-circle'],
    ];

    /** from => allowed next statuses */
    public const TRANSITIONS = [
        'draft'    => ['sent'],
        'sent'     => ['accepted', 'rejected'],
        'accepted' => ['completed'],
    ];

    protected $fillable = ['consulting_client_id', 'title', 'body', 'amount_pkr', 'valid_until', 'status', 'response_note', 'sent_at', 'decided_at', 'completed_at'];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'sent_at' => 'datetime', 'decided_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'consultant_profile_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ConsultingClient::class, 'consulting_client_id');
    }

    public function canMoveTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /** A sent proposal can no longer be accepted after its "valid until" day. */
    public function isExpired(): bool
    {
        return $this->status === 'sent' && $this->valid_until !== null && $this->valid_until->endOfDay()->isPast();
    }

    public function amountLabel(): string
    {
        return $this->amount_pkr ? 'PKR '.number_format($this->amount_pkr) : 'Free';
    }
}
