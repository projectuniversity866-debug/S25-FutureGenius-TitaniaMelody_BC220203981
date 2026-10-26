<?php

namespace App\Modules\Governance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A moderator's decision on a report: remove the content, or keep it. */
class ModerationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('content.moderate');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:remove,dismiss'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
