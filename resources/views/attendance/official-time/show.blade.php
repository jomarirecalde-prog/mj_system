@extends('layouts.app')

@section('title', 'Review Official Time Request')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/official-time.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/official-time-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $req = $officialTimeRequest;
@endphp

<div class="aa-module ot-admin-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">🕐</span>
            <div>
                <h1 class="aa-page-header__title">Official Time Request</h1>
                <p class="aa-page-header__desc">Request #{{ $req->id }}</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.official-time.index') }}" class="btn btn--secondary">Back to List</a>
        </div>
    </header>

    @if($req->isPending() && !empty($conflicts))
        <div class="ot-conflict-alert" role="alert">
            <strong>⚠ Schedule Conflict</strong>
            <p>This employee already has an approved schedule/request covering this period:</p>
            <ul>
                @foreach($conflicts as $conflict)
                    <li>{{ $conflict }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-2">
        <div class="card__header">
            <h2 class="card__title">Employee</h2>
            @include('partials.official-time-status-badge', ['status' => $req->status])
        </div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Employee</dt><dd>{{ $req->user?->displayName() }}</dd></div>
                <div class="dl-item"><dt>Employee ID</dt><dd>{{ $req->user?->employee_id }}</dd></div>
                <div class="dl-item"><dt>Department</dt><dd>{{ $req->user?->department ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Request Type</dt><dd>{{ $req->requestTypeLabel() }}</dd></div>
                <div class="dl-item"><dt>Effective Period</dt><dd>{{ $req->effectivePeriodLabel() }}</dd></div>
                <div class="dl-item"><dt>Submitted</dt><dd>{{ $req->created_at?->timezone('Asia/Manila')->format('F j, Y h:i A') }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="ot-comparison ot-comparison--admin mb-2">
        <div class="ot-comparison__grid">
            <div class="ot-comparison__col">
                <h3>Current Official Time</h3>
                <p class="ot-comparison__range">{{ $req->timeRangeLabel('current') }}</p>
                <p class="text-muted">Break: {{ $req->breakRangeLabel('current') }}</p>
            </div>
            <div class="ot-comparison__col ot-comparison__col--requested">
                <h3>Requested Official Time</h3>
                <p class="ot-comparison__range">{{ $req->timeRangeLabel('requested') }}</p>
                <p class="text-muted">Break: {{ $req->breakRangeLabel('requested') }}</p>
            </div>
        </div>

        <div class="ot-comparison__highlights">
            <h4>Change Summary</h4>
            <ul class="ot-comparison__changes">
                <li><span>Time In</span> <strong>{{ $req::formatTimeField($req->current_time_in) }} → {{ $req::formatTimeField($req->requested_time_in) }}</strong></li>
                <li><span>Time Out</span> <strong>{{ $req::formatTimeField($req->current_time_out) }} → {{ $req::formatTimeField($req->requested_time_out) }}</strong></li>
                <li><span>Break</span> <strong>{{ $req->breakRangeLabel('current') }} → {{ $req->breakRangeLabel('requested') }}</strong></li>
            </ul>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card__header"><h2 class="card__title">Employee Reason</h2></div>
        <div class="card__body">
            <p>{{ $req->reason }}</p>
            @if($req->notes)
                <h3 class="ot-subheading">Additional Notes</h3>
                <p class="text-muted">{{ $req->notes }}</p>
            @endif
        </div>
    </div>

    @if($req->isPending())
        <div class="card mb-2">
            <div class="card__header"><h2 class="card__title">Admin Decision</h2></div>
            <div class="card__body">
                <form method="post" action="{{ route('attendance.official-time.approve', $req) }}" id="ot-approve-form" class="mb-2" data-ot-confirm-approve>
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="approve_remarks">Admin Remarks</label>
                        <textarea name="admin_remarks" id="approve_remarks" class="form-control" rows="2" maxlength="1000" placeholder="Optional remarks for approval"></textarea>
                    </div>
                    <button type="submit" class="btn btn--primary" data-ot-submit>
                        <span class="ot-btn-text">Approve Request</span>
                        <span class="ot-btn-loading" hidden>Processing…</span>
                    </button>
                </form>

                <hr>

                <form method="post" action="{{ route('attendance.official-time.reject', $req) }}" id="ot-reject-form" data-ot-confirm-reject>
                    @csrf
                    <div class="form-group aa-field-required">
                        <label class="form-label" for="reject_remarks">Rejection Reason</label>
                        <textarea name="admin_remarks" id="reject_remarks" class="form-control" rows="2" required maxlength="1000" placeholder="Required — explain why this request is rejected"></textarea>
                        @error('admin_remarks')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn btn--danger" data-ot-submit>
                        <span class="ot-btn-text">Reject Request</span>
                        <span class="ot-btn-loading" hidden>Processing…</span>
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="card mb-2">
            <div class="card__header"><h2 class="card__title">Review Outcome</h2></div>
            <div class="card__body">
                <dl class="dl-grid">
                    <div class="dl-item"><dt>Reviewed By</dt><dd>{{ $req->reviewer?->displayName() ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Reviewed Date</dt><dd>{{ $req->reviewed_at?->timezone('Asia/Manila')->format('F j, Y h:i A') ?? '—' }}</dd></div>
                    @if($req->admin_remarks)
                        <div class="dl-item"><dt>{{ $req->status === 'rejected' ? 'Rejection Reason' : 'Admin Remarks' }}</dt><dd>{{ $req->admin_remarks }}</dd></div>
                    @endif
                    @if($req->employee_schedule_id)
                        <div class="dl-item"><dt>Applied Schedule</dt><dd>#{{ $req->employee_schedule_id }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    @endif

    @if($auditLogs->isNotEmpty())
        <div class="card">
            <div class="card__header"><h2 class="card__title">Audit Trail</h2></div>
            <div class="card__body table-wrap">
                <table class="data-table">
                    <thead><tr><th>Action</th><th>By</th><th>Date</th></tr></thead>
                    <tbody>
                    @foreach($auditLogs as $log)
                        <tr>
                            <td>{{ str_replace('_', ' ', ucfirst($log->action)) }}</td>
                            <td>{{ $log->user?->displayName() ?? 'System' }}</td>
                            <td>{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if($req->isPending())
<div id="ot-confirm-modal" class="aa-modal-backdrop" aria-hidden="true" hidden>
    <div class="aa-modal" role="dialog" aria-modal="true" aria-labelledby="ot-confirm-title" tabindex="-1">
        <h2 class="aa-modal__title" id="ot-confirm-title">Confirm</h2>
        <p data-ot-confirm-message style="margin:0 0 1.25rem;color:var(--muted);"></p>
        <div class="aa-modal__actions">
            <button type="button" class="btn btn--ghost" data-ot-confirm-close>Cancel</button>
            <button type="button" class="btn btn--primary" data-ot-confirm-yes>Confirm</button>
        </div>
    </div>
</div>
@endif
@endsection
