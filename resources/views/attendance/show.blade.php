@extends('layouts.app')

@section('title', 'Attendance Record')

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $record->user?->displayName() }}</h1>
        <p class="page-header__meta">{{ $record->attendance_date?->format('l, F j, Y') }} · {{ $record->user?->employee_id }}</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('attendance.corrections.create', ['record_id' => $record->id]) }}" class="btn btn--secondary">Correct DTR</a>
        @endif
        <a href="{{ route('attendance.records') }}" class="btn btn--ghost">Back</a>
    </div>
</div>

<div class="grid-2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Attendance details</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Department</dt><dd>{{ $record->user?->department ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Schedule</dt><dd>{{ $record->scheduleLabel() }}</dd></div>
                <div class="dl-item"><dt>Time In</dt><dd>{{ $record->time_in ? ph_datetime($record->time_in, 'h:i:s A') : '—' }}</dd></div>
                <div class="dl-item"><dt>Time Out</dt><dd>{{ $record->time_out ? ph_datetime($record->time_out, 'h:i:s A') : '—' }}</dd></div>
                <div class="dl-item"><dt>Total Hours</dt><dd>{{ $record->totalHoursLabel() }}</dd></div>
                <div class="dl-item"><dt>Late</dt><dd>{{ $record->minutesLabel($record->late_minutes) }}</dd></div>
                <div class="dl-item"><dt>Undertime</dt><dd>{{ $record->minutesLabel($record->undertime_minutes) }}</dd></div>
                <div class="dl-item"><dt>Overtime</dt><dd>{{ $record->minutesLabel($record->overtime_minutes) }}</dd></div>
                <div class="dl-item"><dt>Status</dt><dd>{{ $record->statusLabel() }}</dd></div>
                <div class="dl-item"><dt>Source</dt><dd>{{ ucfirst($record->source) }}</dd></div>
                <div class="dl-item"><dt>Remarks</dt><dd>{{ $record->remarks ?: '—' }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Scan / device info</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Time In By</dt><dd>{{ $record->timeInBy?->displayName() ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Time In Device</dt><dd>{{ $record->time_in_device ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Time Out By</dt><dd>{{ $record->timeOutBy?->displayName() ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Time Out Device</dt><dd>{{ $record->time_out_device ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Corrected</dt><dd>{{ $record->is_corrected ? 'Yes' : 'No' }}</dd></div>
            </dl>
        </div>
    </div>
</div>

<div class="card mb-2">
    <div class="card__header"><h2 class="card__title">Correction history</h2></div>
    <div class="card__body table-wrap">
        @if($record->adjustments->isEmpty())
            <p class="text-muted">No corrections.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Field</th><th>Original</th><th>Corrected</th><th>Reason</th><th>By</th><th>When</th></tr></thead>
                <tbody>
                @foreach($record->adjustments as $adj)
                    <tr>
                        <td>{{ $adj->field_name }}</td>
                        <td>{{ $adj->original_value ?? '—' }}</td>
                        <td>{{ $adj->corrected_value ?? '—' }}</td>
                        <td>{{ $adj->reason }}</td>
                        <td>{{ $adj->corrector?->displayName() }}</td>
                        <td>{{ ph_datetime($adj->corrected_at) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="card">
    <div class="card__header"><h2 class="card__title">Attendance logs</h2></div>
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Action</th><th>By</th><th>When</th><th>Device</th><th>Reason</th></tr></thead>
            <tbody>
            @forelse($record->logs as $log)
                <tr>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->performer?->displayName() ?? '—' }}</td>
                    <td>{{ ph_datetime($log->logged_at) }}</td>
                    <td>{{ $log->device ?? '—' }}</td>
                    <td>{{ $log->reason ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No logs.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
