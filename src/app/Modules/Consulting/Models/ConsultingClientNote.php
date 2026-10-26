<?php

namespace App\Modules\Consulting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A consultant's private, dated note about a client. Never shown to the client. */
class ConsultingClientNote extends Model
{
    protected $fillable = ['body'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ConsultingClient::class, 'consulting_client_id');
    }
}
