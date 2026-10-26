<?php

namespace App\Modules\Careers\Http\Requests;

use App\Modules\Careers\Models\ApplicationNote;
use Illuminate\Foundation\Http\FormRequest;

/** A reviewer's private note on an application. */
class ApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('application'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:'.ApplicationNote::MAX_LENGTH],
        ];
    }
}
