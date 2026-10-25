<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Services\RegistrationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Password::min(8)],
            'role_slug' => ['required', 'string', Rule::in(RegistrationService::SELF_SERVICE_ROLES)],
            'headline'  => ['nullable', 'string', 'max:255'],
        ];
    }
}
