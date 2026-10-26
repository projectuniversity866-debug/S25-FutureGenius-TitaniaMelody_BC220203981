<?php

namespace App\Modules\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Applying to be a verified expert. */
class VerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any signed-in member with a confirmed email (route middleware)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'field'        => ['required', 'string', 'min:3', 'max:120'],
            'credentials'  => ['required', 'string', 'min:30', 'max:3000'],
            'evidence_url' => ['nullable', 'url:http,https', 'max:255'],
            'document'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['credentials.min' => 'Tell reviewers a little more: at least :min characters about your qualifications and experience.'];
    }
}
