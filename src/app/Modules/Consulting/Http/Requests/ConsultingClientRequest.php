<?php

namespace App\Modules\Consulting\Http\Requests;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\ConsultingClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Adding a client by hand, or editing one. */
class ConsultingClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client
            ? $this->user()->can('manage', $client)
            : $this->user()->can('offer', ConsultantProfile::class) && $this->user()->consultantProfile !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $linked = (bool) $this->route('client')?->user_id;   // a member's name and email come from their account

        return [
            'name'         => [$linked ? 'nullable' : 'required', 'string', 'min:2', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{5,40}$/'],
            'organization' => ['nullable', 'string', 'max:255'],
            'status'       => ['required', Rule::in(array_keys(ConsultingClient::STATUSES))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['phone.regex' => 'Use digits, spaces, + and brackets only.'];
    }
}
