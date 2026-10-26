<?php

namespace App\Modules\Projects\Http\Requests;

use App\Modules\Projects\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Creating a project space, or editing its settings. */
class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return ! $project instanceof Project || $this->user()->can('update', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility'  => ['required', Rule::in(array_keys(Project::VISIBILITY))],
        ];
    }
}
