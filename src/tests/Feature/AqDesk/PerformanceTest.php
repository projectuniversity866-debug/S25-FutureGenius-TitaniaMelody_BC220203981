<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Communications\Models\Announcement;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Suite PF — performance (4.2): list pages make the same number of queries
 * however many rows they show, N+1 queries fail the tests, the busiest
 * shared data is cached, and the list queries have their indexes. Cases PF1–PF5.
 */
class PerformanceTest extends AqDeskTestCase
{
    /** Queries a GET request makes. */
    private function queries(string $url, ?User $as = null): int
    {
        $as ? $this->actingAs($as) : $this->signOut();
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $this->get($url)->assertOk();
        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $count;
    }

    /** PF1 — question list, opportunity board, a profile's questions and the user admin page: twice the rows, the same queries. */
    public function test_pf1_lists_do_not_grow_with_rows(): void
    {
        $authors = [$this->member(), $this->otherMember(), $this->disposableMember()];
        $add = function (int $n) use ($authors) {
            foreach (range(1, $n) as $i) {
                $this->askQuestionAs($authors[$i % 3], ['title' => 'Performance probe question number '.uniqid()]);
                $this->postJobAs($this->expert(), ['title' => 'Performance probe opportunity '.uniqid()]);
            }
        };
        $pages = fn () => [
            route('questions.index'), route('jobs.index'), route('profile.show', [$authors[0], 'tab' => 'questions']), route('admin.users.index'),
        ];

        $add(4);
        array_map(fn ($url) => $this->queries($url, $this->admin()), $pages());   // warm the caches first
        $before = array_map(fn ($url) => $this->queries($url, $this->admin()), $pages());
        $add(4);
        $after = array_map(fn ($url) => $this->queries($url, $this->admin()), $pages());

        $this->assertSame($before, $after, 'a list page made more queries with more rows (N+1)');
    }

    /** PF2 — in tests a lazy-loaded relation fails; people come with their profile and role. */
    public function test_pf2_no_lazy_loading(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());

        $users = User::take(3)->get();
        $this->assertTrue($users->every(fn (User $u) => $u->relationLoaded('profile') && $u->relationLoaded('role')));
    }

    /** PF3 — the home page totals are counted once per ten minutes, not on every visit. */
    public function test_pf3_home_totals_cached(): void
    {
        Cache::flush();
        $first = $this->queries(route('home'));
        $second = $this->queries(route('home'));

        $this->assertTrue(Cache::has('home.stats'));
        $this->assertLessThanOrEqual($first - 5, $second);   // 4 counts + the topic counts
    }

    /** PF4 — banners come from the cache; posting or deleting an announcement shows at once. */
    public function test_pf4_banners_cached_and_refreshed(): void
    {
        $this->signOut();
        $this->get(route('home'));   // warm the cache
        $hits = 0;
        DB::listen(function ($q) use (&$hits) {
            $hits += str_contains($q->sql, '"announcements"') ? 1 : 0;
        });
        $this->get(route('questions.index'))->assertOk();
        $this->assertSame(0, $hits);

        $this->actingAs($this->admin())->post(route('admin.announcements.store'), [
            'title' => 'Cached banners refresh at once', 'body' => 'Posting an announcement clears the cached list.',
            'tone' => 'info', 'audience' => 'everyone', 'is_dismissible' => '1', 'timezone' => 'UTC', 'starts_at' => now()->subMinute()->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();
        $this->signOut();
        $this->get(route('home'))->assertSee('Cached banners refresh at once');

        $this->actingAs($this->admin())->delete(route('admin.announcements.destroy', Announcement::firstOrFail()));
        $this->signOut();
        $this->get(route('home'))->assertDontSee('Cached banners refresh at once');
    }

    /** PF5 — the listing and lookup queries have indexes. */
    public function test_pf5_indexes(): void
    {
        $names = fn (string $table) => array_column(Schema::getIndexes($table), 'name');

        foreach ([
            'job_posts' => ['job_posts_board_index', 'job_posts_deadline_index'],
            'notifications' => ['notifications_unread_index'],
            'questions' => ['questions_created_at_index'],
            'events' => ['events_status_starts_at_index'],
            'consultations' => ['consultations_consultant_profile_id_status_starts_at_index'],
            'newsletter_campaign_recipients' => ['campaign_recipient_status_index'],
        ] as $table => $expected) {
            foreach ($expected as $index) {
                $this->assertContains($index, $names($table), "$table is missing $index");
            }
        }
    }
}
