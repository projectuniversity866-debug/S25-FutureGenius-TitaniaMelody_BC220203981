<?php

namespace App\Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Starting a feedback thread, optionally about one of the project's files. */
class ProjectThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('discuss', $this->route('project'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'min:5', 'max:150'],
            'body'            => ['required', 'string', 'min:5', 'max:10000'],
            'project_file_id' => ['nullable', 'integer', Rule::exists('project_files', 'id')->where('project_id', $this->route('project')->id)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => 'feedback', 'project_file_id' => 'file'];
    }
}
