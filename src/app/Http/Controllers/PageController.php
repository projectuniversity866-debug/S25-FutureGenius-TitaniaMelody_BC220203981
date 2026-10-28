<?php

namespace App\Http\Controllers;

use App\Modules\Community\Models\Category;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    /** The landing page's totals are the same for every visitor, so they are counted every few minutes, not on each visit (roadmap 4.2). */
    public const HOME_CACHE_SECONDS = 600;

    /** Public landing page. */
    public function home(): View
    {
        $stats = Cache::remember('home.stats', self::HOME_CACHE_SECONDS, fn () => [
            'members'   => User::count(),
            'questions' => Question::count(),
            'answers'   => \App\Modules\Community\Models\Answer::count(),
            'jobs'      => JobPost::active()->count(),
        ]);

        $categories = Cache::remember('home.categories', self::HOME_CACHE_SECONDS, fn () => Category::withCount('questions')->get());

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
