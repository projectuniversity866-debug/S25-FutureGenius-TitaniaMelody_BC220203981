<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Save / un-save a question. The plain form gets a redirect; the Vue button
 * (Accept: application/json) gets the new state without a page reload.
 */
class BookmarkController extends Controller
{
    public function __construct(private BookmarkService $bookmarks) {}

    public function store(Request $request, Question $question): RedirectResponse|JsonResponse
    {
        $this->bookmarks->save($request->user(), $question);

        return $this->respond($request, $question, true, 'Question saved. You can find it under Saved.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse|JsonResponse
    {
        $this->bookmarks->remove($request->user(), $question);

        return $this->respond($request, $question, false, 'Removed from your saved questions.');
    }

    private function respond(Request $request, Question $question, bool $saved, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'saved'   => $saved,
                'count'   => $question->bookmarks()->count(),
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
