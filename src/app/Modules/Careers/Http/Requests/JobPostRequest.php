<?php

namespace App\Modules\Careers\Http\Requests;

use App\Modules\Careers\Models\JobPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Publishing a new opportunity, or editing an existing one. */
class JobPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof JobPost
            ? $this->user()->can('update', $job)
            : $this->user()->can('create', JobPost::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // An opportunity can only be published under its poster's own organizations.
        $job = $this->route('job');
        $posterId = $job instanceof JobPost ? $job->user_id : $this->user()->id;

        return [
            'title'           => ['required', 'string', 'min:5', 'max:255'],
            'organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->where('user_id', $posterId)],
            'company'         => ['required_without:organization_id', 'nullable', 'string', 'max:255'],
            'type'            => ['required', Rule::in(array_keys(JobPost::TYPES))],
            'work_mode'       => ['required', 'in:onsite,remote,hybrid'],
            'location'        => ['nullable', 'string', 'max:255'],
            'description'     => ['required', 'string', 'min:20'],
            'requirements'    => ['nullable', 'string'],
            'eligibility'     => ['nullable', 'string', 'max:5000'],
            'salary_min'      => ['nullable', 'numeric', 'min:0'],
            'salary_max'      => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'deadline'        => ['nullable', 'date', 'after_or_equal:today'],
            'starts_on'       => ['nullable', 'date'],
            'ends_on'         => ['nullable', 'date', 'after_or_equal:starts_on'],
            'external_url'    => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'organization_id.exists'           => 'Choose one of your own organizations.',
            'company.required_without'         => 'Enter the organization name, or choose one of your organizations.',
            'ends_on.after_or_equal'           => 'The end date cannot be before the start date.',
        ];
    }
}
