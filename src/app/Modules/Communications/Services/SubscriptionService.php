<?php

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Models\Subscriber;
use App\Modules\Communications\Notifications\ConfirmSubscription;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Notification;

/** Newsletter sign-up with double opt-in, and one-click unsubscribe (FR7, roadmap 3.15). */
class SubscriptionService
{
    /**
     * A member subscribing their own confirmed account email is subscribed at
     * once; any other address gets a confirmation link first. The answer is
     * the same whether or not the address was already on the list.
     *
     * @return string "subscribed" or "pending"
     */
    public function subscribe(string $email, ?User $user): string
    {
        $email = mb_strtolower(trim($email));
        $ownEmail = $user && $user->hasVerifiedEmail() && mb_strtolower($user->email) === $email;
        $subscriber = Subscriber::firstOrNew(['email' => $email]);

        if ($subscriber->status === 'subscribed') {
            return 'subscribed';
        }

        $subscriber->fill([
            'user_id' => $ownEmail ? $user->id : $subscriber->user_id,
            'token'   => Subscriber::newToken(),   // a new link; earlier ones stop working
            'status'  => $ownEmail ? 'subscribed' : 'pending',
            'confirmed_at' => $ownEmail ? now() : null,
            'unsubscribed_at' => null,
        ]);
        $subscriber->save();   // the new token changes updated_at, so the confirmation window starts now

        if (! $ownEmail) {
            Notification::route('mail', $email)->notify(new ConfirmSubscription($subscriber));
        }

        return $subscriber->status;
    }

    /** @return bool false when the link has expired */
    public function confirm(Subscriber $subscriber): bool
    {
        if ($subscriber->status !== 'pending') {
            return true;
        }
        if ($subscriber->confirmationExpired()) {
            return false;
        }

        $subscriber->update(['status' => 'subscribed', 'confirmed_at' => now()]);

        return true;
    }

    public function unsubscribe(Subscriber $subscriber): void
    {
        if ($subscriber->status !== 'unsubscribed') {
            $subscriber->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        }
    }
}
