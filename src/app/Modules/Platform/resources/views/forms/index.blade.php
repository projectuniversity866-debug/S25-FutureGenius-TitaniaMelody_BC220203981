@extends('layouts.app')
@section('title', 'My forms')
@section('subtitle', 'Form builder')

@section('content')
<x-page-header title="My forms" subtitle="Build surveys, registration and intake forms, share the link, and read the responses here.">
    @can('create', \App\Modules\Platform\Models\Form::class)
        <x-slot:actions>
            <a href="{{ route('forms.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New form</a>
        </x-slot:actions>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
            <caption class="visually-hidden">Forms you have built</caption>
            <thead>
                <tr>
                    <th scope="col">Form</th>
                    <th scope="col">Status</th>
                    <th scope="col">Fields</th>
                    <th scope="col">Responses</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($forms as $form)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <a href="{{ route('forms.edit', $form) }}" class="fw-semibold text-decoration-none d-block">{{ $form->title }}</a>
                            <span class="aq-text-xs aq-muted">Created {{ $form->created_at->format('d M Y') }}</span>
                        </th>
                        <td data-label="Status"><span><x-status :value="$form->displayStatus() === 'draft' ? 'unpublished' : $form->displayStatus()" /></span></td>
                        <td data-label="Fields"><span class="aq-num">{{ $form->fields_count }}</span></td>
                        <td data-label="Responses"><span class="aq-num">{{ $form->responses_count }}</span></td>
                        <td data-label="Actions" class="aq-cell-actions">
                            <div class="aq-actions">
                                <a href="{{ route('forms.responses', $form) }}" class="btn btn-sm btn-quiet">Responses<span class="visually-hidden"> to “{{ $form->title }}”</span></a>
                                <a href="{{ route('forms.edit', $form) }}" class="btn btn-sm btn-quiet">Edit<span class="visually-hidden"> “{{ $form->title }}”</span></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty icon="ui-checks" title="No forms yet" message="A form takes a minute: add a title, then the questions."
                                     :action="auth()->user()->can('create', \App\Modules\Platform\Models\Form::class) ? ['url' => route('forms.create'), 'label' => 'Build your first form'] : null" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
