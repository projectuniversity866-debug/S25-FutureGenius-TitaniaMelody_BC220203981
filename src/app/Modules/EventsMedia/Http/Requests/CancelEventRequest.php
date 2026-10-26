<?php

namespace App\Modules\EventsMedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Cancelling an event; the reason is sent to everyone registered. */
class CancelEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('event'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:500']];
    }
}
