<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Communications\Models\Announcement;
use Illuminate\Support\Carbon;

/**
 * Suite AN — site announcements and banners (3.18). Cases AN1–AN4.
 */
class AnnouncementTest extends AqDeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 10:00', 'Asia/Karachi'));
    }

    private function announce(array $data = []): Announcement
    {
        $this->actingAs($this->admin())->post(route('admin.announcements.store'), $data + [
            'title' => 'Hackathon registration is open', 'body' => 'Teams of three, 14–15 November, in Lahore and online.',
            'link_url' => 'https://aqdesk.test/events', 'link_label' => 'Register now', 'tone' => 'success', 'audience' => 'everyone',
            'is_dismissible' => '1', 'timezone' => 'Asia/Karachi', 'starts_at' => '2026-11-02T09:00', 'ends_at' => '2026-11-15T18:00',
        ])->assertSessionHasNoErrors();
        $this->signOut();

        return Announcement::latest('id')->firstOrFail();
    }

    /** AN1 — a live announcement is a banner on every page for its audience; times are stored in UTC. */
    public function test_an1_banner(): void
    {
        $a = $this->announce();
        $this->assertSame('2026-11-02 04:00:00', $a->starts_at->utc()->format('Y-m-d H:i:s'));

        $this->get(route('home'))->assertSee('aria-label="Announcement: Hackathon registration is open"', false)
            ->assertSee('Good news: Hackathon registration is open')->assertSee('Register now');
        $this->actingAs($this->member())->get(route('questions.index'))->assertSee('Hackathon registration is open');

        $this->announce(['title' => 'Members: new dashboard widgets', 'audience' => 'members']);
        $this->announce(['title' => 'Starts tomorrow morning', 'starts_at' => '2026-11-03T09:00', 'ends_at' => null]);
        $this->announce(['title' => 'An announcement that ended', 'starts_at' => '2026-10-01T09:00', 'ends_at' => '2026-10-02T09:00']);
        $this->get(route('home'))->assertDontSee('Members: new dashboard widgets')->assertDontSee('Starts tomorrow morning')->assertDontSee('An announcement that ended');
        $this->actingAs($this->member())->get(route('home'))->assertSee('Members: new dashboard widgets');
    }

    /** AN2 — closing a banner: a member's choice follows the account, a visitor's the session; some cannot be closed. */
    public function test_an2_dismiss(): void
    {
        $a = $this->announce();
        $member = $this->member();

        $this->actingAs($member)->from(route('jobs.index'))->post(route('announcements.dismiss', $a))->assertRedirect(route('jobs.index'));
        $this->actingAs($member)->get(route('home'))->assertDontSee('Hackathon registration is open');
        $this->assertSame(1, $a->dismissals()->where('user_id', $member->id)->count());
        $this->signOut();

        $this->get(route('home'))->assertSee('Hackathon registration is open');
        $this->post(route('announcements.dismiss', $a));
        $this->get(route('home'))->assertDontSee('Hackathon registration is open');

        $fixed = $this->announce(['title' => 'Planned maintenance on Sunday', 'tone' => 'warning', 'is_dismissible' => '0']);
        $this->get(route('home'))->assertSee('Important: Planned maintenance on Sunday')->assertDontSee('Dismiss the announcement “Planned maintenance on Sunday”');
        $this->post(route('announcements.dismiss', $fixed));
        $this->get(route('home'))->assertSee('Planned maintenance on Sunday');
    }

    /** AN3 — Updates lists current and ended announcements (not upcoming); only managers reach the admin pages; input is checked. */
    public function test_an3_updates_page_and_validation(): void
    {
        $this->announce();
        $this->announce(['title' => 'An announcement that ended', 'starts_at' => '2026-10-01T09:00', 'ends_at' => '2026-10-02T09:00']);
        $this->announce(['title' => 'Starts tomorrow morning', 'starts_at' => '2026-11-03T09:00', 'ends_at' => null]);

        $this->get(route('announcements.index'))->assertOk()->assertSeeInOrder(['Now', 'Hackathon registration is open', 'Earlier', 'An announcement that ended'])
            ->assertDontSee('Starts tomorrow morning');

        $this->actingAs($this->member())->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.announcements.index'))->assertOk()->assertSee('Live')->assertSee('Upcoming')->assertSee('Ended');
        $this->actingAs($this->admin())->post(route('admin.announcements.store'), [
            'title' => 'Bad', 'body' => 'short', 'link_url' => 'javascript:alert(1)', 'tone' => 'loud', 'audience' => 'everyone',
            'timezone' => 'Asia/Karachi', 'starts_at' => '2026-11-05T09:00', 'ends_at' => '2026-11-04T09:00',
        ])->assertSessionHasErrors(['title', 'body', 'link_url', 'link_label', 'tone', 'ends_at']);
    }

    /** AN4 — editing changes the banner; deleting removes it everywhere. */
    public function test_an4_edit_and_delete(): void
    {
        $a = $this->announce();

        $this->actingAs($this->admin())->get(route('admin.announcements.edit', $a))->assertOk()->assertSee('value="2026-11-02T09:00"', false);
        $this->actingAs($this->admin())->put(route('admin.announcements.update', $a), [
            'title' => 'Hackathon: last places left', 'body' => 'Teams of three, 14–15 November, in Lahore and online.', 'tone' => 'warning',
            'audience' => 'everyone', 'is_dismissible' => '1', 'timezone' => 'Asia/Karachi', 'starts_at' => '2026-11-02T09:00',
        ])->assertSessionHasNoErrors();
        $this->signOut();
        $this->get(route('home'))->assertSee('Important: Hackathon: last places left')->assertDontSee('Register now');

        $this->actingAs($this->admin())->delete(route('admin.announcements.destroy', $a));
        $this->signOut();
        $this->get(route('home'))->assertDontSee('Hackathon: last places left');
        $this->assertSame(0, Announcement::count());
    }
}
