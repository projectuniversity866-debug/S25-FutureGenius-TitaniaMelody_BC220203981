<?php

namespace App\Http\Controllers;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Services\ReputationService;
use App\Modules\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * A member's public profile. Part of the application shell because it
 * combines Identity (who they are) with Community (what they contributed).
 * Tabs: overview, questions, answers, activity.
 */
class PublicProfileController extends Controller
{
    public const TABS = ['overview', 'questions', 'answers', 'activity'];

    public function __invoke(Request $request, User $user, ReputationService $reputation): View
    {
        $this->authorize('viewProfile', $user);

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'overview';

        $user->load(['role', 'profile'])->loadCount(['questions', 'answers']);

        $data = [
            'user'       => $user,
            'tab'        => $tab,
            'reputation' => $reputation->for($user),
            'badges'     => $reputation->badges($user),
            'privileges' => $reputation->privileges($user),
            'accepted'   => $user->answers()->where('is_accepted', true)->count(),
        ];

        $data += match ($tab) {
            'questions' => ['questions' => $user->questions()->with(['category', 'votes'])->withCount('answers')
                                ->latest()->paginate(10)->withQueryString()],
            'answers'   => ['answers' => $user->answers()->with(['question', 'votes'])
                                ->orderByDesc('is_accepted')->latest()->paginate(10)->withQueryString()],
            'activity'  => ['activity' => $this->activity($user), 'ledger' => $reputation->recent($user, 10)],
            default     => [
                'topQuestions' => $user->questions()->withCount(['answers', 'votes'])->orderByDesc('votes_count')->take(3)->get(),
                'topAnswers'   => $user->answers()->with('question')->withCount('votes')->orderByDesc('is_accepted')
                                    ->orderByDesc('votes_count')->take(3)->get(),
            ],
        };

        return view('profile.show', $data);
    }

    /**
     * Questions and answers merged into one timeline, newest first.
     *
     * @return Collection<int, array{type: string, title: string, url: string, at: \Carbon\CarbonInterface, accepted: bool}>
     */
    private function activity(User $user): Collection
    {
        $questions = $user->questions()->latest()->take(20)->get()->map(fn ($q) => [
            'type' => 'asked', 'title' => $q->title, 'url' => route('questions.show', $q), 'at' => $q->created_at, 'accepted' => false,
        ]);

        $answers = $user->answers()->with('question')->latest()->take(20)->get()->map(fn (Answer $a) => [
            'type' => 'answered', 'title' => $a->question->title,
            'url' => route('questions.show', $a->question).'#answer-'.$a->id, 'at' => $a->created_at, 'accepted' => $a->is_accepted,
        ]);

        return $questions->concat($answers)->sortByDesc('at')->take(25)->values();
    }
}
