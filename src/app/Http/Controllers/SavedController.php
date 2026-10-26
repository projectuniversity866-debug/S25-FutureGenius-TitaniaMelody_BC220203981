<?php

namespace App\Http\Controllers;

use App\Modules\Community\Services\QuestionFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Saved": what the signed-in member put aside to read later — questions
 * (Community, roadmap 1.10) and opportunities (Careers, 1.15). Lives in the
 * application shell because it gathers items from both modules. Private to
 * its owner.
 */
class SavedController extends Controller
{
    public function __invoke(Request $request, QuestionFeed $feed): View
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'opportunities' ? 'opportunities' : 'questions';

        $counts = [
            'questions'     => $user->bookmarks()->count(),
            'opportunities' => $user->savedOpportunities()->count(),
        ];

        $items = $tab === 'questions'
            ? $feed->savedBy($user)->paginate(10)->withQueryString()
            : $user->savedOpportunities()->with('user')
                ->orderByPivot('created_at', 'desc')->orderByPivot('id', 'desc')
                ->paginate(9)->withQueryString();

        return view('saved.index', compact('tab', 'counts', 'items'));
    }
}
