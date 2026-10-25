<?php

namespace App\Services;

use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Category;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\Vote;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Read-only figures for the role-specific dashboards. The dashboard is part
 * of the application shell, so it may read across modules; it never writes.
 *
 * A "tile" is ['label', 'value', 'icon', 'tone', 'delta', 'hint', 'trend'],
 * where delta is the change over the last 7 days and trend is one value per
 * day for the last DAYS days.
 */
class DashboardMetrics
{
    public const DAYS = 14;

    // ------------------------------------------------------------- admin

    /** @return array<string, mixed> */
    public function admin(): array
    {
        $questions = $this->daily(Question::query());
        $answers   = $this->daily(Answer::query());

        return [
            'tiles' => [
                $this->tile('Members', User::count(), 'people', 'accent', $this->daily(User::query())),
                $this->tile('Questions', Question::count(), 'patch-question', 'blue', $questions),
                $this->tile('Answers', Answer::count(), 'chat-left-text', 'green', $answers),
                $this->tile('Open opportunities', JobPost::active()->count(), 'briefcase', 'amber', $this->daily(JobPost::query())),
            ],
            'activity' => $this->activity($questions, $answers),
            'topics' => Category::withCount('questions')->orderByDesc('questions_count')->get()
                ->map(fn ($c) => ['label' => $c->name, 'value' => $c->questions_count,
                                  'url' => route('questions.index', ['category' => $c->slug])])->all(),
            'pipeline' => $this->pipeline(JobApplication::query()),
            'unanswered' => Question::with(['user', 'category'])
                ->where('is_closed', false)->doesntHave('answers')
                ->oldest()->take(5)->get(),
            'newMembers' => User::with(['role', 'profile'])->latest()->take(5)->get(),
        ];
    }

    // ------------------------------------------------------------ expert

    /** @return array<string, mixed> */
    public function expert(User $user): array
    {
        $answers      = $this->daily($user->answers()->getQuery());
        $answerCount  = $user->answers()->count();
        $accepted     = $user->answers()->where('is_accepted', true)->count();
        $jobs         = $user->jobPosts()->withCount('applications')->latest()->get();
        $applications = JobApplication::whereIn('job_post_id', $jobs->pluck('id'));

        return [
            'tiles' => [
                $this->tile('Answers given', $answerCount, 'chat-left-text', 'green', $answers),
                $this->tile('Accepted answers', $accepted, 'check2-circle', 'accent', null,
                    $answerCount ? round($accepted / $answerCount * 100).'% of your answers' : 'None yet'),
                $this->tile('Open postings', $jobs->filter->isOpen()->count(), 'briefcase', 'amber', null,
                    'of '.$jobs->count().' posted'),
                $this->tile('Applicants', (clone $applications)->count(), 'people', 'blue',
                    $this->daily(clone $applications)),
            ],
            'applicantsPerJob' => $jobs->sortByDesc('applications_count')->take(6)
                ->map(fn ($j) => ['label' => $j->title, 'value' => $j->applications_count,
                                  'url' => route('jobs.applicants', $j)])->values()->all(),
            'pipeline' => $this->pipeline(clone $applications),
            'unanswered' => Question::with(['user', 'category'])
                ->where('is_closed', false)->where('user_id', '!=', $user->id)->doesntHave('answers')
                ->latest()->take(5)->get(),
            'recentApplicants' => (clone $applications)->with(['user', 'jobPost'])->latest()->take(5)->get(),
        ];
    }

    // ------------------------------------------------------------ member

    /** @return array<string, mixed> */
    public function member(User $user): array
    {
        $votesReceived = Vote::query()
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('votable_type', 'question')
                    ->whereIn('votable_id', $user->questions()->select('id')))
                ->orWhere(fn ($q) => $q->where('votable_type', 'answer')
                    ->whereIn('votable_id', $user->answers()->select('id'))))
            ->sum('value');

        return [
            'tiles' => [
                $this->tile('Questions asked', $user->questions()->count(), 'patch-question', 'blue',
                    $this->daily($user->questions()->getQuery())),
                $this->tile('Answers given', $user->answers()->count(), 'chat-left-text', 'green',
                    $this->daily($user->answers()->getQuery())),
                $this->tile('Votes received', (int) $votesReceived, 'hand-thumbs-up', 'accent', null,
                    'on your questions and answers'),
                $this->tile('Applications', $user->jobApplications()->count(), 'send', 'amber',
                    $this->daily($user->jobApplications()->getQuery())),
            ],
            'activity' => $this->activity($this->daily(Question::query()), $this->daily(Answer::query())),
            'myQuestions' => $user->questions()->withCount('answers')->latest()->take(5)->get(),
            'myApplications' => $user->jobApplications()->with('jobPost')->latest()->take(5)->get(),
            'recommendedJobs' => JobPost::active()
                ->whereNotIn('id', $user->jobApplications()->select('job_post_id'))
                ->latest()->take(4)->get(),
        ];
    }

    // ------------------------------------------------- to-do and activity

    /**
     * Things this user should act on, most important first. Each item is
     * ['text', 'url', 'icon'] and links straight to where the action is.
     *
     * @return array<int, array{text: string, url: string, icon: string}>
     */
    public function todo(User $user): array
    {
        $todo = [];

        if (! $user->hasVerifiedEmail()) {
            $todo[] = ['text' => 'Confirm your email address to start posting', 'url' => route('verification.notice'), 'icon' => 'envelope-exclamation'];
        }

        if ($user->can('content.moderate')) {
            $waiting = Question::where('is_closed', false)->doesntHave('answers')
                ->where('created_at', '<', now()->subDays(2))->count();
            if ($waiting) {
                $todo[] = ['text' => $waiting.' '.\Illuminate\Support\Str::plural('question', $waiting).' unanswered for over 2 days',
                           'url' => route('questions.index', ['sort' => 'unanswered']), 'icon' => 'hourglass-split'];
            }
        }

        if ($user->can('opportunities.publish')) {
            $pending = JobApplication::where('status', 'pending')
                ->whereIn('job_post_id', $user->jobPosts()->select('id'))->with('jobPost')->get();
            foreach ($pending->groupBy('job_post_id') as $apps) {
                $todo[] = ['text' => 'Review '.$apps->count().' pending '.\Illuminate\Support\Str::plural('applicant', $apps->count()).' for “'.$apps->first()->jobPost->title.'”',
                           'url' => route('jobs.applicants', [$apps->first()->jobPost, 'status' => 'pending']), 'icon' => 'person-check'];
            }
        }

        $unaccepted = $user->questions()->has('answers')
            ->whereDoesntHave('answers', fn ($q) => $q->where('is_accepted', true))->latest()->take(3)->get();
        foreach ($unaccepted as $question) {
            $todo[] = ['text' => 'Accept the answer that solved “'.$question->title.'”', 'url' => route('questions.show', $question), 'icon' => 'patch-check'];
        }

        if (! $user->profile?->headline || ! $user->profile?->bio) {
            $todo[] = ['text' => 'Complete your profile so others know your expertise', 'url' => route('profile.edit'), 'icon' => 'person-vcard'];
        }

        return array_slice($todo, 0, 6);
    }

    /**
     * Recent events for the feed: the audit trail for people who may read
     * it, otherwise the user's own notifications.
     *
     * @return array<int, array{text: string, when: Carbon, icon: string, url: ?string}>
     */
    public function activityFeed(User $user): array
    {
        if ($user->can('activity.view')) {
            return \App\Modules\Platform\Models\ActivityLog::with('user')->latest('created_at')->latest('id')->take(6)->get()
                ->map(fn ($e) => ['text' => ($e->user?->name ?? 'System').': '.$e->description, 'when' => $e->created_at,
                                  'icon' => 'clock-history', 'url' => null])->all();
        }

        return $user->notifications()->take(6)->get()
            ->map(fn ($n) => ['text' => $n->data['body'] ?? $n->data['title'] ?? 'Notification', 'when' => $n->created_at,
                              'icon' => $n->data['icon'] ?? 'bell', 'url' => route('notifications.open', $n->id)])->all();
    }

    // ----------------------------------------------------------- helpers

    /**
     * One count per day for the last DAYS days, oldest first.
     *
     * @return array<int, int>
     */
    private function daily(Builder $query): array
    {
        $since = Carbon::today()->subDays(self::DAYS - 1);

        $counts = $query->where($query->getModel()->getTable().'.created_at', '>=', $since)
            ->pluck($query->getModel()->getTable().'.created_at')
            ->countBy(fn ($at) => Carbon::parse($at)->toDateString());

        return collect(range(self::DAYS - 1, 0))
            ->map(fn ($ago) => (int) ($counts[Carbon::today()->subDays($ago)->toDateString()] ?? 0))
            ->all();
    }

    /**
     * @param  array<int, int>|null  $trend
     * @return array<string, mixed>
     */
    private function tile(string $label, int $value, string $icon, string $tone, ?array $trend, ?string $hint = null): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'icon'  => $icon,
            'tone'  => $tone,
            'delta' => $trend === null ? null : array_sum(array_slice($trend, -7)),
            'hint'  => $hint,
            'trend' => $trend,
        ];
    }

    /**
     * Two-series daily activity for the line chart.
     *
     * @return array{labels: array<int, string>, series: array<int, array{name: string, values: array<int, int>}>}
     */
    private function activity(array $questions, array $answers): array
    {
        return [
            'labels' => collect(range(self::DAYS - 1, 0))
                ->map(fn ($ago) => Carbon::today()->subDays($ago)->toDateString())->all(),
            'series' => [
                ['name' => 'Questions', 'values' => $questions],
                ['name' => 'Answers', 'values' => $answers],
            ],
        ];
    }

    /** @return array<int, array{label: string, value: int, status: string}> */
    private function pipeline(Builder $applications): array
    {
        $counts = $applications->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(JobApplication::STATUSES)
            ->map(fn ($s) => ['label' => ucfirst($s), 'value' => (int) ($counts[$s] ?? 0), 'status' => $s])
            ->all();
    }
}
