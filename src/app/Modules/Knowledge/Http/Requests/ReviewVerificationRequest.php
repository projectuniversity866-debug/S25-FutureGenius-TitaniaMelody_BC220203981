<?php

namespace App\Modules\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** An administrator's decision. A rejection always says why. */
class ReviewVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('verification'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision'    => ['required', 'in:approve,reject'],
            'review_note' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['review_note.required_if' => 'Explain the rejection so the applicant knows what to improve.'];
    }
}
