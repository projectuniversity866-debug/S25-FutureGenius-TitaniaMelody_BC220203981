<?php

namespace App\Modules\Community\Http\Requests;

use App\Modules\Community\Models\Question;
use Illuminate\Foundation\Http\FormRequest;

/** Asking a new question, or editing an existing one. */
class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question instanceof Question
            ? $this->user()->can('update', $question)
            : $this->user()->can('questions.post');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:10', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'body'        => ['required', 'string', 'min:20'],
            'tags'        => ['nullable', 'string', 'max:255'],
        ];
    }
}
