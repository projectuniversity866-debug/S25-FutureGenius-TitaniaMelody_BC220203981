<?php

namespace App\Modules\Governance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Deleting your own account: confirmed with your password and a tick. */
class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // your own account (route middleware: auth)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
            'confirm'  => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.current_password' => 'That password is not correct.',
            'confirm.accepted'          => 'Tick the box to confirm you understand this cannot be undone.',
        ];
    }
}
