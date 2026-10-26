<?php

namespace App\Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A respondent's answer to one field. Multiple-choice answers are stored as a JSON list. */
class FormAnswer extends Model
{
    public $timestamps = false;

    protected $fillable = ['form_field_id', 'value'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'form_response_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    /** The answer as text, e.g. for the responses table and CSV ("A; B" for several choices). */
    public function display(): string
    {
        $decoded = json_decode((string) $this->value, true);

        return is_array($decoded) ? implode('; ', $decoded) : (string) $this->value;
    }
}
