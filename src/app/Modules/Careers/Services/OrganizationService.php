<?php

namespace App\Modules\Careers\Services;

use App\Modules\Careers\Models\Organization;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/** Organization (employer) profiles — roadmap 1.12. */
class OrganizationService
{
    public function __construct(private ActivityLogger $activity) {}

    /** @param  array<string, mixed>  $data  validated OrganizationRequest data */
    public function create(User $owner, array $data): Organization
    {
        return Organization::create($data + [
            'user_id' => $owner->id,
            'slug'    => Organization::makeSlug($data['name']),
        ]);
    }

    /**
     * Posts show the organization's name, so a rename reaches every
     * opportunity published under it.
     *
     * @param  array<string, mixed>  $data  validated OrganizationRequest data
     */
    public function update(Organization $organization, array $data): Organization
    {
        return DB::transaction(function () use ($organization, $data) {
            $organization->update($data + ['slug' => Organization::makeSlug($data['name'], $organization->id)]);
            $organization->jobPosts()->update(['company' => $organization->name]);

            return $organization;
        });
    }

    /** Opportunities stay on the board; they just lose the profile link. */
    public function delete(Organization $organization): void
    {
        $this->activity->record('organization.deleted', $organization, 'Deleted the organization “'.$organization->name.'”',
            ['opportunities' => $organization->jobPosts()->count()]);

        $organization->delete();
    }
}
