<?php

namespace App\Modules\Platform\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for every AQDesk notification. A module only describes the message;
 * this class delivers it in-app (database → the bell in the top bar) and by
 * email, with the same wording and link in both.
 *
 * Sent synchronously for now. Adding "implements ShouldQueue" moves delivery
 * to the queue worker once production runs one (roadmap 5.1).
 */
abstract class AppNotification extends Notification
{
    use Queueable;

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
