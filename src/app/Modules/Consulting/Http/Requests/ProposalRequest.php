<?php

namespace App\Modules\Consulting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Writing a proposal for a client, or editing a draft. */
class ProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proposal = $this->route('proposal');

        return $proposal ? $this->user()->can('update', $proposal) : $this->user()->can('manage', $this->route('client'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:5', 'max:150'],
            'body'        => ['required', 'string', 'min:20', 'max:20000'],
            'amount_pkr'  => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['valid_until.after_or_equal' => 'Choose today or a later day.'];
    }
}
