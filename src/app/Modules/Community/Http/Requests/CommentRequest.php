<?php

namespace App\Modules\Community\Http\Requests;

use App\Modules\Community\Models\Comment;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CommentRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', [Comment::class, $this->route('answer') ?? $this->route('question')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:5', 'max:'.Comment::MAX_LENGTH],
        ];
    }
}
