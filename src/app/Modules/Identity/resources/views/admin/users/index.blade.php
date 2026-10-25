@extends('layouts.app')
@section('title', 'Manage users')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Manage users" subtitle="Assign roles and control account access. Administrator-only area." />

<x-filter-panel :action="route('admin.users.index')" label="Filter users"
                :filters="['q' => 'Search', 'role' => ['Role', $roles->pluck('name', 'slug')->all()]]"
                :total="$users->total()" noun="user">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm"
               value="{{ request('q') }}" placeholder="Name or email…">
    </div>
    <div>
        <label for="role" class="form-label">Role</label>
        <select id="role" name="role" class="form-select form-select-sm">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>
</x-filter-panel>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
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
                @forelse ($users as $user)
                    @php $isMe = $user->is(auth()->user()); @endphp
                    <tr>
                        <th scope="row" class="fw-normal">
                            <div class="d-flex align-items-center gap-2">
                                <x-avatar :user="$user" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('profile.show', $user) }}"
                                       class="fw-semibold text-decoration-none d-block">{{ $user->name }}</a>
                                    <span class="aq-text-xs aq-muted">{{ $user->email }}</span>
                                </div>
                            </div>
                        </th>
                        <td data-label="Activity" class="aq-text-xs aq-muted">
                            <span>
                                <span class="aq-num">{{ $user->questions_count }}</span> {{ \Illuminate\Support\Str::plural('question', $user->questions_count) }}
                                · <span class="aq-num">{{ $user->answers_count }}</span> {{ \Illuminate\Support\Str::plural('answer', $user->answers_count) }}
                            </span>
                        </td>
                        <td data-label="Status">
                            <span>
                                <x-status :value="$user->is_active ? 'active' : 'inactive'" />
                            </span>
                        </td>
                        <td data-label="Role">
                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <label for="role_{{ $user->id }}" class="visually-hidden">Role for {{ $user->name }}</label>
                                <select id="role_{{ $user->id }}" name="role_id" class="form-select form-select-sm"
                                        @disabled($isMe)>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-quiet" @disabled($isMe)>
                                    Set<span class="visually-hidden"> role for {{ $user->name }}</span>
                                </button>
                            </form>
                        </td>
                        <td data-label="Access" class="aq-cell-actions">
                            <form method="POST" action="{{ route('admin.users.active', $user) }}" class="aq-actions"
                                  @if ($user->is_active)
                                      data-confirm-title="Deactivate {{ $user->name }}?"
                                      data-confirm="They are signed out immediately and cannot sign in until an administrator reactivates the account. Their content stays visible."
                                      data-confirm-button="Deactivate account"
                                  @endif>
                                @csrf
                                @method('PUT')
                                <button type="submit"
                                        class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        @disabled($isMe)>
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}<span class="visually-hidden"> {{ $user->name }}</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="people" title="No users match these filters" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="User list pages" class="mt-4">
    {{ $users->links() }}
</nav>
@endsection
