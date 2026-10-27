<?php

namespace App\Modules\Communications\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Communications\Http\Requests\CampaignRequest;
use App\Modules\Communications\Http\Requests\ScheduleCampaignRequest;
use App\Modules\Communications\Models\Campaign;
use App\Modules\Communications\Models\Subscriber;
use App\Modules\Communications\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Administration → Newsletter: write, test, schedule and send campaigns (FR7, roadmap 3.16). */
class CampaignController extends Controller
{
    public function __construct(private CampaignService $campaigns) {}

    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status'), Campaign::STATUSES) ? $request->query('status') : null;

        $campaigns = Campaign::query()->when($status, fn ($q) => $q->where('status', $status))
            ->withCount(['recipients as sent_count' => fn ($r) => $r->where('status', 'sent')])
            ->latest('updated_at')->paginate(20)->withQueryString();
        $counts = Campaign::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $subscribers = Subscriber::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('communications::admin.index', compact('campaigns', 'status', 'counts', 'subscribers'));
    }

    public function create(): View
    {
        return view('communications::admin.form', ['campaign' => null]);
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaigns->create($request->user(), $request->validated());

        return redirect()->route('admin.newsletter.show', $campaign)->with('status', 'Draft saved. Send yourself a test, then schedule or send it.');
    }

    public function show(Campaign $campaign): View
    {
        $progress = $campaign->recipients()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $audience = Subscriber::where('status', 'subscribed')->count();

        return view('communications::admin.show', compact('campaign', 'progress', 'audience'));
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        return view('communications::admin.form', compact('campaign'));
    }

    public function update(CampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->campaigns->update($campaign, $request->validated());

        return redirect()->route('admin.newsletter.show', $campaign)->with('status', 'Draft saved.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaigns->delete($campaign);

        return redirect()->route('admin.newsletter.index')->with('status', 'Draft deleted.');
    }

    public function test(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->campaigns->sendTest($campaign, $request->user());

        return back()->with('status', 'Test sent to '.$request->user()->email.'.');
    }

    public function schedule(ScheduleCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $campaign->timezone = $request->validated('timezone');
        $this->campaigns->schedule($campaign, $request->sendAt());

        return back()->with('status', 'Scheduled for '.$campaign->scheduleLabel().'.');
    }

    public function unschedule(Campaign $campaign): RedirectResponse
    {
        $this->authorize('unschedule', $campaign);

        $this->campaigns->unschedule($campaign);

        return back()->with('status', 'Schedule cancelled; the campaign is a draft again.');
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        $this->authorize('send', $campaign);

        $this->campaigns->start($campaign);

        return back()->with('status', 'Sending started. Emails go out from the queue; this page shows the progress.');
    }
}
