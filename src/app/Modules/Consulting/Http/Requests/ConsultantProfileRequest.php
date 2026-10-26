<?php

namespace App\Modules\Consulting\Http\Requests;

use App\Modules\Consulting\Models\ConsultantProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Your consultant profile and booking settings. */
class ConsultantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('offer', ConsultantProfile::class);
    }

    /** "Laravel, Testing , ,APIs" → ["Laravel", "Testing", "APIs"]. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('expertise'))) {
            $this->merge(['expertise' => array_values(array_unique(array_filter(array_map('trim', explode(',', $this->input('expertise'))))))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'headline'        => ['required', 'string', 'min:10', 'max:150'],
            'bio'             => ['nullable', 'string', 'max:5000'],
            'expertise'       => ['nullable', 'array', 'max:8'],
            'expertise.*'     => ['string', 'min:2', 'max:40'],
            'session_minutes' => ['required', 'integer', Rule::in(ConsultantProfile::SESSION_LENGTHS)],
            'price_pkr'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'timezone'        => ['required', Rule::in(array_keys(ConsultantProfile::TIMEZONES))],
            'notice_hours'    => ['required', 'integer', 'min:0', 'max:336'],
            'window_days'     => ['required', 'integer', 'min:1', 'max:90'],
            'is_accepting'    => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['expertise.max' => 'List up to 8 topics.', 'expertise.*.max' => 'Keep each topic under 40 characters.'];
    }

    /** @return array<string, mixed> */
    public function profileData(): array
    {
        return array_merge($this->validated(), [
            'price_pkr'    => $this->validated('price_pkr') ?: null,
            'is_accepting' => $this->boolean('is_accepting'),
            'expertise'    => $this->validated('expertise') ?: [],
        ]);
    }
}
