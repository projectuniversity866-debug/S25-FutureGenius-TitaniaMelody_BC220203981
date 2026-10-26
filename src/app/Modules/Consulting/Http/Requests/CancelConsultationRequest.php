<?php

namespace App\Modules\Consulting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Cancelling a session; the reason goes to the other side. */
class CancelConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('consultation'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:500']];
    }
}
