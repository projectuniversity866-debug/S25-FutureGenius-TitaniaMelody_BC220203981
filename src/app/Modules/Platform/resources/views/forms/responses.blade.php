@extends('layouts.app')
@section('title', 'Responses: '.$form->title)
@section('subtitle', 'Form builder')

@section('content')
<x-page-header :title="'Responses: '.$form->title" :subtitle="$responses->total().' '.\Illuminate\Support\Str::plural('response', $responses->total())"
               :breadcrumbs="['My forms' => route('forms.index'), $form->title => route('forms.edit', $form), 'Responses' => null]">
    @if ($responses->total())
        <x-slot:actions>
            <a href="{{ route('forms.export', $form) }}" class="btn btn-quiet btn-sm"><i class="bi bi-download me-1" aria-hidden="true"></i> Download CSV</a>
        </x-slot:actions>
    @endif
</x-page-header>

@if ($responses->total() === 0)
    <div class="card"><x-empty icon="inbox" title="No responses yet" message="Open the form and share its link to start collecting answers." /></div>
@else
    <h2 class="aq-h3 mb-3">Summary</h2>
    <div class="row g-3 mb-4">
        @foreach ($summary as $row)
            <div class="col-md-6">
                <x-card :title="$row['field']->label" class="h-100">
                    <p class="aq-text-xs aq-muted">{{ $row['field']->typeLabel() }} · {{ $row['answered'] }} {{ \Illuminate\Support\Str::plural('answer', $row['answered']) }}</p>
                    @isset($row['average'])
                        <p class="aq-text mb-2">Average <strong class="aq-num">{{ $row['average'] }}</strong> out of 5</p>
                    @endisset
                    @isset($row['counts'])
                        <x-bar-list noun="answer" empty="No answers yet"
                                    :items="collect($row['counts'])->map(fn ($n, $label) => ['label' => (string) $label, 'value' => $n])->values()->all()" />
                    @endisset
                    @isset($row['latest'])
                        @if ($row['latest']->isEmpty())
                            <p class="aq-text-sm aq-muted mb-0">No answers.</p>
                        @else
                            <ul class="aq-latest-answers">
                                @foreach ($row['latest'] as $text)<li>{{ \Illuminate\Support\Str::limit($text, 200) }}</li>@endforeach
                            </ul>
                        @endif
                    @endisset
                </x-card>
            </div>
        @endforeach
    </div>

    <h2 class="aq-h3 mb-3">Individual responses</h2>
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle aq-table">
                <caption class="visually-hidden">Every response, newest first</caption>
                <thead>
                    <tr>
                        <th scope="col">Submitted</th>
                        <th scope="col">Respondent</th>
                        @foreach ($form->fields as $field)<th scope="col">{{ $field->label }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($responses as $response)
                        @php $byField = $response->answers->keyBy('form_field_id'); @endphp
                        <tr>
                            <th scope="row" class="fw-normal"><span class="aq-num aq-text-sm">{{ $response->created_at->format('d M Y H:i') }}</span></th>
                            <td data-label="Respondent"><span>{{ $response->user?->name ?? 'Anonymous' }}</span></td>
                            @foreach ($form->fields as $field)
                                <td data-label="{{ $field->label }}"><span>{{ $byField->has($field->id) ? $byField[$field->id]->display() : '—' }}</span></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <nav aria-label="Response pages" class="mt-4">{{ $responses->links() }}</nav>
@endif
@endsection
