<?php

namespace App\Modules\Platform\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for every AQDesk notification. A module only describes the message;
 * this class delivers it in-app (database → the bell in the top bar) and by
 * email, with the same wording and link in both.
 *
 * Delivered by the queue worker, only after the database transaction that
 * raised it commits, so a slow mail server never slows the page and nobody
 * is told about a change that was rolled back (roadmap 5.1). With
 * QUEUE_CONNECTION=sync (local, tests) it is sent at once.
 */
abstract class AppNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /** Attempts before the job lands in failed_jobs (pruned weekly). */
    public int $tries = 3;

    /** Short headline, e.g. "New answer to your question". */
    abstract protected function title(): string;

    /** One sentence with the detail. */
    abstract protected function body(): string;

    /** Where clicking the notification takes the user. */
    abstract protected function url(): string;

    /** Bootstrap Icons name shown beside the notification. */
    protected function icon(): string
    {
        return 'bell';
    }

    /**
     * Preference group the user can switch off for email in Account settings
     * ("answers", "applications"). In-app notifications are always kept.
     */
    protected function category(): string
    {
        return 'general';
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $wantsMail = ! method_exists($notifiable, 'wantsEmailFor') || $notifiable->wantsEmailFor($this->category());

        return $wantsMail ? ['database', 'mail'] : ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body'  => $this->body(),
            'url'   => $this->url(),
            'icon'  => $this->icon(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' — AQDesk')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->body())
            ->action('Open in AQDesk', $this->url());
    }
}
