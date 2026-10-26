@extends('layouts.app')
@section('title', $form->title)
@section('subtitle', 'Form')
@section('container', 'aq-container-narrow')

@section('content')
@if ($form->status === 'draft')
    <div class="alert alert-warning py-2 aq-text-sm" role="status">
        <i class="bi bi-eye me-1" aria-hidden="true"></i> Preview — this form is a draft. Only its editors can see it.
    </div>
@endif

<x-page-header :title="$form->title" :subtitle="$form->description" />

<div class="card">
    <div class="card-body p-4">
        @if ($refusal && $form->status !== 'draft')
            <x-empty icon="lock" :title="$refusal"
                     :action="! auth()->check() && str_starts_with($refusal, 'Sign in') ? ['url' => route('login'), 'label' => 'Sign in'] : null" />
        @else
            @if ($errors->has('form'))
                <div class="alert alert-danger" role="alert">{{ $errors->first('form') }}</div>
            @elseif ($errors->any())
                <div class="alert alert-danger" role="alert">Please check the {{ $errors->count() === 1 ? 'answer' : $errors->count().' answers' }} marked below.</div>
            @endif

            <form method="POST" action="{{ route('forms.submit', $form) }}" novalidate>
                @csrf
                <p class="aq-text-xs aq-muted"><span class="aq-danger" aria-hidden="true">*</span> marks a required question.</p>
                @include('platform::forms._questions', ['fields' => $form->fields])

                @if ($form->status === 'draft')
                    <p class="aq-text-sm aq-muted mb-0">Submitting is disabled in preview. Open the form to collect responses.</p>
                @else
                    <button type="submit" class="btn btn-accent">Submit</button>
                @endif
            </form>
        @endif
    </div>
</div>
@endsection
