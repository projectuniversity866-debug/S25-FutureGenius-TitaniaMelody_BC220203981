<?php

namespace App\Modules\Consulting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Accepting or rejecting a sent proposal. */
class ProposalDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decide', $this->route('proposal'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:accepted,rejected'],
            'note'     => ['nullable', 'string', 'max:500'],
        ];
    }
}
