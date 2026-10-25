@extends('layouts.app')
@section('title', 'Manage users')
@section('subtitle', 'Administration')

@section('content')
<div class="aq-page-head">
    <div>
        <h1>Manage users</h1>
        <p>Assign roles and control account access. Administrator-only area.</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="aq-toolbar">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label for="q" class="form-label">Search</label>
            <input type="search" id="q" name="q" class="form-control form-control-sm"
                   value="{{ request('q') }}" placeholder="Name or email…">
        </div>
        <div class="col-md-4">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-select form-select-sm">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-accent btn-sm">Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <caption class="visually-hidden">Registered users</caption>
            <thead>
                <tr>
                    <th scope="col">User</th>
                    <th scope="col">Activity</th>
                    <th scope="col">Status</th>
                    <th scope="col">Role</th>
                    <th scope="col" class="text-end">Access</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <div class="d-flex align-items-center gap-2">
                                @include('partials.avatar', ['user' => $user, 'size' => 34])
                                <div class="min-w-0">
                                    <a href="{{ route('profile.show', $user) }}"
                                       class="fw-semibold text-decoration-none d-block">{{ $user->name }}</a>
                                    <span style="font-size: .75rem; color: var(--aq-muted);">{{ $user->email }}</span>
                                </div>
                            </div>
                        </th>
                        <td style="font-size: .75rem; color: var(--aq-muted);">
                            <span class="aq-num">{{ $user->questions_count }}</span> questions<br>
                            <span class="aq-num">{{ $user->answers_count }}</span> answers
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <label for="role_{{ $user->id }}" class="visually-hidden">Role for {{ $user->name }}</label>
                                <select id="role_{{ $user->id }}" name="role_id" class="form-select form-select-sm"
                                        style="width: auto;" @disabled($user->id === auth()->id())>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-quiet"
                                        @disabled($user->id === auth()->id())>Set</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.users.active', $user) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit"
                                        class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        @disabled($user->id === auth()->id())>
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="User list pages" class="mt-4">
    {{ $users->links() }}
</nav>
@endsection
