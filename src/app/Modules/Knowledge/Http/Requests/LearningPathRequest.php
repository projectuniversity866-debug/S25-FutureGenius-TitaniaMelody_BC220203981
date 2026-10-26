<?php

namespace App\Modules\Knowledge\Http\Requests;

use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A learning path's title, summary, topic and level. */
class LearningPathRequest extends FormRequest
{
    public function authorize(): bool
    {
        $path = $this->route('path');

        return $path instanceof LearningPath
            ? $this->user()->can('update', $path)
            : $this->user()->can('create', LearningPath::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:10', 'max:160'],
            'summary'     => ['required', 'string', 'min:20', 'max:300'],
            'level'       => ['required', Rule::in(array_keys(LearningResource::LEVELS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
