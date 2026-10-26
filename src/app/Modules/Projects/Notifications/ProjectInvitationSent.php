<?php

namespace App\Modules\Projects\Notifications;

use App\Modules\Platform\Notifications\AppNotification;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectInvitation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The invitation itself (roadmap 3.2): in-app and by email to a member, or
 * by email only to an address that has no AQDesk account yet.
 */
class ProjectInvitationSent extends AppNotification
{
    public function __construct(private ProjectInvitation $invitation, private string $token) {}

    protected function title(): string
    {
        return 'Invitation to join a project';
    }

    protected function body(): string
    {
        return ($this->invitation->inviter?->name ?? 'A member').' invited you to “'.$this->invitation->project->name.'” as '
            .mb_strtolower(Project::ROLES[$this->invitation->role][0]).'. The invitation is valid for '.ProjectInvitation::VALID_DAYS.' days.';
    }

    protected function url(): string
    {
        return route('invitations.show', $this->token);
    }

    protected function icon(): string
    {
        return 'envelope-open';
    }

    /** Someone without an account can only be emailed. */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' — AQDesk')
            ->greeting('Hello'.(isset($notifiable->name) ? ' '.$notifiable->name : '').',')
            ->line($this->body())
            ->action('View the invitation', $this->url())
            ->line('If you were not expecting this, you can ignore this email or decline the invitation.');
    }
}
