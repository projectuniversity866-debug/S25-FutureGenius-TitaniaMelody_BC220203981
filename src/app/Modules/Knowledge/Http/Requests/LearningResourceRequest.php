<?php

namespace App\Modules\Knowledge\Http\Requests;

use App\Modules\Knowledge\Models\LearningResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Adding a resource to the Learning Hub library, or editing one. */
class LearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('resource');

        return $resource instanceof LearningResource
            ? $this->user()->can('update', $resource)
            : $this->user()->can('create', LearningResource::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:5', 'max:255'],
            'kind'        => ['required', Rule::in(array_keys(LearningResource::KINDS))],
            'level'       => ['required', Rule::in(array_keys(LearningResource::LEVELS))],
            'url'         => ['required', 'url:http,https', 'max:255'],
            'provider'    => ['nullable', 'string', 'max:80'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'minutes'     => ['nullable', 'integer', 'min:1', 'max:10000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_free'     => ['boolean'],
        ];
    }

    /** An unticked checkbox is not sent; treat it as "paid". */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_free' => $this->boolean('is_free')]);
    }
}
