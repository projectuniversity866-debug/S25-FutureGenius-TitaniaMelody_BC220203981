<?php

namespace Tests\Unit;

use App\Modules\Communications\Models\Announcement;
use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Consulting\Models\Proposal;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Support\PermissionCatalog;
use App\Modules\Platform\Support\LineDiff;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Suite UN — unit tests (4.1): rules that live in plain classes and models,
 * checked without a database or HTTP request (the framework is booted only
 * for date casting and config). Cases UN1–UN5.
 */
class DomainRulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** UN1 — Platform: the edit-history diff marks kept, removed and added lines in order. */
    public function test_un1_line_diff(): void
    {
        $this->assertSame([['type' => 'same', 'text' => 'one'], ['type' => 'same', 'text' => 'two']], LineDiff::compare("one\ntwo", "one\ntwo"));

        $diff = LineDiff::compare("Ask a question\nabout queues\nThanks", "Ask a question\nabout Laravel queues\nThanks");
        $this->assertSame(['same', 'removed', 'added', 'same'], array_column($diff, 'type'));
        $this->assertSame(['about queues', 'about Laravel queues'], [$diff[1]['text'], $diff[2]['text']]);

        $this->assertSame(['added', 'added'], array_column(LineDiff::compare('', "new\ntext"), 'type'));
    }

    /** UN2 — Identity: every permission has a unique slug, a known group and real roles; admins hold them all. */
    public function test_un2_permission_catalog(): void
    {
        $all = PermissionCatalog::all();
        $roles = [Role::ADMIN, Role::EXPERT, Role::MEMBER];

        $this->assertGreaterThanOrEqual(14, count($all));
        foreach ($all as $slug => $p) {
            $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
            $this->assertNotEmpty($p['name']);
            $this->assertNotEmpty($p['group']);
            $this->assertSame([], array_diff($p['roles'], $roles), "$slug has an unknown role");
            $this->assertContains(Role::ADMIN, $p['roles'], "administrators lack $slug");
        }
        foreach (PermissionCatalog::LOCKED as $slug) {
            $this->assertArrayHasKey($slug, $all);
        }
    }

    /** UN3 — Consulting: proposals only move forward along the allowed path, and expire after their last day. */
    public function test_un3_proposal_lifecycle(): void
    {
        $expected = [
            'draft' => ['sent'], 'sent' => ['accepted', 'rejected'], 'accepted' => ['completed'], 'rejected' => [], 'completed' => [],
        ];
        foreach ($expected as $from => $allowed) {
            $p = new Proposal(['status' => $from]);
            foreach (array_keys(Proposal::STATUSES) as $to) {
                $this->assertSame(in_array($to, $allowed, true), $p->canMoveTo($to), "$from → $to");
            }
        }

        Carbon::setTestNow('2026-11-10 12:00');
        $this->assertFalse((new Proposal(['status' => 'sent', 'valid_until' => '2026-11-10']))->isExpired());   // the whole last day counts
        $this->assertTrue((new Proposal(['status' => 'sent', 'valid_until' => '2026-11-09']))->isExpired());
        $this->assertFalse((new Proposal(['status' => 'accepted', 'valid_until' => '2026-11-09']))->isExpired());
        $this->assertFalse((new Proposal(['status' => 'sent']))->isExpired());
        $this->assertSame(['PKR 12,500', 'Free'], [(new Proposal(['amount_pkr' => 12500]))->amountLabel(), (new Proposal)->amountLabel()]);
    }

    /** UN4 — Communications: confirmation links last 7 days; times show in the chosen zone. */
    public function test_un4_communications_rules(): void
    {
        Carbon::setTestNow('2026-11-10 12:00');
        $pending = new Subscriber(['status' => 'pending']);
        $pending->updated_at = Carbon::parse('2026-11-03 12:01');
        $this->assertFalse($pending->confirmationExpired());
        $pending->updated_at = Carbon::parse('2026-11-03 11:59');
        $this->assertTrue($pending->confirmationExpired());
        $confirmed = new Subscriber(['status' => 'subscribed']);
        $confirmed->updated_at = Carbon::parse('2025-01-01');
        $this->assertFalse($confirmed->confirmationExpired());

        $campaign = new Campaign(['timezone' => 'Asia/Karachi', 'scheduled_at' => Carbon::parse('2026-11-03 04:30', 'UTC')]);
        $this->assertSame('Tue 3 Nov 2026, 09:30 PKT', $campaign->scheduleLabel());
        $this->assertNull((new Campaign)->scheduleLabel());
        $this->assertSame(array_keys(Campaign::TIMEZONES), array_keys(\App\Modules\EventsMedia\Models\Event::TIMEZONES));   // one list of zones in the app
    }

    /** UN5 — Communications: an announcement is upcoming, live or ended; every kind has a word and an icon. */
    public function test_un5_announcement_phase(): void
    {
        Carbon::setTestNow('2026-11-10 12:00');
        $make = fn ($start, $end) => new Announcement(['starts_at' => $start, 'ends_at' => $end, 'timezone' => 'UTC']);

        $this->assertSame('upcoming', $make('2026-11-11 00:00', null)->phase());
        $this->assertSame('live', $make('2026-11-01 00:00', null)->phase());
        $this->assertSame('live', $make('2026-11-01 00:00', '2026-11-10 12:01')->phase());
        $this->assertSame('ended', $make('2026-11-01 00:00', '2026-11-10 12:00')->phase());

        foreach (Announcement::TONES as [$label, $icon, $class]) {
            $this->assertNotEmpty($label);
            $this->assertNotEmpty($icon);
            $this->assertStringStartsWith('alert-', $class);
        }
    }
}
