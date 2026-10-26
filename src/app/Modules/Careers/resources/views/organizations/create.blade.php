@extends('layouts.app')
@section('title', 'Add an organization')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Add an organization"
               subtitle="A public profile for the employer, university or programme behind your opportunities."
               :breadcrumbs="['Organizations' => route('organizations.index'), 'Add' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('organizations.store') }}" novalidate>
            @include('careers::organizations._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">Create profile</button>
                <a href="{{ route('organizations.index') }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
