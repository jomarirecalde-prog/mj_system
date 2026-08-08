@extends('layouts.app')

@section('title', 'Attendance Audit Logs')

@section('content')
<div class="page-header"><div><h1>Attendance Audit Logs</h1><p class="page-header__meta">Read-only trail of attendance actions</p></div></div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div>
            <div class="form-group"><label class="form-label">Action</label><input type="text" name="action" class="form-control" value="{{ request('action') }}" placeholder="QR Time In"></div>
            <button class="btn btn--secondary" type="submit">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Employee</th><th>Action</th><th>Original</th><th>New</th><th>User</th><th>Date/Time</th><th>IP</th><th>Device</th><th>Reason</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->employee?->displayName() ?? '—' }}</td>
                    <td>{{ $log->action }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;">{{ $log->original_value ?? '—' }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;">{{ $log->new_value ?? '—' }}</td>
                    <td>{{ $log->performer?->displayName() ?? '—' }}</td>
                    <td>{{ ph_datetime($log->logged_at) }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                    <td>{{ $log->device ?? '—' }}</td>
                    <td>{{ $log->reason ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No attendance audit logs.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $logs->withQueryString()])
    </div>
</div>
@endsection
