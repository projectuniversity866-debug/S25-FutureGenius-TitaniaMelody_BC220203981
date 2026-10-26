<?php

namespace App\Modules\Careers\Services;

use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Events\ApplicationSubmitted;
use App\Modules\Careers\Models\ApplicationNote;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use App\Modules\Platform\Services\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Application submission" from FR5, and the employer's review of it.
 * CVs are private files: stored outside the web root and only sent through
 * downloadResume(), after the controller has checked the policy.
 */
class ApplicationService
{
    public function __construct(private PrivateFiles $files, private ActivityLogger $activity) {}

    /**
     * One application per person per opportunity.
     *
     * @throws ValidationException sending the user back to the opportunity
     */
    public function ensureNotAlreadyApplied(User $applicant, JobPost $job): void
    {
        if ($job->hasApplicationFrom($applicant)) {
            throw ValidationException::withMessages([
                'apply' => 'You have already applied to this opportunity.',
            ])->redirectTo(route('jobs.show', $job));
        }
    }

    public function submit(User $applicant, JobPost $job, string $coverLetter, ?UploadedFile $resume): JobApplication
    {
        $this->ensureNotAlreadyApplied($applicant, $job);

        $application = DB::transaction(function () use ($applicant, $job, $coverLetter, $resume) {
            $application = JobApplication::create([
                'job_post_id'  => $job->id,
                'user_id'      => $applicant->id,
                'cover_letter' => $coverLetter,
                'resume_path'  => $resume ? $this->files->store($resume, 'resumes') : null,
                'status'       => 'pending',
            ]);

            // The first line of the history: submitted by the applicant.
            $application->statusChanges()->create(['user_id' => $applicant->id, 'to_status' => 'pending']);

            return $application;
        });

        ApplicationSubmitted::dispatch($application);

        return $application;
    }

    /**
     * Move an application to another status, keeping the step in its history
     * with who made it and an optional message for the applicant.
     */
    public function changeStatus(JobApplication $application, string $status, ?User $reviewer = null, ?string $message = null): JobApplication
    {
        $from = $application->status;

        if ($from === $status) {
            return $application;
        }

        DB::transaction(function () use ($application, $status, $from, $reviewer, $message) {
            $application->update(['status' => $status]);
            $application->statusChanges()->create([
                'user_id' => $reviewer?->id, 'from_status' => $from, 'to_status' => $status, 'message' => $message,
            ]);
        });

        $this->activity->record('application.status_changed', $application,
            'Moved '.$application->user->name.'’s application for “'.$application->jobPost->title.'” from '.$from.' to '.$status,
            ['from' => $from, 'to' => $status]);

        ApplicationStatusChanged::dispatch($application, $from, $message);

        return $application;
    }

    /** A private reviewer note; the applicant never sees these. */
    public function addNote(JobApplication $application, User $reviewer, string $body): ApplicationNote
    {
        return $application->notes()->create(['user_id' => $reviewer->id, 'body' => $body]);
    }

    public function deleteNote(ApplicationNote $note): void
    {
        $note->delete();
    }

    /** The applicant withdraws; their uploaded CV is deleted with it. */
    public function withdraw(JobApplication $application): void
    {
        $this->files->delete($application->resume_path);

        $application->delete();
    }

    /** Stream the CV with a readable file name. Authorise before calling. */
    public function downloadResume(JobApplication $application): StreamedResponse
    {
        abort_unless($this->files->exists($application->resume_path), 404);

        $extension = pathinfo($application->resume_path, PATHINFO_EXTENSION);
        $name = 'CV - '.Str::slug($application->user->name, ' ').'.'.$extension;

        return $this->files->download($application->resume_path, $name);
    }
}
