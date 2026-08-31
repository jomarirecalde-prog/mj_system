@extends('layouts.app')

@section('title', 'Attendance Record')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@section('content')
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">{{ $record->user?->displayName() }}</h1>
                <div class="aa-page-header__meta">
                    <span>{{ $record->user?->employee_id }}</span>
                    <span>{{ $record->attendance_date?->format('l, F j, Y') }}</span>
                    @include('partials.attendance-record-status-badge', ['status' => $record->status])
                </div>
            </div>
        </div>
        <div class="aa-page-header__actions">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('attendance.corrections.create', ['record_id' => $record->id]) }}" class="btn btn--secondary">Correct DTR</a>
            @endif
            <a href="{{ route('attendance.records') }}" class="btn btn--ghost">Back</a>
        </div>
    </header>

    <div class="aa-record-hero">
        <div class="aa-record-hero__item">
            <div class="aa-record-hero__label">Time In</div>
            <div class="aa-record-hero__value">{{ $record->time_in ? ph_datetime($record->time_in, 'h:i A') : '—' }}</div>
        </div>
        <div class="aa-record-hero__item">
            <div class="aa-record-hero__label">Time Out</div>
            <div class="aa-record-hero__value">{{ $record->time_out ? ph_datetime($record->time_out, 'h:i A') : '—' }}</div>
        </div>
        <div class="aa-record-hero__item">
            <div class="aa-record-hero__label">Total</div>
            <div class="aa-record-hero__value aa-record-hero__value--total">{{ $record->totalHoursLabel() }}</div>
        </div>
        <div class="aa-record-hero__item">
            <div class="aa-record-hero__label">Late</div>
            <div class="aa-record-hero__value">{{ $record->minutesLabel($record->late_minutes) }}</div>
        </div>
        <div class="aa-record-hero__item">
            <div class="aa-record-hero__label">Undertime</div>
            <div class="aa-record-hero__value">{{ $record->minutesLabel($record->undertime_minutes) }}</div>
        </div>
        <div class="aa-record-hero__item">
            <div class="aa-record-hero__label">Overtime</div>
            <div class="aa-record-hero__value">{{ $record->minutesLabel($record->overtime_minutes) }}</div>
        </div>
    </div>

    <div class="grid-2 mb-2">
        <div class="card">
            <div class="card__header"><h2 class="card__title">Attendance Summary</h2></div>
            <div class="card__body">
                <dl class="aa-meta-grid">
                    <div class="aa-meta-item"><dt>Department</dt><dd>{{ $record->user?->department ?? '—' }}</dd></div>
                    <div class="aa-meta-item"><dt>Schedule</dt><dd>{{ $record->scheduleLabel() }}</dd></div>
                    <div class="aa-meta-item"><dt>Status</dt><dd>@include('partials.attendance-record-status-badge', ['status' => $record->status])</dd></div>
                    <div class="aa-meta-item"><dt>Source</dt><dd>{{ ucfirst($record->source) }}</dd></div>
                    <div class="aa-meta-item" style="grid-column:1/-1;"><dt>Remarks</dt><dd>{{ $record->remarks ?: '—' }}</dd></div>
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card__header"><h2 class="card__title">Scan / Device Info</h2></div>
            <div class="card__body">
                <dl class="aa-meta-grid">
                    <div class="aa-meta-item"><dt>Time In By</dt><dd>{{ $record->timeInBy?->displayName() ?? '—' }}</dd></div>
                    <div class="aa-meta-item"><dt>Time In Device</dt><dd class="mono">{{ $record->time_in_device ?? '—' }}</dd></div>
                    <div class="aa-meta-item"><dt>Time Out By</dt><dd>{{ $record->timeOutBy?->displayName() ?? '—' }}</dd></div>
                    <div class="aa-meta-item"><dt>Time Out Device</dt><dd class="mono">{{ $record->time_out_device ?? '—' }}</dd></div>
                    <div class="aa-meta-item"><dt>Corrected</dt><dd>{{ $record->is_corrected ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card__header"><h2 class="card__title">Correction History</h2></div>
        <div class="card__body">
            @if($record->adjustments->isEmpty())
                <div class="aa-empty" style="padding:1.5rem;"><p class="aa-empty__text">No corrections recorded for this attendance.</p></div>
            @else
                <div class="aa-timeline">
                    @foreach($record->adjustments as $adj)
                        <article class="aa-timeline__item">
                            <div class="aa-cell-primary">{{ $adj->field_name }}</div>
                            <div class="aa-timeline__change">
                                {{ $adj->original_value ?? '—' }}
                                <span aria-hidden="true"> → </span>
                                <strong>{{ $adj->corrected_value ?? '—' }}</strong>
                            </div>
                            <div class="aa-cell-secondary" style="margin-top:.35rem;"><strong>Reason:</strong> {{ $adj->reason }}</div>
                            <div class="aa-cell-secondary">{{ $adj->corrector?->displayName() }} · {{ ph_datetime($adj->corrected_at) }}</div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Attendance Logs</h2></div>
        <div class="card__body">
            @if($record->logs->isEmpty())
                <div class="aa-empty" style="padding:1.5rem;"><p class="aa-empty__text">No attendance logs for this record.</p></div>
            @else
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Action</th>
                            <th scope="col">Performed By</th>
                            <th scope="col">When</th>
                            <th scope="col">Device</th>
                            <th scope="col">Reason</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($record->logs as $log)
                            <tr>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->performer?->displayName() ?? '—' }}</td>
                                <td>{{ ph_datetime($log->logged_at) }}</td>
                                <td class="mono" style="font-family:ui-monospace,monospace;font-size:.82rem;">{{ $log->device ?? '—' }}</td>
                                <td>{{ $log->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="aa-mobile-cards">
                    @foreach($record->logs as $log)
                        <article class="aa-card-row">
                            <div class="aa-cell-primary">{{ $log->action }}</div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">By</span> {{ $log->performer?->displayName() ?? '—' }}</div>
                                <div><span class="aa-card-row__label">When</span> {{ ph_datetime($log->logged_at) }}</div>
                                <div><span class="aa-card-row__label">Device</span> {{ $log->device ?? '—' }}</div>
                                @if($log->reason)
                                    <div><span class="aa-card-row__label">Reason</span> {{ $log->reason }}</div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
