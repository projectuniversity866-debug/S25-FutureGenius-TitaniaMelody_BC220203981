<?php

namespace App\Modules\Projects\Http\Requests;

use App\Modules\Projects\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Adding or editing a task; it can only be given to the project's owner or a collaborator. */
class ProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contribute', $this->route('project'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assignee_id' => ['nullable', 'integer', Rule::in($this->route('project')->workers()->pluck('users.id'))],
            'priority'    => ['required', Rule::in(array_keys(ProjectTask::PRIORITIES))],
            'due_on'      => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['assignee_id.in' => 'Tasks can be given to the owner or a collaborator of this project.'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['assignee_id' => 'assignee', 'due_on' => 'due date'];
    }
}
