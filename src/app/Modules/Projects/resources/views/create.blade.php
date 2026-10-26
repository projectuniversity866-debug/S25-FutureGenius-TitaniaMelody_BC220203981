@extends('layouts.app')
@section('title', 'New project')
@section('subtitle', 'Projects')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="New project" subtitle="A shared space for your team. You become its owner and can add members next."
               :breadcrumbs="['Projects' => route('projects.index'), 'New project' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('projects.store') }}" novalidate>
            @csrf
            @include('projects::_form', ['project' => null])
            <button type="submit" class="btn btn-accent mt-3">Create project</button>
        </form>
    </div>
</div>
@endsection
