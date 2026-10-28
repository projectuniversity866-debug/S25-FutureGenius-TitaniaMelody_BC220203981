@extends('layouts.app')
@section('title', 'Report · '.$campaign->subject)
@section('subtitle', 'Newsletter campaign')

@section('content')
@php
    use App\Modules\Communications\Models\CampaignRecipient;
    $c = $campaign;
    $sent = $byStatus['sent'] ?? 0;
    $pct = fn (int $part, int $whole) => $whole ? round($part * 100 / $whole).'%' : '—';
    $bars = collect(CampaignRecipient::STATUSES)->map(fn ($s, $key) => ['label' => $s[0], 'value' => (int) ($byStatus[$key] ?? 0)])->values()->all();
@endphp
<x-page-header :title="'Report: '.$c->subject"
               :subtitle="$c->sent_at ? 'Finished '.$c->sent_at->format('d M Y, H:i').' UTC' : ($c->status === 'sending' ? 'Still sending' : null)"
               :breadcrumbs="['Newsletter' => route('admin.newsletter.index'), $c->subject => route('admin.newsletter.show', $c), 'Report' => null]" />

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><x-stat label="Recipients" :value="$c->recipients_count" icon="people" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Sent" :value="$sent" icon="check2-circle" :hint="$pct($sent, $c->recipients_count).' of recipients'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Opened" :value="$opened" icon="envelope-open" :hint="$pct($opened, $sent).' of sent · '.$opens.' '.($opens === 1 ? 'open' : 'opens').' in all'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Failed" :value="$byStatus['failed'] ?? 0" icon="exclamation-triangle" /></div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <x-card title="What happened to each address" icon="bar-chart">
            <x-bar-list :items="$bars" noun="address" />
            <p class="aq-text-xs aq-muted mt-3 mb-0">
                “Sent” means the mail server accepted it. Opens are counted when the email’s images load, so the real number is usually higher —
                many mail apps block images.
            </p>
        </x-card>
    </div>

    <div class="col-lg-7">
        <x-card :title="'Failed ('.$failed->count().')'" icon="exclamation-triangle" flush>
            @if ($failed->isEmpty())
                <p class="card-body aq-text-sm aq-muted mb-0">No failures.</p>
            @else
                <table class="table aq-table mb-0">
                    <caption class="visually-hidden">Addresses the campaign could not be sent to</caption>
                    <thead><tr><th scope="col">Email</th><th scope="col">Reason</th></tr></thead>
                    <tbody>
                        @foreach ($failed as $r)
                            <tr>
                                <td data-label="Email" class="text-break">{{ $r->email }}</td>
                                <td data-label="Reason" class="aq-text-sm">{{ $r->error }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($c->status === 'sent')
                    <form method="POST" action="{{ route('admin.newsletter.retry', $c) }}" class="card-body"
                          data-confirm-title="Retry {{ $failed->count() }} failed {{ $failed->count() === 1 ? 'address' : 'addresses' }}?" data-confirm="Only these addresses get the campaign; nobody it already reached gets it twice." data-confirm-button="Retry">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-soft btn-sm"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i> Retry failed</button>
                    </form>
                @endif
            @endif
        </x-card>
    </div>
</div>
@endsection
