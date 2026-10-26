<?php

namespace App\Modules\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Adding a library resource as the next step of a path. */
class LearningStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('path'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'learning_resource_id' => ['required', 'integer', 'exists:learning_resources,id'],
            'note'                 => ['nullable', 'string', 'max:300'],
        ];
    }
}
