<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Communications\Jobs\SendCampaignBatch;
use App\Modules\Communications\Mail\CampaignMail;
use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Communications\Notifications\ConfirmSubscription;
use App\Modules\Communications\Services\CampaignService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * Suite NL — newsletter subscription with double opt-in and one-click
 * unsubscribe (3.15), and suite CM — campaigns with scheduled, queued
 * sending (3.16), and suite RP — delivery reports (3.17). Cases NL1–NL4,
 * CM1–CM4, RP1–RP3.
 */
class NewsletterTest extends AqDeskTestCase
{
    private function subscriber(string $email, string $status = 'subscribed'): Subscriber
    {
        return Subscriber::create(['email' => $email, 'status' => $status, 'token' => Subscriber::newToken(), 'confirmed_at' => $status === 'subscribed' ? now() : null]);
    }

    private function draft(array $data = []): Campaign
    {
        $this->actingAs($this->admin())->post(route('admin.newsletter.store'), $data + [
            'subject' => 'November on AQDesk', 'body' => "## New this month\n\nTwo learning paths and a **hackathon**.",
        ])->assertSessionHasNoErrors();

        return Campaign::latest('id')->firstOrFail();
    }

    // ------------------------------------------------------------------ 3.15

    /** NL1 — a visitor gets a confirmation link; nothing else; the same answer for an address already on the list. */
    public function test_nl1_double_opt_in(): void
    {
        Notification::fake();

        $this->post(route('newsletter.subscribe'), ['email' => 'Reader@Example.com', 'consent' => '1'])->assertRedirect(route('newsletter.show'))->assertSessionHas('status', fn ($s) => str_contains($s, 'Check your inbox'));
        $subscriber = Subscriber::firstOrFail();
        $this->assertSame(['reader@example.com', 'pending'], [$subscriber->email, $subscriber->status]);
        Notification::assertSentOnDemand(ConfirmSubscription::class, fn ($n, $channels, AnonymousNotifiable $to) => $to->routes['mail'] === 'reader@example.com');

        $this->subscriber('already@example.com');
        $this->post(route('newsletter.subscribe'), ['email' => 'already@example.com', 'consent' => '1'])->assertSessionHas('status', fn ($s) => str_contains($s, 'Check your inbox'));
        Notification::assertSentOnDemandTimes(ConfirmSubscription::class, 1);

        $this->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])->assertSessionHasErrors(['email', 'consent']);
    }

    /** NL2 — the link confirms; it expires after 7 days; signing up again replaces it. */
    public function test_nl2_confirm_and_expiry(): void
    {
        Notification::fake();
        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'consent' => '1']);
        $first = Subscriber::firstOrFail()->token;

        $this->travel(8)->days();
        $this->get(route('newsletter.confirm', $first))->assertOk()->assertSee('This link has expired');
        $this->assertSame('pending', Subscriber::first()->status);

        $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'consent' => '1']);
        $second = Subscriber::firstOrFail()->token;
        $this->assertNotSame($first, $second);
        $this->get(route('newsletter.confirm', $first))->assertNotFound();
        $this->get(route('newsletter.confirm', $second))->assertOk()->assertSee('You are subscribed');
        $this->assertSame('subscribed', Subscriber::first()->status);
        $this->assertNotNull(Subscriber::first()->confirmed_at);
    }

    /** NL3 — a member subscribing their confirmed account email is in at once, and sees it on the page. */
    public function test_nl3_member_subscribes_own_email(): void
    {
        Notification::fake();
        $member = $this->member();

        $this->actingAs($member)->post(route('newsletter.subscribe'), ['email' => strtoupper($member->email), 'consent' => '1'])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'You are subscribed'));
        Notification::assertNothingSent();
        $this->assertSame([$member->id, 'subscribed'], [Subscriber::first()->user_id, Subscriber::first()->status]);
        $this->actingAs($member)->get(route('newsletter.show'))->assertSee($member->email.'</strong> is subscribed', false)->assertSee('Unsubscribe');

        $this->actingAs($member)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1']);
        $this->assertSame(0, Subscriber::count());   // a deleted account leaves the list
    }

    /** NL4 — opening the unsubscribe link only asks; the button (or a mail app's one-click POST) unsubscribes. */
    public function test_nl4_one_click_unsubscribe(): void
    {
        $subscriber = $this->subscriber('reader@example.com');

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertOk()->assertSee('Stop sending the AQDesk newsletter to');
        $this->assertSame('subscribed', $subscriber->fresh()->status);

        $this->post(route('newsletter.unsubscribe.store', $subscriber->token), ['List-Unsubscribe' => 'One-Click'])->assertOk()->assertSee('You are unsubscribed');
        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
        $this->post(route('newsletter.unsubscribe.store', $subscriber->token))->assertOk();   // again: harmless
        $this->get(route('newsletter.unsubscribe', str_repeat('x', 64)))->assertNotFound();
        $this->assertContains('newsletter/unsubscribe/*', (new \ReflectionProperty(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, 'neverVerify'))->getValue());
    }

    // ------------------------------------------------------------------ 3.16

    /** CM1 — only senders reach campaigns; drafts are edited and tested; the test has no unsubscribe header. */
    public function test_cm1_drafts_and_test_send(): void
    {
        Mail::fake();
        $this->actingAs($this->member())->get(route('admin.newsletter.index'))->assertForbidden();
        $this->actingAs($this->expert())->post(route('admin.newsletter.store'), ['subject' => 'Not allowed here', 'body' => 'Experts cannot send newsletters at all.'])->assertForbidden();
        $this->actingAs($this->admin())->post(route('admin.newsletter.store'), ['subject' => 'Hi', 'body' => 'short'])->assertSessionHasErrors(['subject', 'body']);

        $campaign = $this->draft();
        $this->actingAs($this->admin())->get(route('admin.newsletter.show', $campaign))->assertOk()->assertSee('<strong>hackathon</strong>', false);
        $this->actingAs($this->admin())->put(route('admin.newsletter.update', $campaign), ['subject' => 'November news on AQDesk', 'body' => $campaign->body])->assertSessionHasNoErrors();
        $this->actingAs($this->admin())->post(route('admin.newsletter.test', $campaign));

        Mail::assertSent(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo($this->admin()->email) && $m->unsubscribeUrl === null && $m->headers()->text === []);
        $this->assertSame(['draft', 'November news on AQDesk'], [$campaign->fresh()->status, $campaign->fresh()->subject]);
    }

    /** CM2 — sending reaches each confirmed subscriber once, with one-click headers; a sent campaign is locked. */
    public function test_cm2_send_now(): void
    {
        Mail::fake();
        $a = $this->subscriber('a@example.com');
        $this->subscriber('b@example.com');
        $this->subscriber('pending@example.com', 'pending');
        $this->subscriber('gone@example.com', 'unsubscribed');
        $campaign = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.newsletter.send', $campaign))->assertSessionHasNoErrors();
        $campaign->refresh();

        $this->assertSame(['sent', 2], [$campaign->status, $campaign->recipients_count]);
        Mail::assertSentCount(2);
        Mail::assertSent(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo('a@example.com')
            && $m->headers()->text['List-Unsubscribe'] === '<'.route('newsletter.unsubscribe', $a->token).'>'
            && $m->headers()->text['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click');
        Mail::assertNotSent(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo('pending@example.com') || $m->hasTo('gone@example.com'));

        $this->actingAs($this->admin())->put(route('admin.newsletter.send', $campaign))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.newsletter.edit', $campaign))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.newsletter.show', $campaign))->assertSee('2 of 2 sent');
        $this->assertFalse(app(CampaignService::class)->start($campaign));   // a second start never re-sends
        Mail::assertSentCount(2);
    }

    /** CM3 — scheduling in a chosen time zone, cancelling, and the every-minute command sending when due. */
    public function test_cm3_schedule(): void
    {
        Mail::fake();
        $this->subscriber('a@example.com');
        $this->travelTo(Carbon::parse('2026-11-02 08:00', 'Asia/Karachi'));
        $campaign = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.newsletter.schedule', $campaign), ['send_at' => '2026-11-02T07:00', 'timezone' => 'Asia/Karachi'])->assertSessionHasErrors('send_at');
        $this->actingAs($this->admin())->put(route('admin.newsletter.schedule', $campaign), ['send_at' => '2026-11-03T09:30', 'timezone' => 'Asia/Karachi'])->assertSessionHasNoErrors();
        $campaign->refresh();
        $this->assertSame(['scheduled', '2026-11-03 04:30:00'], [$campaign->status, $campaign->scheduled_at->utc()->format('Y-m-d H:i:s')]);
        $this->actingAs($this->admin())->get(route('admin.newsletter.index'))->assertSee('Goes out Tue 3 Nov 2026, 09:30 PKT');
        $this->actingAs($this->admin())->get(route('admin.newsletter.edit', $campaign))->assertForbidden();

        $this->actingAs($this->admin())->put(route('admin.newsletter.unschedule', $campaign));
        $this->assertSame(['draft', null], [$campaign->fresh()->status, $campaign->fresh()->scheduled_at]);
        $this->actingAs($this->admin())->put(route('admin.newsletter.schedule', $campaign), ['send_at' => '2026-11-03T09:30', 'timezone' => 'Asia/Karachi']);

        $this->artisan('newsletter:send-due')->expectsOutputToContain('Started 0 campaigns');
        Mail::assertNothingSent();
        $this->travelTo(Carbon::parse('2026-11-03 09:31', 'Asia/Karachi'));
        $this->artisan('newsletter:send-due')->expectsOutputToContain('Started 1 campaign');
        Mail::assertSentCount(1);
        $this->assertSame('sent', $campaign->fresh()->status);
        $this->artisan('schedule:list')->expectsOutputToContain('newsletter:send-due')->assertSuccessful();
    }

    /** CM4 — queued in batches of 50; a batch run twice sends once; someone who unsubscribes meanwhile is skipped. */
    public function test_cm4_queued_batches_exactly_once(): void
    {
        Queue::fake();
        Mail::fake();
        foreach (range(1, 120) as $i) {
            $this->subscriber("reader{$i}@example.com");
        }
        $campaign = $this->draft();
        $this->actingAs($this->admin())->put(route('admin.newsletter.send', $campaign));

        Queue::assertPushed(SendCampaignBatch::class, 3);
        $this->assertSame(['sending', 120], [$campaign->fresh()->status, $campaign->fresh()->recipients_count]);
        Mail::assertNothingSent();

        Subscriber::where('email', 'reader1@example.com')->update(['status' => 'unsubscribed']);
        $jobs = Queue::pushed(SendCampaignBatch::class);
        foreach ($jobs as $job) {
            $job->handle(app(CampaignService::class));
        }
        $jobs->first()->handle(app(CampaignService::class));   // a retried batch

        Mail::assertSentCount(119);
        $this->assertSame(1, $campaign->recipients()->where('status', 'skipped')->count());
        $this->assertSame('sent', $campaign->fresh()->status);
    }

    // ------------------------------------------------------------------ 3.17

    /** @return int emails the "array" mail transport has accepted so far */
    private function mailbox(): int
    {
        return app('mailer')->getSymfonyTransport()->messages()->count();
    }

    /** RP1 — each email carries its own tracking image; opens are counted once per person and in total. */
    public function test_rp1_open_tracking(): void
    {
        Mail::fake();
        foreach (['a', 'b', 'c'] as $who) {
            $this->subscriber("{$who}@example.com");
        }
        $campaign = $this->draft();
        $this->actingAs($this->admin())->put(route('admin.newsletter.send', $campaign));

        $a = $campaign->recipients()->where('email', 'a@example.com')->firstOrFail();
        $this->assertSame(40, strlen($a->token));
        Mail::assertSent(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo('a@example.com') && $m->openUrl === route('newsletter.open', $a->token));

        $this->signOut();
        $this->get(route('newsletter.open', $a->token))->assertOk()->assertHeader('Content-Type', 'image/gif')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->get(route('newsletter.open', $a->token));
        $this->get(route('newsletter.open', str_repeat('z', 40)))->assertOk();   // unknown: same image, nothing recorded
        $this->assertSame(2, $a->fresh()->open_count);
        $this->assertNotNull($a->fresh()->opened_at);

        $this->actingAs($this->member())->get(route('admin.newsletter.report', $campaign))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.newsletter.report', $campaign))->assertOk()
            ->assertSee('33% of sent · 2 opens in all')->assertSee('100% of recipients')->assertSee('No failures.');
    }

    /** RP2 — a failure keeps its reason; Retry failed sends only to those addresses. */
    public function test_rp2_failures_and_retry(): void
    {
        $this->subscriber('good@example.com');
        $bad = $this->subscriber('broken@@example');   // rejected by the mailer
        $campaign = $this->draft();

        $this->actingAs($this->admin())->put(route('admin.newsletter.send', $campaign));
        $this->assertSame(1, $this->mailbox());
        $failed = $campaign->recipients()->where('status', 'failed')->firstOrFail();
        $this->assertNotEmpty($failed->error);
        $this->actingAs($this->admin())->get(route('admin.newsletter.report', $campaign))->assertSee('broken@@example')->assertSee('Retry failed');

        $failed->update(['email' => 'fixed@example.com']);   // e.g. the address was corrected
        $this->actingAs($this->admin())->put(route('admin.newsletter.retry', $campaign))->assertSessionHas('status', '1 failed address is back in the queue.');

        $this->assertSame(2, $this->mailbox());   // good@ was not sent to again
        $this->assertSame(['sent', null], [$failed->fresh()->status, $failed->fresh()->error]);
        $this->assertSame('sent', $campaign->fresh()->status);
        $this->actingAs($this->admin())->put(route('admin.newsletter.retry', $campaign))->assertSessionHas('status', 'Nothing to retry.');
        $this->assertNotNull($bad->id);
    }

    /** RP3 — the report shows every outcome; a campaign still sending cannot be retried. */
    public function test_rp3_report_outcomes(): void
    {
        Queue::fake();
        $this->subscriber('a@example.com');
        $this->subscriber('b@example.com');
        $campaign = $this->draft();
        $this->actingAs($this->admin())->put(route('admin.newsletter.send', $campaign));

        $this->actingAs($this->admin())->get(route('admin.newsletter.report', $campaign))->assertOk()->assertSee('Still sending')->assertSee('Waiting in the queue')->assertDontSee('Retry failed');
        $this->assertSame(0, app(CampaignService::class)->retryFailed($campaign));
        $this->actingAs($this->admin())->get(route('admin.newsletter.show', $campaign))->assertSee('Delivery report');
    }
}
