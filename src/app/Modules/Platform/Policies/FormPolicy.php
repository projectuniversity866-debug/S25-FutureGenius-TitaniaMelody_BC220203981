<?php

namespace App\Modules\Platform\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\Form;
use Illuminate\Auth\Access\Response;

class FormPolicy
{
    /** Form creators hold the forms.create permission (admins and experts by default). */
    public function create(User $user): bool
    {
        return $user->can('forms.create');
    }

    /** The creator — or a moderator — edits a form and reads its responses. */
    public function update(User $user, Form $form): bool
    {
        return $user->id === $form->user_id || $user->canModerate();
    }

    public function delete(User $user, Form $form): bool
    {
        return $this->update($user, $form);
    }

    /** Respondents' answers are private to the creator and moderators. */
    public function viewResponses(User $user, Form $form): bool
    {
        return $this->update($user, $form);
    }

    /** Anyone may open a form that has left draft; a draft is a preview for its editors only. */
    public function fill(?User $user, Form $form): Response
    {
        return $form->status !== 'draft' || ($user && $this->update($user, $form))
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
