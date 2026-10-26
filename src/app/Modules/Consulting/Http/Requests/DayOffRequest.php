<?php

namespace App\Modules\Consulting\Http\Requests;

use App\Modules\Consulting\Models\ConsultantProfile;
use Illuminate\Foundation\Http\FormRequest;

/** A day with no sessions (holiday, exams, travel). */
class DayOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('offer', ConsultantProfile::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tz = $this->user()->consultantProfile?->timezone ?? 'Asia/Karachi';

        return [
            'date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now($tz)->toDateString(), 'before:'.now($tz)->addYear()->toDateString()],
            'reason' => ['nullable', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['date.after_or_equal' => 'Pick today or a later day.', 'date.before' => 'Pick a day within the next year.'];
    }
}
