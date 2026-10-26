<?php

namespace App\Modules\Projects\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The owner invites someone, or changes a member's role. */
class ProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('project'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => [$this->isMethod('post') ? 'required' : 'prohibited', 'email', 'max:255'],
            'role'  => ['required', 'in:collaborator,viewer'],
        ];
    }
}
