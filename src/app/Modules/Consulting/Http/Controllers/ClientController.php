<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Http\Requests\ClientNoteRequest;
use App\Modules\Consulting\Http\Requests\ConsultingClientRequest;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Consulting\Models\ConsultingClientNote;
use App\Modules\Consulting\Services\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A consultant's clients (FR6 "Client CRM", roadmap 3.13). */
class ClientController extends Controller
{
    public function __construct(private ClientService $clients) {}

    public function index(Request $request): View|RedirectResponse
    {
        $profile = $this->profileOf($request);
        if (! $profile) {
            return redirect()->route('consulting.profile')->with('status', 'Set up your consultant profile first.');
        }

        $status = array_key_exists((string) $request->query('status'), ConsultingClient::STATUSES) ? $request->query('status') : null;
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        $clients = $profile->clients()
            ->when($status, fn ($w) => $w->where('status', $status))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('organization', 'like', "%{$q}%")))
            ->withCount(['consultations as sessions_count' => fn ($c) => $c->where('status', '!=', 'cancelled')])
            ->withMin(['consultations as next_at' => fn ($c) => $c->where('status', 'confirmed')->where('starts_at', '>', now())], 'starts_at')
            ->withMax(['consultations as last_at' => fn ($c) => $c->where('status', '!=', 'cancelled')->where('starts_at', '<=', now())], 'starts_at')
            ->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $counts = $profile->clients()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('consulting::clients.index', compact('profile', 'clients', 'status', 'q', 'counts'));
    }

    public function create(Request $request): View
    {
        abort_unless($this->profileOf($request), 404);

        return view('consulting::clients.form', ['client' => null]);
    }

    public function store(ConsultingClientRequest $request): RedirectResponse
    {
        $client = $this->clients->add($request->user()->consultantProfile, $request->validated());

        return redirect()->route('consulting.clients.show', $client)->with('status', $client->name.' added to your clients.');
    }

    public function show(ConsultingClient $client): View
    {
        $this->authorize('manage', $client);

        $client->load(['notes', 'user', 'proposals', 'consultations' => fn ($c) => $c->with('profile')]);

        return view('consulting::clients.show', compact('client'));
    }

    public function edit(ConsultingClient $client): View
    {
        $this->authorize('manage', $client);

        return view('consulting::clients.form', compact('client'));
    }

    public function update(ConsultingClientRequest $request, ConsultingClient $client): RedirectResponse
    {
        $this->clients->update($client, $request->validated());

        return redirect()->route('consulting.clients.show', $client)->with('status', 'Client saved.');
    }

    public function destroy(ConsultingClient $client): RedirectResponse
    {
        $this->authorize('manage', $client);

        $this->clients->delete($client);

        return redirect()->route('consulting.clients.index')->with('status', $client->name.' removed from your clients. Their sessions stay in your calendar.');
    }

    public function storeNote(ClientNoteRequest $request, ConsultingClient $client): RedirectResponse
    {
        $this->clients->addNote($client, $request->validated('body'));

        return redirect()->to(route('consulting.clients.show', $client).'#notes')->with('status', 'Note saved.');
    }

    public function destroyNote(ConsultingClient $client, ConsultingClientNote $note): RedirectResponse
    {
        $this->authorize('manage', $client);
        abort_unless($note->consulting_client_id === $client->id, 404);

        $this->clients->deleteNote($note);

        return redirect()->to(route('consulting.clients.show', $client).'#notes')->with('status', 'Note deleted.');
    }

    private function profileOf(Request $request): ?ConsultantProfile
    {
        $this->authorize('offer', ConsultantProfile::class);

        return $request->user()->consultantProfile;
    }
}
