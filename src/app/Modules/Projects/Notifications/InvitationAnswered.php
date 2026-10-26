<?php

namespace App\Modules\Projects\Notifications;

use App\Modules\Platform\Notifications\AppNotification;
use App\Modules\Projects\Models\ProjectInvitation;

/** Tells whoever sent an invitation that it was accepted or declined. */
class InvitationAnswered extends AppNotification
{
    public function __construct(private ProjectInvitation $invitation, private string $who, private bool $accepted) {}

    protected function title(): string
    {
        return $this->accepted ? 'Invitation accepted' : 'Invitation declined';
    }

    protected function body(): string
    {
        return $this->who.($this->accepted ? ' joined “' : ' declined to join “').$this->invitation->project->name.'”.';
    }

    protected function url(): string
    {
        return route('projects.show', $this->invitation->project).'#members';
    }

    protected function icon(): string
    {
        return $this->accepted ? 'person-check' : 'person-x';
    }
}
