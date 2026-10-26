<?php

namespace App\Modules\Projects\Http\Requests;

use App\Modules\Projects\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Moving a task to another column of the board. */
class ProjectTaskMoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contribute', $this->route('project'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_keys(ProjectTask::STATUSES))]];
    }
}
