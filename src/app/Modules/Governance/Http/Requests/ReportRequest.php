<?php

namespace App\Modules\Governance\Http\Requests;

use App\Modules\Governance\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Reporting content to the moderators. */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any signed-in member with a confirmed email (route middleware)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason'  => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'required_if:reason,other', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['details.required_if' => 'Tell the moderators what is wrong.', 'reason.required' => 'Choose what is wrong with it.'];
    }
}
