<?php

namespace App\Modules\Community\Http\Requests;

use App\Modules\Community\Models\Answer;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** Posting an answer to a question, or editing an existing answer. */
class AnswerRequest extends FormRequest
{
    public function authorize(): Response
    {
        $answer = $this->route('answer');

        return $answer instanceof Answer
            ? Gate::inspect('update', $answer)
            : Gate::inspect('answer', $this->route('question'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:10'],
        ];
    }
}
