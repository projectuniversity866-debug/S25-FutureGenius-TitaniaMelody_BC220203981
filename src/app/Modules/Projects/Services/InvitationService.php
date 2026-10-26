<?php

namespace App\Modules\Projects\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectInvitation;
use App\Modules\Projects\Notifications\InvitationAnswered;
use App\Modules\Projects\Notifications\ProjectInvitationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * FR3 "Invite collaborators" (roadmap 3.2). An invitation goes to an email
 * address — a member or someone who has not joined AQDesk yet — with a
 * secret link valid for ProjectInvitation::VALID_DAYS days. Accepting needs
 * an account with that same email address.
 */
class InvitationService
{
    /** @throws ValidationException when that person is already a member or already invited */
    public function invite(Project $project, User $inviter, string $email, string $role): ProjectInvitation
    {
        $email = Str::lower(trim($email));

        if ($project->members()->whereRaw('lower(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'That person is already a member of this project.']);
        }
        if ($project->invitations()->where('email', $email)->where('status', 'pending')->where('expires_at', '>', now())->exists()) {
            throw ValidationException::withMessages(['email' => 'An invitation to that address is still waiting. You can resend it from the list below.']);
        }

        $invitation = new ProjectInvitation(['email' => $email, 'role' => $role, 'status' => 'pending']);
        $invitation->project()->associate($project);
        $invitation->inviter()->associate($inviter);

        return $this->send($invitation);
    }

    /** A fresh link and expiry date; the old link stops working. */
    public function resend(ProjectInvitation $invitation): ProjectInvitation
    {
        return $this->send($invitation->fill(['status' => 'pending', 'responded_at' => null]));
    }

    public function cancel(ProjectInvitation $invitation): void
    {
        $invitation->update(['status' => 'cancelled', 'responded_at' => now()]);
    }

    /** The invitation behind a link, whatever its state; 404 for an unknown token. */
    public function fromToken(string $token): ProjectInvitation
    {
        return ProjectInvitation::with('project.owner', 'inviter')
            ->where('token_hash', ProjectInvitation::hashToken($token))->firstOrFail();
    }

    /** @throws ValidationException when the link is no longer valid or was meant for someone else */
    public function accept(ProjectInvitation $invitation, User $user): void
    {
        $this->ensureOpen($invitation);
        if (Str::lower($user->email) !== $invitation->email) {
            throw ValidationException::withMessages(['invitation' => 'This invitation was sent to '.$invitation->email
                .'. Sign in with that address to accept it.']);
        }

        DB::transaction(function () use ($invitation, $user) {
            $invitation->project->members()->syncWithoutDetaching([$user->id => ['role' => $invitation->role]]);
            $invitation->update(['status' => 'accepted', 'responded_at' => now()]);
        });

        $invitation->inviter?->notify(new InvitationAnswered($invitation, $user->name, true));
    }

    /** Anyone holding the link may decline it (no account needed). */
    public function decline(ProjectInvitation $invitation): void
    {
        $this->ensureOpen($invitation);
        $invitation->update(['status' => 'declined', 'responded_at' => now()]);

        $invitation->inviter?->notify(new InvitationAnswered($invitation, $invitation->email, false));
    }

    private function ensureOpen(ProjectInvitation $invitation): void
    {
        if (! $invitation->isOpen()) {
            throw ValidationException::withMessages(['invitation' => $invitation->status === 'pending'
                ? 'This invitation has expired. Ask the project owner to send a new one.'
                : 'This invitation has already been '.$invitation->status.'.']);
        }
    }

    private function send(ProjectInvitation $invitation): ProjectInvitation
    {
        $token = Str::random(48);
        $invitation->fill([
            'token_hash' => ProjectInvitation::hashToken($token),
            'expires_at' => now()->addDays(ProjectInvitation::VALID_DAYS),
        ])->save();

        $notification = new ProjectInvitationSent($invitation, $token);
        $member = User::whereRaw('lower(email) = ?', [$invitation->email])->where('is_active', true)->first();
        $member ? $member->notify($notification) : Notification::route('mail', $invitation->email)->notify($notification);

        return $invitation;
    }
}
