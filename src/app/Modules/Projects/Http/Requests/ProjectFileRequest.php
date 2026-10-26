<?php

namespace App\Modules\Projects\Http\Requests;

use App\Modules\Projects\Models\ProjectFile;
use Illuminate\Foundation\Http\FormRequest;

/** Uploading a new file, or a new version of an existing one. */
class ProjectFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user()->can('contribute', $project);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:'.ProjectFile::MIMES, 'max:'.ProjectFile::MAX_KB],
            'name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['file.max' => 'Files can be up to 10 MB.', 'file.mimes' => 'That kind of file cannot be shared here.'];
    }
}
