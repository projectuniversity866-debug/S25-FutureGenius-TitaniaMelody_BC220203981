<?php

namespace App\Modules\Careers\Services;

use App\Modules\Careers\Models\JobPost;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;

class JobPostService
{
    public function __construct(private ActivityLogger $activity) {}

    /** @param  array<string, mixed>  $data  validated JobPostRequest data */
    public function publish(User $poster, array $data): JobPost
    {
        return JobPost::create($data + [
            'user_id' => $poster->id,
            'slug'    => JobPost::makeSlug($data['title']),
        ]);
    }

    /** @param  array<string, mixed>  $data  validated JobPostRequest data */
    public function update(JobPost $job, array $data, bool $isActive): JobPost
    {
        $job->update($data + [
            'slug'      => JobPost::makeSlug($data['title'], $job->id),
            'is_active' => $isActive,
        ]);

        return $job;
    }

    public function delete(JobPost $job): void
    {
        $this->activity->record('opportunity.deleted', $job, 'Deleted the opportunity “'.$job->title.'”',
            ['company' => $job->company, 'applications' => $job->applications()->count()]);

        $job->delete();
    }

    /** Moderator action: hide an opportunity from the board, or show it again. */
    public function togglePublished(JobPost $job): JobPost
    {
        $job->update(['is_active' => ! $job->is_active]);

        $this->activity->record(
            $job->is_active ? 'opportunity.published' : 'opportunity.unpublished',
            $job,
            ($job->is_active ? 'Published' : 'Unpublished').' the opportunity “'.$job->title.'”',
        );

        return $job;
    }
}
