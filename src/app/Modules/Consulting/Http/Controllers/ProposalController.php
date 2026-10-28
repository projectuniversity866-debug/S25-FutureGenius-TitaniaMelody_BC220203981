<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Http\Requests\ProposalDecisionRequest;
use App\Modules\Consulting\Http\Requests\ProposalRequest;
use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Consulting\Models\Proposal;
use App\Modules\Consulting\Services\ProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Proposals: written by consultants for a client, answered by the client (FR6, roadmap 3.14). */
class ProposalController extends Controller
{
    public function __construct(private ProposalService $proposals) {}

    /** "Proposals": the ones sent to you, and — for consultants — the ones you wrote, by status. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->consultantProfile;
        $side = $profile && $request->query('side') !== 'received' ? 'sent' : 'received';
        $status = array_key_exists((string) $request->query('status'), Proposal::STATUSES) ? $request->query('status') : null;

        $base = fn () => $side === 'sent'
            ? Proposal::where('consultant_profile_id', $profile->id)
            : Proposal::where('status', '!=', 'draft')->whereHas('client', fn ($c) => $c->where('user_id', $user->id));

        $proposals = $base()->when($status, fn ($q) => $q->where('status', $status))
            ->with('client', 'profile.user')->latest('updated_at')->paginate(15)->withQueryString();
        $counts = $base()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('consulting::proposals.index', compact('proposals', 'side', 'status', 'counts', 'profile'));
    }

    public function create(ConsultingClient $client): View
    {
        $this->authorize('manage', $client);

        return view('consulting::proposals.form', ['client' => $client, 'proposal' => null]);
    }

    public function store(ProposalRequest $request, ConsultingClient $client): RedirectResponse
    {
        $proposal = $this->proposals->create($client, $request->validated());

        return redirect()->route('consulting.proposals.show', $proposal)->with('status', 'Draft saved. Check it, then send it to '.$client->name.'.');
    }

    public function show(Proposal $proposal): View
    {
        $this->authorize('view', $proposal);

        return view('consulting::proposals.show', ['proposal' => $proposal->load('client.user', 'profile.user')]);
    }

    public function edit(Proposal $proposal): View
    {
        $this->authorize('update', $proposal);

        return view('consulting::proposals.form', ['client' => $proposal->client, 'proposal' => $proposal]);
    }

    public function update(ProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->proposals->update($proposal, $request->validated());

        return redirect()->route('consulting.proposals.show', $proposal)->with('status', 'Draft saved.');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $this->proposals->delete($proposal);

        return redirect()->route('consulting.clients.show', $proposal->client)->with('status', 'Draft deleted.');
    }

    public function send(Proposal $proposal): RedirectResponse
    {
        $this->authorize('send', $proposal);

        $this->proposals->send($proposal);

        return back()->with('status', $proposal->client->user_id
            ? 'Sent. '.$proposal->client->name.' has been told and can accept or decline it here.'
            : 'Marked as sent. '.$proposal->client->name.' is not on AQDesk, so share it with them and record their answer here.');
    }

    public function decide(ProposalDecisionRequest $request, Proposal $proposal): RedirectResponse
    {
        $decision = $request->validated('decision');
        $this->proposals->decide($proposal, $request->user(), $decision, $request->validated('note'));

        return back()->with('status', $decision === 'accepted' ? 'Proposal accepted.' : 'Proposal declined.');
    }

    public function complete(Proposal $proposal): RedirectResponse
    {
        $this->authorize('complete', $proposal);

        $this->proposals->complete($proposal);

        return back()->with('status', 'Marked as completed.');
    }
}
