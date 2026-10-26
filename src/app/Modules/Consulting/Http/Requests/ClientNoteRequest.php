<?php

namespace App\Modules\Consulting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A private note on a client. */
class ClientNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('client'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:5000']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => 'note'];
    }
}
