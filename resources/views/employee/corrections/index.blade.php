@extends('layouts.employee')

@section('title', 'DTR Correction Requests')

@section('content')
<div class="page-header">
    <div>
        <h1>DTR Correction Requests</h1>
        <p class="page-header__meta">Submit and track requests. You cannot modify approved attendance directly.</p>
    </div>
    <a href="{{ route('employee.corrections.create') }}" class="btn btn--primary">New request</a>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Request</th>
                <th>Requested Time</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
            </thead>
            <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>{{ $req->attendance_date?->format('M d, Y') }}</td>
                    <td>{{ $req->issueTypeLabel() }}</td>
                    <td>{{ $req->requestedTimeLabel() }}</td>
                    <td>
                        <span class="badge {{ $req->status === 'approved' ? 'badge--available' : ($req->status === 'rejected' ? 'badge--out' : 'badge--warn') }}">
                            {{ $req->statusLabel() }}
                        </span>
                    </td>
                    <td>
                        @if($req->status === 'rejected' && $req->admin_remarks)
                            {{ $req->admin_remarks }}
                        @elseif($req->admin_remarks)
                            {{ $req->admin_remarks }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No correction requests yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $requests])
    </div>
</div>
@endsection
