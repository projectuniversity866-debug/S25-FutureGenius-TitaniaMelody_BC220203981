<?php

namespace App\Modules\Careers\Http\Requests;

use App\Modules\Careers\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Creating an organization profile, or editing one. */
class OrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization
            ? $this->user()->can('update', $organization)
            : $this->user()->can('create', Organization::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:120'],
            'tagline'  => ['nullable', 'string', 'max:160'],
            'about'    => ['nullable', 'string', 'max:5000'],
            'website'  => ['nullable', 'url:http,https', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:80'],
            'size'     => ['nullable', Rule::in(Organization::SIZES)],
        ];
    }
}
