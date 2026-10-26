<?php

namespace App\Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/** One question on a form. */
class FormField extends Model
{
    /** type => [label, icon] */
    public const TYPES = [
        'short_text'      => ['Short answer', 'input-cursor-text'],
        'long_text'       => ['Paragraph', 'text-paragraph'],
        'email'           => ['Email address', 'envelope'],
        'number'          => ['Number', '123'],
        'date'            => ['Date', 'calendar-date'],
        'single_choice'   => ['One choice (radio buttons)', 'ui-radios'],
        'multiple_choice' => ['Several choices (checkboxes)', 'ui-checks'],
        'dropdown'        => ['Dropdown', 'menu-button-wide'],
        'rating'          => ['Rating 1–5', 'star'],
        'consent'         => ['Consent / agreement', 'check2-square'],
    ];

    /** Types that need a list of options. */
    public const CHOICE_TYPES = ['single_choice', 'multiple_choice', 'dropdown'];

    public const MAX_OPTIONS = 20;

    protected $fillable = ['type', 'label', 'help', 'options', 'is_required', 'position'];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_required' => 'boolean'];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][1] ?? 'question';
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, self::CHOICE_TYPES, true);
    }

    /** Validation rules for a respondent's answer to this field. */
    public function rules(): array
    {
        $presence = $this->is_required ? 'required' : 'nullable';

        return match ($this->type) {
            'short_text'      => [$presence, 'string', 'max:255'],
            'long_text'       => [$presence, 'string', 'max:5000'],
            'email'           => [$presence, 'email', 'max:255'],
            'number'          => [$presence, 'numeric'],
            'date'            => [$presence, 'date'],
            'single_choice', 'dropdown' => [$presence, Rule::in($this->options ?? [])],
            'multiple_choice' => [$presence, 'array'],
            'rating'          => [$presence, 'integer', 'between:1,5'],
            'consent'         => $this->is_required ? ['accepted'] : ['nullable', 'boolean'],
            default           => [$presence],
        };
    }
}
