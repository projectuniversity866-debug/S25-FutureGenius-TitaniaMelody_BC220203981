<?php

namespace App\Modules\Platform\Http\Requests;

use App\Modules\Platform\Models\Form;
use Illuminate\Foundation\Http\FormRequest;

/** A form's title, description and response settings. */
class FormSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('form');

        return $form instanceof Form
            ? $this->user()->can('update', $form)
            : $this->user()->can('create', Form::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'                 => ['required', 'string', 'min:3', 'max:255'],
            'description'           => ['nullable', 'string', 'max:2000'],
            'requires_login'        => ['boolean'],
            'one_response_per_user' => ['boolean'],
            'confirmation_message'  => ['nullable', 'string', 'max:500'],
            'closes_at'             => ['nullable', 'date', 'after:now'],
        ];
    }

    /** Unticked checkboxes are not sent. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_login'        => $this->boolean('requires_login'),
            'one_response_per_user' => $this->boolean('one_response_per_user'),
        ]);
    }
}
