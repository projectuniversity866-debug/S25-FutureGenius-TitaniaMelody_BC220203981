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
use App\Modules\Careers\Services\ApplicationService;
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

    /** Your applications, saved opportunities, alerts and postings (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'applications' => fn (User $user) => $user->jobApplications()->with('jobPost:id,title,company', 'statusChanges')->get()
                ->map(fn (JobApplication $a) => [
                    'opportunity' => $a->jobPost?->title, 'organization' => $a->jobPost?->company, 'status' => $a->status,
                    'cover_letter' => $a->cover_letter, 'cv_uploaded' => (bool) $a->resume_path, 'applied_at' => $a->created_at,
                    'history' => $a->statusChanges->map(fn ($c) => ['to' => $c->to_status, 'message' => $c->message, 'at' => $c->created_at])->all(),
                ])->all(),
            'saved_opportunities' => fn (User $user) => $user->savedOpportunities()->pluck('title')->all(),
            'job_alerts'          => fn (User $user) => JobAlert::ownedBy($user)->get()->map->describe()->all(),
            'opportunities_published' => fn (User $user) => $user->jobPosts()->get(['title', 'company', 'type', 'created_at'])->toArray(),
            'organizations'       => fn (User $user) => $user->organizations()->pluck('name')->all(),
        ];
    }

    /** Applications are withdrawn (CVs deleted); saved items and alerts go. Postings stay. */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            $applications = app(ApplicationService::class);
            $user->jobApplications()->get()->each(fn (JobApplication $a) => $applications->withdraw($a));
            $user->savedOpportunities()->detach();
            JobAlert::ownedBy($user)->delete();
        }];
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
