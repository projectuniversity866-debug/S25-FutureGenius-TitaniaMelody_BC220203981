<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Notifications\ProjectInvitationSent;

/** A fresh project with an owner, a collaborator and a viewer, joined through real invitations. */
trait BuildsProjectTeams
{
    /** @return array{0: Project, 1: \App\Modules\Identity\Models\User, 2: \App\Modules\Identity\Models\User, 3: \App\Modules\Identity\Models\User} */
    private function team(): array
    {
        [$owner, $collaborator, $viewer] = [$this->member(), $this->otherMember(), $this->expert()];
        $this->actingAs($owner)->post(route('projects.store'), ['name' => 'Capstone team', 'visibility' => 'private']);
        $project = Project::where('user_id', $owner->id)->latest('id')->firstOrFail();

        foreach ([[$collaborator, 'collaborator'], [$viewer, 'viewer']] as [$user, $role]) {
            $this->travel(1)->seconds();   // a second team() call must find its own, newer invitation
            $this->actingAs($owner)->post(route('projects.invitations.store', $project), ['email' => $user->email, 'role' => $role]);
            $url = $user->notifications()->where('type', ProjectInvitationSent::class)->latest()->firstOrFail()->data['url'];
            $this->actingAs($user)->post($url.'/accept')->assertSessionHasNoErrors();
        }

        return [$project->fresh(), $owner, $collaborator, $viewer];
    }
}
