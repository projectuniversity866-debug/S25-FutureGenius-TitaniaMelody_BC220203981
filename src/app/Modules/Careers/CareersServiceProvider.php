<?php

namespace App\Modules\Careers;

use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Events\ApplicationSubmitted;
use App\Modules\Careers\Listeners\SendApplicationNotifications;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Policies\JobApplicationPolicy;
use App\Modules\Careers\Policies\JobPostPolicy;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR5 Jobs and Opportunities — publishing, applying, applicant review and
 * opportunity moderation.
 */
class CareersServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Careers';

    protected function policies(): array
    {
        return [
            JobPost::class        => JobPostPolicy::class,
            JobApplication::class => JobApplicationPolicy::class,
        ];
    }

    protected function listeners(): array
    {
        return [
            ApplicationSubmitted::class     => SendApplicationNotifications::class.'@handleSubmitted',
            ApplicationStatusChanged::class => SendApplicationNotifications::class.'@handleStatusChanged',
        ];
    }

    protected function morphMap(): array
    {
        return [
            'job_post'        => JobPost::class,
            'job_application' => JobApplication::class,
        ];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                // Opportunities this user has published.
                'jobPosts'        => fn (User $user) => $user->hasMany(JobPost::class),
                // Applications this user has submitted.
                'jobApplications' => fn (User $user) => $user->hasMany(JobApplication::class),
            ],
        ];
    }
}
