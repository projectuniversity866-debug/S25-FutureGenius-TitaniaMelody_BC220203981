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

    /** Community guidelines, terms of use and privacy policy (roadmap 2.5 – 2.6). */
    public function legal(string $page): View
    {
        return view('legal.'.$page);
    }
}
