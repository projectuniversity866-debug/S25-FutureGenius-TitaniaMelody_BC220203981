<?php

namespace App\Modules\Careers\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ApplyRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('apply', $this->route('job'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cover_letter' => ['required', 'string', 'min:30', 'max:3000'],
            'resume'       => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
        ];
    }
}
