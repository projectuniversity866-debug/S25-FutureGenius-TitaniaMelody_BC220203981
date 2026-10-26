<?php

namespace App\Modules\Consulting\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/** Booking one free slot: the start (an exact instant from the slot list) and what the session is about. */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('consultant'));
    }

    /** A hidden (paused) profile does not exist for anyone else — same 404 as its page. */
    protected function failedAuthorization(): void
    {
        abort(404);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'start'   => ['required', 'string', 'date'],
            'topic'   => ['required', 'string', 'min:5', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['start' => 'time', 'topic' => 'session topic'];
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('start'))->utc();
    }
}
