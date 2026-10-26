<?php

namespace App\Modules\Consulting\Http\Requests;

use App\Modules\Consulting\Models\ConsultantProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A weekly block of hours, in the consultant's own time zone. */
class AvailabilityRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('offer', ConsultantProfile::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'weekday' => ['required', 'integer', Rule::in(array_keys(ConsultantProfile::WEEKDAYS))],
            'starts'  => ['required', 'date_format:H:i'],
            'ends'    => ['required', 'date_format:H:i', 'after:starts'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['starts' => 'start time', 'ends' => 'end time'];
    }
}
