@extends('layouts.app')
@section('title', 'My clients')
@section('subtitle', 'Consulting')

@section('content')
@php
    use App\Modules\Consulting\Models\ConsultingClient;
    use Illuminate\Support\Carbon;
    $tabs = [['label' => 'All', 'url' => route('consulting.clients.index', array_filter(['q' => $q])), 'active' => ! $status, 'count' => $counts->sum()]];
    foreach (ConsultingClient::STATUSES as $key => [$label]) {
        $tabs[] = ['label' => $label, 'url' => route('consulting.clients.index', array_filter(['status' => $key, 'q' => $q])), 'active' => $status === $key, 'count' => (int) ($counts[$key] ?? 0)];
    }
@endphp
<x-page-header title="My clients" subtitle="Everyone you consult for. Notes here are private to you.">
    <x-slot:actions>
        <a href="{{ route('consulting.sessions.index', ['side' => 'clients']) }}" class="btn btn-quiet"><i class="bi bi-calendar-week me-1" aria-hidden="true"></i> Sessions</a>
        <a href="{{ route('consulting.clients.create') }}" class="btn btn-accent"><i class="bi bi-person-plus me-1" aria-hidden="true"></i> Add a client</a>
    </x-slot:actions>
</x-page-header>

<x-tabs label="Client status" :items="$tabs" />

<form method="GET" action="{{ route('consulting.clients.index') }}" class="d-flex gap-2 mb-3" role="search">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <label for="client_q" class="visually-hidden">Search clients</label>
    <input type="search" id="client_q" name="q" value="{{ $q }}" class="form-control" placeholder="Name, email or organization…">
    <button type="submit" class="btn btn-soft">Search</button>
</form>

@if ($clients->isEmpty())
    <div class="card">
        <x-empty icon="people" title="No clients yet" message="Members who book a session appear here automatically. You can also add people by hand."
                 :action="['url' => route('consulting.clients.create'), 'label' => 'Add a client']" />
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table aq-table mb-0">
                <caption class="visually-hidden">Your clients</caption>
                <thead>
                    <tr><th scope="col">Client</th><th scope="col">Status</th><th scope="col">Sessions</th><th scope="col">Last</th><th scope="col">Next</th></tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                        <tr>
                            <th scope="row" class="fw-normal">
                                <a href="{{ route('consulting.clients.show', $client) }}" class="fw-semibold text-decoration-none">{{ $client->name }}</a>
                                <span class="d-block aq-text-xs aq-muted">
                                    {{ $client->organization ?: $client->email }}
                                    @if ($client->user_id) · <i class="bi bi-person-check" aria-hidden="true"></i> AQDesk member @endif
                                </span>
                            </th>
                            <td data-label="Status"><span class="badge text-bg-light"><i class="bi bi-{{ ConsultingClient::STATUSES[$client->status][1] }} me-1" aria-hidden="true"></i>{{ ConsultingClient::STATUSES[$client->status][0] }}</span></td>
                            <td data-label="Sessions">{{ $client->sessions_count }}</td>
                            <td data-label="Last session" class="aq-text-sm">{{ $client->last_at ? Carbon::parse($client->last_at)->setTimezone($profile->timezone)->format('j M Y') : '—' }}</td>
                            <td data-label="Next session" class="aq-text-sm">{{ $client->next_at ? Carbon::parse($client->next_at)->setTimezone($profile->timezone)->format('j M, H:i') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <nav aria-label="Client pages" class="mt-3">{{ $clients->links() }}</nav>
@endif
@endsection
