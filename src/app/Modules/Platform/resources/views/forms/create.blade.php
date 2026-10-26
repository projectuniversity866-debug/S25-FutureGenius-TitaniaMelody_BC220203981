@extends('layouts.app')
@section('title', 'New form')
@section('subtitle', 'Form builder')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="New form" subtitle="Start with the basics; you add the questions on the next screen."
               :breadcrumbs="['My forms' => route('forms.index'), 'New form' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('forms.store') }}" novalidate>
            @csrf
            @include('platform::forms._settings', ['form' => null])
            <button type="submit" class="btn btn-accent mt-4">Create and add questions</button>
        </form>
    </div>
</div>
@endsection
