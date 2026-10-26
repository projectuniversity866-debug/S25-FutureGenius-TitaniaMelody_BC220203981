<?php

namespace App\Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Replying in a project feedback thread. */
class ProjectReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('discuss', $this->route('project'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:10000']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => 'reply'];
    }
}
