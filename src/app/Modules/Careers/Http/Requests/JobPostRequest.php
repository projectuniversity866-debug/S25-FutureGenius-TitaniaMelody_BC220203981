<?php

namespace App\Modules\Careers\Http\Requests;

use App\Modules\Careers\Models\JobPost;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'title'        => ['required', 'string', 'min:5', 'max:255'],
            'company'      => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:job,internship'],
            'work_mode'    => ['required', 'in:onsite,remote,hybrid'],
            'location'     => ['nullable', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:20'],
            'requirements' => ['nullable', 'string'],
            'salary_min'   => ['nullable', 'numeric', 'min:0'],
            'salary_max'   => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'deadline'     => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
