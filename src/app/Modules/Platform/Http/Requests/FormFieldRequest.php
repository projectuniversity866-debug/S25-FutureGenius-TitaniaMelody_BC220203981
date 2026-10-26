<?php

namespace App\Modules\Platform\Http\Requests;

use App\Modules\Platform\Models\FormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Adding a field to a form, or editing one. */
class FormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('form') ?? $this->route('field')?->form;

        return $form !== null && $this->user()->can('update', $form);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type'        => ['required', Rule::in(array_keys(FormField::TYPES))],
            'label'       => ['required', 'string', 'max:255'],
            'help'        => ['nullable', 'string', 'max:300'],
            'options'     => ['nullable', 'required_if:type,'.implode(',', FormField::CHOICE_TYPES), 'string', 'max:2000'],
            'is_required' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['options.required_if' => 'List the choices, one per line.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_required' => $this->boolean('is_required')]);
    }
}
