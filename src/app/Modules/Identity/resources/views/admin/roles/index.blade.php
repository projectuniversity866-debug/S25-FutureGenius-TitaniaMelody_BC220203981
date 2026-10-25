@extends('layouts.app')
@section('title', 'Roles & permissions')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Roles & permissions"
               subtitle="Choose what each role may do. Changes apply immediately to everyone with that role and are recorded in the activity log." />

<div class="row g-4">
    @foreach ($roles as $role)
        @php $granted = $role->permissions->pluck('slug')->all(); @endphp
        <div class="col-lg-4">
            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="card h-100">
                @csrf
                @method('PUT')
                <div class="card-header aq-card-head">
                    <h2 class="aq-h3">{{ $role->name }}</h2>
                    <span class="aq-text-xs aq-muted">{{ $role->users_count }} {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}</span>
                </div>
                <div class="card-body">
                    <p class="aq-text-sm aq-muted">{{ $role->description }}</p>

                    @foreach ($permissions as $group => $items)
                        <fieldset class="mb-3">
                            <legend class="aq-label mb-2">{{ $group }}</legend>
                            @foreach ($items as $permission)
                                @php
                                    $id = 'perm-'.$role->id.'-'.\Illuminate\Support\Str::slug($permission->slug);
                                    $isLocked = $role->slug === \App\Modules\Identity\Models\Role::ADMIN && in_array($permission->slug, $locked, true);
                                @endphp
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" id="{{ $id }}"
                                           value="{{ $permission->slug }}" @checked(in_array($permission->slug, $granted, true))
                                           @if ($isLocked) disabled aria-describedby="{{ $id }}-why" @endif>
                                    @if ($isLocked)
                                        {{-- Disabled inputs are not submitted; keep the locked grant. --}}
                                        <input type="hidden" name="permissions[]" value="{{ $permission->slug }}">
                                    @endif
                                    <label class="form-check-label" for="{{ $id }}">{{ $permission->name }}</label>
                                    @if ($isLocked)
                                        <span id="{{ $id }}-why" class="d-block aq-text-xs aq-muted">Always on, so administrators cannot lock themselves out.</span>
                                    @endif
                                </div>
                            @endforeach
                        </fieldset>
                    @endforeach
                </div>
                <div class="card-body pt-0">
                    <button type="submit" class="btn btn-accent btn-sm w-100">
                        Save<span class="visually-hidden"> permissions for {{ $role->name }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
