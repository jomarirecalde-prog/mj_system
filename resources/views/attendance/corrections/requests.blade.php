@extends('layouts.app')

@section('title', 'DTR Correction Requests')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">DTR Correction Requests</h1>
                <p class="aa-page-header__desc">Review employee requests to correct attendance records.</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.corrections.index') }}" class="btn btn--secondary">Adjustment History</a>
        </div>
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-requests" method="get" action="{{ route('attendance.correction-requests.index') }}">
                <div class="aa-filters__top">
                    <div class="aa-search">
                        <svg class="aa-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" name="search" class="aa-search__input" value="{{ request('search') }}" placeholder="Employee, employee ID, issue…" aria-label="Search correction requests">
                    </div>
                    <div class="aa-filters__actions">
                        <button type="button" class="btn btn--secondary" id="aa-filters-requests-toggle" aria-expanded="false" aria-controls="aa-filters-requests-advanced aa-filters-requests-mobile">
                            Filters
                        </button>
                        <button type="submit" class="btn btn--primary">Apply Filters</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-requests-clear">Clear Filters</button>
                    </div>
                </div>
                <div class="aa-filters__advanced aa-filters__advanced-desktop" id="aa-filters-requests-advanced">
                    <div class="aa-filters__advanced-inner">
                        <div class="aa-filters__grid">
                            <div class="form-group">
                                <label class="form-label" for="aa-req-status">Status</label>
                                <select name="status" id="aa-req-status" class="form-select">
                                    <option value="">All</option>
                                    @foreach(['pending','approved','rejected'] as $st)
                                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="aa-filters__drawer-backdrop" id="aa-filters-requests-backdrop" aria-hidden="true"></div>
    <div class="aa-filters__mobile" id="aa-filters-requests-mobile" role="dialog" aria-label="Filter correction requests" aria-modal="true">
        <h2 class="card__title" style="margin-bottom:1rem;">Filters</h2>
        <div class="form-group">
            <label class="form-label" for="aa-req-status-mobile">Status</label>
            <select id="aa-req-status-mobile" class="form-select" data-aa-sync="status">
                <option value="">All</option>
                @foreach(['pending','approved','rejected'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn btn--primary btn--block" id="aa-filters-requests-mobile-apply" style="margin-top:1rem;">Apply Filters</button>
    </div>

    <div class="card">
        <div class="card__body">
            @if($requests->isEmpty())
                <div class="aa-empty">
                    <svg class="aa-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <h2 class="aa-empty__title">No correction requests</h2>
                    <p class="aa-empty__text">
                        @if(request()->hasAny(['search', 'status']))
                            No employee correction requests match your current filters.
                        @else
                            No employee correction requests have been submitted yet.
                        @endif
                    </p>
                </div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Date</th>
                            <th scope="col">Issue</th>
                            <th scope="col">Requested Time</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($requests as $req)
                            @php
                                $record = $req->attendanceRecord;
                                $originalIn = $record?->time_in ? ph_datetime($record->time_in, 'h:i A') : '—';
                                $originalOut = $record?->time_out ? ph_datetime($record->time_out, 'h:i A') : '—';
                                $reviewData = [
                                    'employee' => $req->user?->displayName(),
                                    'employeeId' => $req->user?->employee_id,
                                    'date' => $req->attendance_date?->format('M d, Y'),
                                    'issue' => $req->issueTypeLabel(),
                                    'originalIn' => $originalIn,
                                    'originalOut' => $originalOut,
                                    'requested' => $req->requestedTimeLabel(),
                                    'reason' => $req->reason,
                                    'approveUrl' => route('attendance.correction-requests.approve', $req),
                                    'rejectUrl' => route('attendance.correction-requests.reject', $req),
                                ];
                            @endphp
                            <tr>
                                <td>
                                    <div class="aa-cell-primary">{{ $req->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $req->user?->employee_id }}</div>
                                </td>
                                <td>{{ $req->attendance_date?->format('M d, Y') }}</td>
                                <td>{{ $req->issueTypeLabel() }}</td>
                                <td><strong>{{ $req->requestedTimeLabel() }}</strong></td>
                                <td>
                                    <div id="reason-{{ $req->id }}" class="aa-cell-clamp">{{ $req->reason }}</div>
                                    @if(strlen($req->reason ?? '') > 80)
                                        <button type="button" class="aa-link-btn" data-aa-reason-toggle aria-expanded="false" aria-controls="reason-{{ $req->id }}">View reason</button>
                                    @endif
                                </td>
                                <td>
                                    @include('partials.attendance-status-badge', ['status' => $req->status])
                                    @if($req->admin_remarks)
                                        <div class="aa-cell-secondary" style="margin-top:.35rem;max-width:180px;">{{ $req->admin_remarks }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($req->isPending())
                                        <button type="button" class="btn btn--primary btn--sm" data-aa-review='@json($reviewData)'>Review Request</button>
                                    @else
                                        <span class="aa-cell-secondary">Reviewed by {{ $req->reviewer?->displayName() ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($requests as $req)
                        @php
                            $record = $req->attendanceRecord;
                            $reviewData = [
                                'employee' => $req->user?->displayName(),
                                'employeeId' => $req->user?->employee_id,
                                'date' => $req->attendance_date?->format('M d, Y'),
                                'issue' => $req->issueTypeLabel(),
                                'originalIn' => $record?->time_in ? ph_datetime($record->time_in, 'h:i A') : '—',
                                'originalOut' => $record?->time_out ? ph_datetime($record->time_out, 'h:i A') : '—',
                                'requested' => $req->requestedTimeLabel(),
                                'reason' => $req->reason,
                                'approveUrl' => route('attendance.correction-requests.approve', $req),
                                'rejectUrl' => route('attendance.correction-requests.reject', $req),
                            ];
                        @endphp
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $req->user?->displayName() }}</div>
                                    <div class="aa-cell-secondary">{{ $req->user?->employee_id }}</div>
                                </div>
                                @include('partials.attendance-status-badge', ['status' => $req->status])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Date</span> {{ $req->attendance_date?->format('M d, Y') }}</div>
                                <div><span class="aa-card-row__label">Issue</span> {{ $req->issueTypeLabel() }}</div>
                                <div><span class="aa-card-row__label">Requested</span> <strong>{{ $req->requestedTimeLabel() }}</strong></div>
                                <div><span class="aa-card-row__label">Reason</span> {{ $req->reason }}</div>
                                @if(!$req->isPending())
                                    <div><span class="aa-card-row__label">Reviewed by</span> {{ $req->reviewer?->displayName() ?? '—' }}</div>
                                @endif
                            </div>
                            @if($req->isPending())
                                <div class="aa-card-row__actions">
                                    <button type="button" class="btn btn--primary btn--block" data-aa-review='@json($reviewData)'>Review Request</button>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                @include('partials.pagination', ['paginator' => $requests->withQueryString()])
            @endif
        </div>
    </div>
</div>

<div id="aa-review-modal" class="aa-modal-backdrop" aria-hidden="true" hidden>
    <div class="aa-modal" role="dialog" aria-modal="true" aria-labelledby="aa-review-modal-title" tabindex="-1">
        <h2 class="aa-modal__title" id="aa-review-modal-title">Review Correction Request</h2>
        <div data-aa-review-body></div>

        <form method="post" data-aa-approve-form data-aa-submit>
            @csrf
            <div class="form-group">
                <label class="form-label" for="aa-approve-remarks">Admin Remarks</label>
                <input type="text" name="admin_remarks" id="aa-approve-remarks" class="form-control" data-aa-approve-remarks placeholder="Optional remarks for approval">
            </div>
            <button type="submit" class="btn btn--primary">
                <span class="aa-btn-text">Approve Request</span>
                <span class="aa-btn-spinner" aria-hidden="true"></span>
            </button>
        </form>

        <hr class="aa-modal__divider">

        <form method="post" data-aa-reject-form data-aa-submit>
            @csrf
            <div class="form-group aa-field-required">
                <label class="form-label" for="aa-reject-remarks">Rejection Reason</label>
                <textarea name="admin_remarks" id="aa-reject-remarks" class="form-control" rows="2" data-aa-reject-remarks required placeholder="Required — explain why this request is rejected"></textarea>
            </div>
            <div class="aa-modal__actions">
                <button type="button" class="btn btn--ghost" data-aa-modal-close>Cancel</button>
                <button type="submit" class="btn btn--danger">
                    <span class="aa-btn-text">Reject Request</span>
                    <span class="aa-btn-spinner" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="aa-confirm-modal" class="aa-modal-backdrop" aria-hidden="true" hidden>
    <div class="aa-modal" role="dialog" aria-modal="true" aria-labelledby="aa-confirm-title" tabindex="-1">
        <h2 class="aa-modal__title" id="aa-confirm-title" data-aa-confirm-title>Confirm</h2>
        <p data-aa-confirm-message style="margin:0 0 1.25rem;color:var(--muted);"></p>
        <div class="aa-modal__actions">
            <button type="button" class="btn btn--ghost" data-aa-confirm-close>Cancel</button>
            <button type="button" class="btn btn--primary" data-aa-confirm-yes>Confirm</button>
        </div>
    </div>
</div>
@endsection
