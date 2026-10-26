<?php

namespace App\Modules\Careers\Http\Requests;

use App\Modules\Careers\Models\JobPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A job alert: the same three filters as the opportunities board. */
class JobAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any signed-in member (route middleware)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'keywords'  => ['nullable', 'string', 'max:120'],
            'type'      => ['nullable', Rule::in(array_keys(JobPost::TYPES))],
            'work_mode' => ['nullable', 'in:onsite,remote,hybrid'],
        ];
    }
}
