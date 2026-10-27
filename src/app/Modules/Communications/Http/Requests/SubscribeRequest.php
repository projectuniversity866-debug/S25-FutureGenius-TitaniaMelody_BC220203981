<?php

namespace App\Modules\Communications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Signing up for the newsletter. */
class SubscribeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email'   => ['required', 'email', 'max:255'],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['consent.accepted' => 'Tick the box to agree to receive the newsletter.'];
    }
}
