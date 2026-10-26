<?php

namespace App\Http\Controllers;

use App\Modules\Community\Models\Category;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Public landing page. */
    public function home(): View
    {
        $stats = [
            'members'   => User::count(),
            'questions' => Question::count(),
            'answers'   => \App\Modules\Community\Models\Answer::count(),
            'jobs'      => JobPost::active()->count(),
        ];

        $categories = Category::withCount('questions')->get();

        $latestQuestions = Question::with(['user', 'category', 'tags', 'votes'])
            ->withCount('answers')
            ->latest()
            ->take(5)
            ->get();

        $latestJobs = JobPost::active()->latest()->take(4)->get();

        return view('home', compact('stats', 'categories', 'latestQuestions', 'latestJobs'));
    }

    /**
     * Placeholder pages for the four modules deferred to the final project
     * (FR3 Project Collaboration, FR4 Events & Podcasts, FR6 Consulting,
     * FR7 Newsletter). They exist so the navigation and the modular
     * architecture are visible in the prototype.
     */
    public function comingSoon(string $module): View
    {
        $modules = [
            'newsletter' => [
                'title'    => 'Newsletter & Announcements',
                'icon'     => 'envelope-paper',
                'source'   => 'FR7 — Main Requirement File',
                'summary'  => 'Community updates delivered by email campaign.',
                'features' => ['Newsletter subscription', 'Email campaign management', 'Community updates'],
            ],
        ];

        abort_unless(isset($modules[$module]), 404);

        return view('coming-soon', ['module' => $modules[$module]]);
    }

    /** Community guidelines, terms of use and privacy policy (roadmap 2.5 – 2.6). */
    public function legal(string $page): View
    {
        return view('legal.'.$page);
    }
}
