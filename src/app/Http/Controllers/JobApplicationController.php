<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * "Application submission" from FR5.
 */
class JobApplicationController extends Controller
{
    public function create(Request $request, JobPost $job): View
    {
        abort_unless($job->isOpen(), 404, 'This opportunity is no longer accepting applications.');

        abort_if(
            $job->hasApplicationFrom($request->user()),
            403,
            'You have already applied to this opportunity.'
        );

        return view('jobs.apply', compact('job'));
    }

    public function store(Request $request, JobPost $job): RedirectResponse
    {
        abort_unless($job->isOpen(), 404);

        if ($job->hasApplicationFrom($request->user())) {
            return redirect()->route('jobs.show', $job)
                ->withErrors(['apply' => 'You have already applied to this opportunity.']);
        }

        $validated = $request->validate([
            'cover_letter' => ['required', 'string', 'min:30', 'max:3000'],
            'resume'       => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
        ]);

        $resumePath = $request->hasFile('resume')
            ? $request->file('resume')->store('resumes', 'public')
            : null;

        JobApplication::create([
            'job_post_id'  => $job->id,
            'user_id'      => $request->user()->id,
            'cover_letter' => $validated['cover_letter'],
            'resume_path'  => $resumePath,
            'status'       => 'pending',
        ]);

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

        return view('jobs.my-applications', compact('applications'));
    }

    /** The employer moves an application through the hiring statuses. */
    public function updateStatus(Request $request, JobApplication $application): RedirectResponse
    {
        $user = $request->user();
        $job  = $application->jobPost;

        abort_unless($user->id === $job->user_id || $user->canModerate(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', JobApplication::STATUSES)],
        ]);

        $application->update($validated);

        return back()->with('status', 'Application status updated.');
    }

    /** Applicant withdraws their own application. */
    public function destroy(Request $request, JobApplication $application): RedirectResponse
    {
        abort_unless($request->user()->id === $application->user_id, 403);

        if ($application->resume_path) {
            Storage::disk('public')->delete($application->resume_path);
        }

        $application->delete();

        return redirect()->route('applications.index')->with('status', 'Application withdrawn.');
    }
}
