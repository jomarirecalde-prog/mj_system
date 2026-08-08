@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="page-header">
    <div><h1>Users</h1><p class="page-header__meta">Manage system access and roles</p></div>
    <a href="{{ route('users.create') }}" class="btn btn--primary">Add user</a>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, email, employee ID"></div>
            <div class="form-group"><label class="form-label">Role</label>
                <select name="role" class="form-select"><option value="">All</option>
                    @foreach(['admin','staff','viewer'] as $r)<option value="{{ $r }}" @selected(request('role')===$r)>{{ ucfirst($r) }}</option>@endforeach
                </select></div>
            <button type="submit" class="btn btn--secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        @if($users->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No users found</p></div>
        @else
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
                <tbody>
                @foreach($users as $u)
                    <tr>
                        <td>{{ $u->displayName() }}<br><span class="text-muted" style="font-size:0.8rem;">{{ $u->employee_id }}</span></td>
                        <td>{{ $u->email }}</td>
                        <td>{{ ucfirst($u->role) }}</td>
                        <td><span class="badge {{ $u->status === 'active' ? 'badge--available' : 'badge--archived' }}">{{ ucfirst($u->status) }}</span></td>
                        <td>{{ ph_datetime($u->last_login_at) ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('users.edit', $u) }}" class="btn btn--ghost btn--sm">Edit</a>
                                <a href="{{ route('users.reset-password.form', $u) }}" class="btn btn--secondary btn--sm">Reset pwd</a>
                                @if($u->status === 'active' && $u->id !== auth()->id())
                                    <form action="{{ route('users.deactivate', $u) }}" method="post" style="display:inline;">@csrf<button type="submit" class="btn btn--ghost btn--sm">Deactivate</button></form>
                                @elseif($u->status !== 'active')
                                    <form action="{{ route('users.activate', $u) }}" method="post" style="display:inline;">@csrf<button type="submit" class="btn btn--ghost btn--sm">Activate</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @include('partials.pagination', ['paginator' => $users->withQueryString()])
        @endif
    </div>
</div>
@endsection
