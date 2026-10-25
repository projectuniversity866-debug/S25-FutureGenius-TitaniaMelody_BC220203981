<?php

namespace App\Http\Controllers;

use App\Modules\Community\Services\QuestionFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Saved": what the signed-in member put aside to read later. Lives in the
 * application shell because it will gather items from several modules
 * (saved opportunities join it in roadmap 1.15). Private to its owner.
 */
class SavedController extends Controller
{
    public function __invoke(Request $request, QuestionFeed $feed): View
    {
        $questions = $feed->savedBy($request->user())->paginate(10);

        return view('saved.index', compact('questions'));
    }
}
