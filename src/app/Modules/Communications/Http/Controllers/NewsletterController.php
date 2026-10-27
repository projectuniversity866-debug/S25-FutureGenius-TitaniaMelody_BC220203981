<?php

namespace App\Modules\Communications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communications\Http\Requests\SubscribeRequest;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Communications\Services\CampaignService;
use App\Modules\Communications\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** The public newsletter page, the confirmation link and one-click unsubscribe (FR7, roadmap 3.15), and open tracking (3.17). */
class NewsletterController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $subscriber = $user ? Subscriber::where('email', mb_strtolower($user->email))->first() : null;

        return view('communications::newsletter.show', compact('subscriber'));
    }

    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        $status = $this->subscriptions->subscribe($request->validated('email'), $request->user());
        $own = $request->user() && mb_strtolower($request->user()->email) === mb_strtolower(trim($request->validated('email')));

        // Anyone else's address gets the same answer whether or not it is on the list.
        return redirect()->route('newsletter.show')->with('status', $status === 'subscribed' && $own
            ? 'You are subscribed. Every newsletter has a one-click unsubscribe link.'
            : 'Check your inbox: we sent a link to confirm the subscription. Nothing is sent until you click it.');
    }

    public function confirm(Subscriber $subscriber): View
    {
        $ok = $this->subscriptions->confirm($subscriber);

        return view('communications::newsletter.result', [
            'icon'  => $ok ? 'envelope-check' : 'hourglass-bottom',
            'title' => $ok ? 'You are subscribed' : 'This link has expired',
            'text'  => $ok
                ? 'Thank you for confirming. Every newsletter ends with a one-click unsubscribe link.'
                : 'Confirmation links work for '.Subscriber::CONFIRM_DAYS.' days. Sign up again and we will send a new one.',
        ]);
    }

    /** The 1×1 image in a campaign email (3.17): count the open, always answer with the same image. */
    public function open(string $token, CampaignService $campaigns): Response
    {
        $campaigns->recordOpen($token);

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif', 'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    /** Opening the link only asks; mail scanners that follow links must not unsubscribe anyone. */
    public function confirmUnsubscribe(Subscriber $subscriber): View
    {
        return view('communications::newsletter.unsubscribe', compact('subscriber'));
    }

    /** The button on that page, and RFC 8058 one-click POSTs from mail apps (no CSRF token: bootstrap/app.php). */
    public function unsubscribe(Subscriber $subscriber): View
    {
        $this->subscriptions->unsubscribe($subscriber);

        return view('communications::newsletter.result', [
            'icon'  => 'envelope-slash',
            'title' => 'You are unsubscribed',
            'text'  => $subscriber->email.' will not receive the newsletter any more. Changed your mind? You can sign up again on the newsletter page.',
        ]);
    }
}
