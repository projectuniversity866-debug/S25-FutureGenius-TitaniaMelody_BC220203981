<?php

namespace App\Modules\Careers;

use App\Modules\Careers\Console\CloseExpiredOpportunities;
use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Events\ApplicationSubmitted;
use App\Modules\Careers\Events\OpportunityPublished;
use App\Modules\Careers\Listeners\SendApplicationNotifications;
use App\Modules\Careers\Listeners\SendJobAlerts;
use App\Modules\Careers\Models\JobAlert;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Models\Organization;
use App\Modules\Careers\Policies\JobAlertPolicy;
use App\Modules\Careers\Policies\JobApplicationPolicy;
use App\Modules\Careers\Policies\JobPostPolicy;
use App\Modules\Careers\Policies\OrganizationPolicy;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR5 Jobs and Opportunities — organizations, publishing, applying, the
 * application pipeline, saved opportunities, job alerts, deadline closing
 * and opportunity moderation.
 */
class CareersServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Careers';

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([CloseExpiredOpportunities::class]);
        }
    }

    protected function policies(): array
    {
        return [
            JobPost::class        => JobPostPolicy::class,
            JobApplication::class => JobApplicationPolicy::class,
            Organization::class   => OrganizationPolicy::class,
            JobAlert::class       => JobAlertPolicy::class,
        ];
    }

    protected function listeners(): array
    {
        return [
            ApplicationSubmitted::class     => SendApplicationNotifications::class.'@handleSubmitted',
            ApplicationStatusChanged::class => SendApplicationNotifications::class.'@handleStatusChanged',
            OpportunityPublished::class     => SendJobAlerts::class.'@handle',
        ];
    }

    protected function morphMap(): array
    {
        return [
            'job_post'        => JobPost::class,
            'job_application' => JobApplication::class,
            'organization'    => Organization::class,
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
                // Organization profiles this user manages.
                'organizations'   => fn (User $user) => $user->hasMany(Organization::class),
                // Opportunities this user saved for later.
                'savedOpportunities' => fn (User $user) => $user->belongsToMany(
                    JobPost::class, 'saved_opportunities', 'user_id', 'job_post_id', 'id', 'id', 'savedOpportunities'
                )->withTimestamps(),
            ],
        ];
    }
}
