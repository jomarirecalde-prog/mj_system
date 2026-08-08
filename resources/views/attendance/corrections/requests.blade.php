@extends('layouts.app')

@section('title', 'Employee Correction Requests')

@section('content')
<div class="page-header">
    <div>
        <h1>Employee DTR Correction Requests</h1>
        <p class="page-header__meta">Approve or reject requests submitted from the Employee Portal</p>
    </div>
    <a href="{{ route('attendance.corrections.index') }}" class="btn btn--ghost">Adjustment history</a>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['pending','approved','rejected'] as $st)
                        <option value="{{ $st }}" @selected(request('status')===$st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn--secondary" type="submit">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Employee</th>
                <th>Date</th>
                <th>Issue</th>
                <th>Requested</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>{{ $req->user?->displayName() }}<br><span class="text-muted">{{ $req->user?->employee_id }}</span></td>
                    <td>{{ $req->attendance_date?->format('M d, Y') }}</td>
                    <td>{{ $req->issueTypeLabel() }}</td>
                    <td>{{ $req->requestedTimeLabel() }}</td>
                    <td>{{ $req->reason }}</td>
                    <td>
                        <span class="badge {{ $req->status === 'approved' ? 'badge--available' : ($req->status === 'rejected' ? 'badge--out' : 'badge--warn') }}">
                            {{ $req->statusLabel() }}
                        </span>
                        @if($req->admin_remarks)
                            <div class="text-muted" style="font-size:.8rem;margin-top:.25rem;">{{ $req->admin_remarks }}</div>
                        @endif
                    </td>
                    <td>
                        @if($req->isPending())
                            <form method="post" action="{{ route('attendance.correction-requests.approve', $req) }}" style="margin-bottom:.5rem;">
                                @csrf
                                <input type="text" name="admin_remarks" class="form-control" placeholder="Optional remarks" style="margin-bottom:.35rem;">
                                <button class="btn btn--primary btn--sm" type="submit">Approve</button>
                            </form>
                            <form method="post" action="{{ route('attendance.correction-requests.reject', $req) }}">
                                @csrf
                                <input type="text" name="admin_remarks" class="form-control" placeholder="Rejection reason (required)" required style="margin-bottom:.35rem;">
                                <button class="btn btn--ghost btn--sm" type="submit">Reject</button>
                            </form>
                        @else
                            <span class="text-muted">Reviewed by {{ $req->reviewer?->displayName() ?? '—' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No employee correction requests.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $requests->withQueryString()])
    </div>
</div>
@endsection
