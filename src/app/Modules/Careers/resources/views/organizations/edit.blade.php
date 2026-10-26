@extends('layouts.app')
@section('title', 'Edit organization')
@section('subtitle', 'Jobs & opportunities')

@section('content')
<x-page-header title="Edit organization"
               :breadcrumbs="['Organizations' => route('organizations.index'), $organization->name => route('organizations.show', $organization), 'Edit' => null]" />

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('organizations.update', $organization) }}" novalidate>
            @include('careers::organizations._form')

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-accent">Save changes</button>
                <a href="{{ route('organizations.show', $organization) }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
