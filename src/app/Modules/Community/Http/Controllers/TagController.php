<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Tag;
use App\Modules\Community\Services\QuestionFeed;
use App\Modules\Community\Services\TagFollowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The tag directory, and following a tag (roadmap 1.10). */
class TagController extends Controller
{
    /** Sort key => tab label. */
    public const SORTS = ['popular' => 'Popular', 'name' => 'Name', 'new' => 'New'];

    public function __construct(private TagFollowService $follows) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $sort = is_string($request->query('sort')) && isset(self::SORTS[$request->query('sort')]) ? $request->query('sort') : 'popular';
        $search = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $since = now()->subDays(QuestionFeed::TRENDING_DAYS);

        $tags = Tag::query()
            ->withCount([
                'questions',
                'followers',
                'questions as recent_questions_count' => fn (Builder $q) => $q->where('questions.created_at', '>=', $since),
            ])
            ->when($user, fn (Builder $q) => $q->withExists([
                'followers as is_followed' => fn (Builder $f) => $f->where('users.id', $user->id),
            ]))
            ->when($search !== '', fn (Builder $q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($sort === 'popular', fn (Builder $q) => $q->orderByDesc('questions_count'))
            ->when($sort === 'new', fn (Builder $q) => $q->latest())
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('community::tags.index', compact('tags', 'sort', 'search'));
    }

    public function follow(Request $request, Tag $tag): RedirectResponse|JsonResponse
    {
        $this->follows->follow($request->user(), $tag);

        return $this->respond($request, $tag, true,
            'You now follow '.$tag->name.'. New questions with this tag will appear in your notifications.');
    }

    public function unfollow(Request $request, Tag $tag): RedirectResponse|JsonResponse
    {
        $this->follows->unfollow($request->user(), $tag);

        return $this->respond($request, $tag, false, 'You no longer follow '.$tag->name.'.');
    }

    private function respond(Request $request, Tag $tag, bool $following, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'following' => $following,
                'count'     => $tag->followers()->count(),
                'message'   => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
