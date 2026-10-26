@extends('layouts.app')
@section('title', 'Build: '.$form->title)
@section('subtitle', 'Form builder')

@section('content')
@php $status = $form->displayStatus(); @endphp
<x-page-header :title="$form->title" subtitle="Add questions, preview, then open the form and share its link."
               :breadcrumbs="['My forms' => route('forms.index'), $form->title => null]">
    <x-slot:actions>
        <a href="{{ route('forms.fill', $form) }}" class="btn btn-quiet btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-eye me-1" aria-hidden="true"></i> {{ $form->status === 'draft' ? 'Preview' : 'Open form' }}<span class="visually-hidden"> (opens in a new tab)</span>
        </a>
        <a href="{{ route('forms.responses', $form) }}" class="btn btn-quiet btn-sm">
            <i class="bi bi-bar-chart me-1" aria-hidden="true"></i> Responses ({{ $form->responses_count }})
        </a>
    </x-slot:actions>
</x-page-header>

{{-- Status and share link. --}}
<section class="card mb-4 aq-form-status" aria-labelledby="status-heading">
    <div class="card-body">
        <div class="min-w-0">
            <h2 id="status-heading" class="aq-label mb-1">Status</h2>
            <x-status :value="$status === 'draft' ? 'unpublished' : $status" />
            @if ($status === 'open')
                <p class="aq-text-sm mb-0 mt-2">
                    Share this link: <a href="{{ route('forms.fill', $form) }}">{{ route('forms.fill', $form) }}</a>
                </p>
            @elseif ($status === 'draft')
                <p class="aq-text-sm aq-muted mb-0 mt-2">Only you can see the form until you open it.</p>
            @endif
            @error('status')<p class="aq-text-sm aq-danger mb-0 mt-2" role="alert">{{ $message }}</p>@enderror
        </div>
        <form method="POST" action="{{ route('forms.status', $form) }}" class="d-flex gap-2">
            @csrf
            @method('PUT')
            @if ($form->status !== 'open')
                <button type="submit" name="status" value="open" class="btn btn-accent btn-sm">Open for responses</button>
            @else
                <button type="submit" name="status" value="closed" class="btn btn-quiet btn-sm">Close</button>
            @endif
            @if ($form->status !== 'draft' && $form->responses_count === 0)
                <button type="submit" name="status" value="draft" class="btn btn-quiet btn-sm">Back to draft</button>
            @endif
        </form>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Questions ({{ $form->fields->count() }})" icon="ui-checks" id="fields">
            @if ($form->fields->isEmpty())
                <p class="aq-text-sm aq-muted">No questions yet — add the first one below.</p>
            @endif
            <ol class="aq-field-list">
                @foreach ($form->fields as $i => $field)
                    <li id="field-{{ $field->id }}">
                        <div class="d-flex align-items-start gap-2">
                            <span class="aq-field-icon" aria-hidden="true"><i class="bi bi-{{ $field->typeIcon() }}"></i></span>
                            <div class="min-w-0 flex-grow-1">
                                <strong class="aq-text-sm">{{ $field->label }}</strong>
                                @if ($field->is_required)<span class="aq-danger" aria-hidden="true"> *</span><span class="visually-hidden">(required)</span>@endif
                                <span class="d-block aq-text-xs aq-muted">
                                    {{ $field->typeLabel() }}@if ($field->hasOptions()) · {{ implode(', ', $field->options ?? []) }}@endif
                                </span>
                            </div>
                            <div class="aq-actions flex-shrink-0">
                                @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $dir => $icon)
                                    <form method="POST" action="{{ route('forms.fields.move', $field) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="direction" value="{{ $dir }}">
                                        <button type="submit" class="btn btn-sm btn-quiet" @disabled(($dir === 'up' && $loop->parent->first) || ($dir === 'down' && $loop->parent->last))>
                                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i><span class="visually-hidden">Move “{{ $field->label }}” {{ $dir }}</span>
                                        </button>
                                    </form>
                                @endforeach
                                <form method="POST" action="{{ route('forms.fields.destroy', $field) }}"
                                      data-confirm-title="Delete this question?" data-confirm="“{{ $field->label }}” and every answer to it are deleted."
                                      data-confirm-button="Delete question">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Delete “{{ $field->label }}”</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <details class="aq-comment-add mt-2">
                            <summary>Edit question</summary>
                            <form method="POST" action="{{ route('forms.fields.update', $field) }}">
                                @csrf
                                @method('PUT')
                                @include('platform::forms._field-form', ['field' => $field])
                                <button type="submit" class="btn btn-sm btn-accent mt-2">Save question</button>
                            </form>
                        </details>
                    </li>
                @endforeach
            </ol>

            <form method="POST" action="{{ route('forms.fields.store', $form) }}" class="aq-border-top pt-3 mt-2" novalidate>
                @csrf
                <h3 class="aq-label mb-2">Add a question</h3>
                @if ($errors->hasAny(['type', 'label', 'help', 'options']))
                    <div class="alert alert-danger py-2 aq-text-sm" role="alert">{{ $errors->first('label') ?: $errors->first('options') ?: $errors->first('type') }}</div>
                @endif
                @include('platform::forms._field-form', ['field' => null])
                <button type="submit" class="btn btn-sm btn-soft mt-2"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add question</button>
            </form>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="Settings" icon="gear" class="mb-3">
            <form method="POST" action="{{ route('forms.update', $form) }}" novalidate>
                @csrf
                @method('PUT')
                @include('platform::forms._settings', ['form' => $form])
                <button type="submit" class="btn btn-quiet btn-sm mt-3">Save settings</button>
            </form>
        </x-card>
        @can('delete', $form)
            <form method="POST" action="{{ route('forms.destroy', $form) }}"
                  data-confirm-title="Delete “{{ $form->title }}”?" data-confirm="The form and all {{ $form->responses_count }} {{ \Illuminate\Support\Str::plural('response', $form->responses_count) }} are permanently deleted."
                  data-confirm-button="Delete form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete form</button>
            </form>
        @endcan
    </div>
</div>
@endsection
