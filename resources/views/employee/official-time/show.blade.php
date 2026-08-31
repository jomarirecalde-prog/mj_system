@extends('layouts.employee')

@section('title', 'Official Time Request Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/official-time.css') }}">
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1>Official Time Request</h1>
        <p class="page-header__meta">Request #{{ $request->id }}</p>
    </div>
    <a href="{{ route('employee.official-time.index') }}" class="btn btn--ghost">Back</a>
</div>

<div class="card mb-2">
    <div class="card__header">
        <h2 class="card__title">Request Details</h2>
        @include('partials.official-time-status-badge', ['status' => $request->status])
    </div>
    <div class="card__body">
        <dl class="dl-grid">
            <div class="dl-item"><dt>Employee</dt><dd>{{ $request->user?->displayName() }}</dd></div>
            <div class="dl-item"><dt>Employee ID</dt><dd>{{ $request->user?->employee_id }}</dd></div>
            <div class="dl-item"><dt>Request Type</dt><dd>{{ $request->requestTypeLabel() }}</dd></div>
            <div class="dl-item"><dt>Effective From</dt><dd>{{ $request->effective_from?->format('F j, Y') }}</dd></div>
            <div class="dl-item"><dt>Effective To</dt><dd>{{ $request->effective_to?->format('F j, Y') ?? 'No end date' }}</dd></div>
            <div class="dl-item"><dt>Submitted</dt><dd>{{ $request->created_at?->timezone('Asia/Manila')->format('F j, Y h:i A') }}</dd></div>
        </dl>
    </div>
</div>

<div class="grid-2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Current Official Time</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Time In</dt><dd>{{ $request::formatTimeField($request->current_time_in) }}</dd></div>
                <div class="dl-item"><dt>Time Out</dt><dd>{{ $request::formatTimeField($request->current_time_out) }}</dd></div>
                <div class="dl-item"><dt>Break</dt><dd>{{ $request->breakRangeLabel('current') }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Requested Official Time</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Time In</dt><dd>{{ $request::formatTimeField($request->requested_time_in) }}</dd></div>
                <div class="dl-item"><dt>Time Out</dt><dd>{{ $request::formatTimeField($request->requested_time_out) }}</dd></div>
                <div class="dl-item"><dt>Break</dt><dd>{{ $request->breakRangeLabel('requested') }}</dd></div>
            </dl>
        </div>
    </div>
</div>

<div class="card mb-2">
    <div class="card__header"><h2 class="card__title">Reason</h2></div>
    <div class="card__body">
        <p>{{ $request->reason }}</p>
        @if($request->notes)
            <h3 class="ot-subheading">Additional Notes</h3>
            <p class="text-muted">{{ $request->notes }}</p>
        @endif
    </div>
</div>

@if($request->status === 'approved')
    <div class="card mb-2 ot-review-card ot-review-card--approved">
        <div class="card__header"><h2 class="card__title">Approval</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Approved By</dt><dd>{{ $request->reviewer?->displayName() ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Approved Date</dt><dd>{{ $request->reviewed_at?->timezone('Asia/Manila')->format('F j, Y h:i A') }}</dd></div>
                @if($request->admin_remarks)
                    <div class="dl-item"><dt>Admin Remarks</dt><dd>{{ $request->admin_remarks }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
@elseif($request->status === 'rejected')
    <div class="card mb-2 ot-review-card ot-review-card--rejected">
        <div class="card__header"><h2 class="card__title">Rejection</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Rejected By</dt><dd>{{ $request->reviewer?->displayName() ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Rejected Date</dt><dd>{{ $request->reviewed_at?->timezone('Asia/Manila')->format('F j, Y h:i A') }}</dd></div>
                <div class="dl-item"><dt>Rejection Reason</dt><dd>{{ $request->admin_remarks ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>
@elseif($request->isPending())
    <div class="card mb-2">
        <div class="card__body">
            <form method="post" action="{{ route('employee.official-time.cancel', $request) }}" onsubmit="return confirm('Cancel this official time request?');">
                @csrf
                <button type="submit" class="btn btn--danger">Cancel Request</button>
            </form>
        </div>
    </div>
@endif
@endsection
