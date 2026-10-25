<?php

namespace App\Modules\Community\Http\Requests;

use App\Modules\Community\Services\VoteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('questions.vote');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', Rule::in([VoteService::UP, VoteService::DOWN])],
        ];
    }
}
