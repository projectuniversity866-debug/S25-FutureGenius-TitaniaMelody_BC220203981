<?php

namespace App\Modules\Communications\Notifications;

use App\Modules\Communications\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The double opt-in email (roadmap 3.15). Sent to an address, not an
 * account, so it is a plain mail notification rather than an
 * AppNotification (which also writes to the in-app bell).
 */
class ConfirmSubscription extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    private string $link;

    public function __construct(Subscriber $subscriber)
    {
        $this->link = route('newsletter.confirm', $subscriber->token);
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your AQDesk newsletter subscription')
            ->line('Someone — hopefully you — asked to receive the AQDesk newsletter at this address.')
            ->action('Yes, subscribe me', $this->link)
            ->line('The link works for '.Subscriber::CONFIRM_DAYS.' days. If you did not ask for this, ignore this email and nothing will be sent to you.');
    }
}
