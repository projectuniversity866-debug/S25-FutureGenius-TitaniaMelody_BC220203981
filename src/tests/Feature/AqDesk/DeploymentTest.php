<?php

namespace Tests\Feature\AqDesk;

use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Suite DP — production readiness (5.1): queued notifications, HTTPS behind
 * a proxy and in production, the cPanel queue worker, safe production
 * settings, short overlap locks. Cases DP1–DP6.
 */
class DeploymentTest extends AqDeskTestCase
{
    /** DP1 — a notification goes to the queue instead of being sent during the request. */
    public function test_dp1_notifications_are_queued(): void
    {
        $asker = $this->member();
        $question = $this->askQuestionAs($asker);

        Queue::fake();
        $this->answerQuestionAs($this->otherMember(), $question);

        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notifiables->contains($asker));
        $this->assertSame(0, $asker->notifications()->count());
    }

    /** DP2 — X-Forwarded-Proto is honoured only from a trusted proxy. */
    public function test_dp2_https_behind_a_trusted_proxy(): void
    {
        $secureLogin = 'https://'.Str::after(route('login'), '://');

        $this->get('/', ['X-Forwarded-Proto' => 'https'])->assertOk()->assertDontSee($secureLogin, false);

        config(['trustedproxy.proxies' => '*']);
        $this->get('/', ['X-Forwarded-Proto' => 'https'])->assertOk()->assertSee($secureLogin, false);
    }

    /** DP3 — in production every generated link uses https. */
    public function test_dp3_production_forces_https(): void
    {
        $this->assertStringStartsWith('http://', url('/events'));

        config(['app.env' => 'production']);
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/events'));
        $this->assertStringStartsWith('https://', route('login'));
    }

    /** DP4 — the scheduler drains the queue only when the cPanel switch is on. */
    public function test_dp4_queue_worker_in_scheduler(): void
    {
        $worker = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'queue:work'));

        $this->assertNotNull($worker);
        $this->assertStringContainsString('--stop-when-empty', $worker->command);
        $this->assertTrue($worker->withoutOverlapping);

        config(['queue.worker_in_scheduler' => false]);
        $this->assertFalse($worker->filtersPass($this->app));

        config(['queue.worker_in_scheduler' => true]);
        $this->assertTrue($worker->filtersPass($this->app));
    }

    /** DP6 — overlap locks expire within minutes, so a killed run cannot stop the queue for a day. */
    public function test_dp6_overlap_locks_expire_quickly(): void
    {
        $locked = collect($this->app->make(Schedule::class)->events())->filter(fn ($event) => $event->withoutOverlapping);

        $this->assertCount(3, $locked);
        foreach ($locked as $event) {
            $this->assertLessThanOrEqual(10, $event->expiresAt, $event->command.' keeps its lock too long');
        }

        $worker = $locked->first(fn ($event) => str_contains((string) $event->command, 'queue:work'));
        $this->assertSame(5, $worker->expiresAt);
    }

    /** DP5 — the production template is safe and holds no secrets. */
    public function test_dp5_production_settings_template(): void
    {
        $env = file_get_contents(base_path('.env.production.example'));

        foreach (['APP_ENV=production', 'APP_DEBUG=false', 'SESSION_SECURE_COOKIE=true', 'QUEUE_CONNECTION=database', 'MAIL_MAILER=smtp', 'DB_CONNECTION=mysql'] as $line) {
            $this->assertMatchesRegularExpression('/^'.preg_quote($line, '/').'$/m', $env);
        }
        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $env);
        $this->assertDoesNotMatchRegularExpression('/^MAIL_PASSWORD=(?!<).+$/m', $env);
        $this->assertFileExists(base_path('deploy.sh'));
    }
}
