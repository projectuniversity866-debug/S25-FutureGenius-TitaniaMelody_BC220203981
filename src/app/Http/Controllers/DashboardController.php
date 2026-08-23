<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Prototype module #3 "Dashboard".
 *
 * The dashboard is role-aware: the statistics and the quick actions shown
 * depend on whether the signed-in user is an Administrator, an Expert or a
 * Member. This is the most visible demonstration of role-based access.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = match (true) {
            $user->isAdmin()  => $this->adminStats(),
            $user->isExpert() => $this->expertStats($user),
            default           => $this->memberStats($user),
        };

        // Recent community activity is shown to everybody.
        $recentQuestions = Question::with(['user', 'category'])
            ->withCount('answers')
            ->latest()
            ->take(5)
            ->get();

        $recentJobs = JobPost::active()->latest()->take(4)->get();

        // The user's own latest contributions.
        $myQuestions = $user->questions()->withCount('answers')->latest()->take(5)->get();
        $myApplications = $user->jobApplications()->with('jobPost')->latest()->take(5)->get();

        return view('dashboard.index', compact(
            'user', 'stats', 'recentQuestions', 'recentJobs', 'myQuestions', 'myApplications'
        ));
    }

    /** @return array<int, array{label:string, value:int|string, icon:string, color:string}> */
    private function adminStats(): array
    {
        return [
            ['label' => 'Total users',        'value' => User::count(),                    'icon' => 'people',          'color' => 'primary'],
            ['label' => 'Questions',          'value' => Question::count(),                'icon' => 'patch-question',  'color' => 'info'],
            ['label' => 'Answers',            'value' => Answer::count(),                  'icon' => 'chat-left-text',  'color' => 'success'],
            ['label' => 'Open opportunities', 'value' => JobPost::active()->count(),       'icon' => 'briefcase',       'color' => 'warning'],
        ];
    }

    /** @return array<int, array{label:string, value:int|string, icon:string, color:string}> */
    private function expertStats(User $user): array
    {
        return [
            ['label' => 'My answers',        'value' => $user->answers()->count(),   'icon' => 'chat-left-text', 'color' => 'success'],
            ['label' => 'My questions',      'value' => $user->questions()->count(), 'icon' => 'patch-question', 'color' => 'info'],
            ['label' => 'Jobs I posted',     'value' => $user->jobPosts()->count(),  'icon' => 'briefcase',      'color' => 'warning'],
            ['label' => 'Applicants',        'value' => JobApplication::whereIn(
                'job_post_id', $user->jobPosts()->pluck('id')
            )->count(), 'icon' => 'inbox', 'color' => 'primary'],
        ];
    }

    /** @return array<int, array{label:string, value:int|string, icon:string, color:string}> */
    private function memberStats(User $user): array
    {
        return [
            ['label' => 'My questions',    'value' => $user->questions()->count(),        'icon' => 'patch-question', 'color' => 'info'],
            ['label' => 'My answers',      'value' => $user->answers()->count(),          'icon' => 'chat-left-text', 'color' => 'success'],
            ['label' => 'My applications', 'value' => $user->jobApplications()->count(),  'icon' => 'send',           'color' => 'primary'],
            ['label' => 'Open jobs',       'value' => JobPost::active()->count(),         'icon' => 'briefcase',      'color' => 'warning'],
        ];
    }
}
