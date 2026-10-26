<?php

namespace App\Modules\Careers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Careers\Http\Requests\ApplicationNoteRequest;
use App\Modules\Careers\Http\Requests\ApplyRequest;
use App\Modules\Careers\Http\Requests\UpdateApplicationStatusRequest;
use App\Modules\Careers\Models\ApplicationNote;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Application submission" from FR5.
 */
class JobApplicationController extends Controller
{
    public function __construct(private ApplicationService $applications) {}

    public function create(Request $request, JobPost $job): View
    {
        $this->authorize('apply', $job);
        $this->applications->ensureNotAlreadyApplied($request->user(), $job);

        return view('careers::jobs.apply', compact('job'));
    }

    public function store(ApplyRequest $request, JobPost $job): RedirectResponse
    {
        $this->applications->submit(
            $request->user(),
            $job,
            $request->validated('cover_letter'),
            $request->file('resume'),
        );

        return redirect()->route('applications.index')
            ->with('status', 'Your application to "'.$job->title.'" has been submitted.');
    }

    /** "My applications" screen for the signed-in user. */
    public function index(Request $request): View
    {
        $applications = $request->user()
            ->jobApplications()
            ->with('jobPost')
            ->latest()
            ->paginate(10);

        return view('careers::jobs.my-applications', compact('applications'));
    }

    /**
     * One application: the applicant sees its progress and history; reviewers
     * also get the status form and their private notes (roadmap 1.14).
     */
    public function show(Request $request, JobApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load(['jobPost.organization', 'user.profile', 'statusChanges.user']);
        $isReviewer = $request->user()->can('review', $application);
        $notes = $isReviewer ? $application->notes()->with('user')->get() : collect();

        return view('careers::applications.show', compact('application', 'isReviewer', 'notes'));
    }

    /** The employer moves an application through the hiring statuses. */
    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $application): RedirectResponse
    {
        $this->applications->changeStatus(
            $application, $request->validated('status'), $request->user(), $request->validated('message'),
        );

        return back()->with('status', 'Application status updated.');
    }

    public function storeNote(ApplicationNoteRequest $request, JobApplication $application): RedirectResponse
    {
        $this->applications->addNote($application, $request->user(), $request->validated('body'));

        return redirect()->to(route('applications.show', $application).'#notes')->with('status', 'Note added.');
    }

    public function destroyNote(ApplicationNote $note): RedirectResponse
    {
        $this->authorize('deleteNote', [JobApplication::class, $note]);

        $this->applications->deleteNote($note);

        return back()->with('status', 'Note deleted.');
    }

    /** Download a CV — private file, sent only after the policy check. */
    public function resume(JobApplication $application): StreamedResponse
    {
        $this->authorize('viewResume', $application);

        return $this->applications->downloadResume($application);
    }

    /** Applicant withdraws their own application. */
    public function destroy(JobApplication $application): RedirectResponse
    {
        $this->authorize('delete', $application);

        $this->applications->withdraw($application);

        return redirect()->route('applications.index')->with('status', 'Application withdrawn.');
    }
}
