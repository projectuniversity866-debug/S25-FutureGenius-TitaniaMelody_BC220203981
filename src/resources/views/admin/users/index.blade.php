@extends('layouts.app')
@section('title', 'Manage users')

@section('content')
<h1 class="h4 fw-bold mb-1">Manage users</h1>
<p class="text-muted small mb-4">
    Assign roles and control account access. Administrator-only area.
</p>

<form method="GET" action="{{ route('admin.users.index') }}" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="q" class="form-label small">Search</label>
                <input type="search" id="q" name="q" class="form-control form-control-sm"
                       value="{{ request('q') }}" placeholder="Name or email…">
            </div>
            <div class="col-md-4">
                <label for="role" class="form-label small">Role</label>
                <select id="role" name="role" class="form-select form-select-sm">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark btn-sm">Filter</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Registered users</caption>
            <thead class="table-light">
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
                                @include('partials.avatar', ['user' => $user, 'size' => 36])
                                <div class="min-w-0">
                                    <a href="{{ route('profile.show', $user) }}"
                                       class="fw-semibold text-decoration-none d-block">{{ $user->name }}</a>
                                    <span class="small text-muted">{{ $user->email }}</span>
                                </div>
                            </div>
                        </th>
                        <td class="small text-muted">
                            {{ $user->questions_count }} question(s)<br>
                            {{ $user->answers_count }} answer(s)
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.role', $user) }}"
                                  class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <label for="role_{{ $user->id }}" class="visually-hidden">Role for {{ $user->name }}</label>
                                <select id="role_{{ $user->id }}" name="role_id" class="form-select form-select-sm"
                                        @disabled($user->id === auth()->id())>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-dark"
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
