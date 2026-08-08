@extends('layouts.app')

@section('title', 'Monthly DTR')

@section('content')
<div class="page-header">
    <div>
        <h1>Monthly DTR</h1>
        <p class="page-header__meta">Per-employee monthly time record</p>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Employee</label>
                <select name="employee_id" class="form-select">
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(optional($employee)->id===$e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}">
            </div>
            <button type="submit" class="btn btn--secondary">Load</button>
            @if($employee)
                <a class="btn btn--ghost" href="{{ route('attendance.reports.export', ['type' => 'monthly', 'format' => 'pdf', 'employee_id' => $employee->id, 'month' => $month]) }}">Export PDF</a>
            @endif
        </form>
    </div>
</div>

@if($employee)
<div class="card mb-2">
    <div class="card__body">
        <strong>Employee:</strong> {{ $employee->displayName() }}
        &nbsp;·&nbsp; <strong>Month:</strong> {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}
        &nbsp;·&nbsp; <strong>Department:</strong> {{ $employee->department ?? '—' }}
    </div>
</div>

<div class="stat-grid mb-2">
    <div class="stat-card"><div class="stat-card__label">Days Present</div><div class="stat-card__value">{{ $totals['present'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Days Absent</div><div class="stat-card__value">{{ $totals['absent'] }}</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Late</div><div class="stat-card__value">{{ intdiv($totals['late'],60) }}h {{ $totals['late']%60 }}m</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Undertime</div><div class="stat-card__value">{{ intdiv($totals['undertime'],60) }}h {{ $totals['undertime']%60 }}m</div></div>
    <div class="stat-card"><div class="stat-card__label">Total Overtime</div><div class="stat-card__value">{{ intdiv($totals['overtime'],60) }}h {{ $totals['overtime']%60 }}m</div></div>
    <div class="stat-card"><div class="stat-card__label">Hours Worked</div><div class="stat-card__value">{{ intdiv($totals['hours'],60) }}h {{ $totals['hours']%60 }}m</div></div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Date</th><th>Day</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Late</th><th>Undertime</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($records as $row)
                @php $r = $row->record; @endphp
                <tr>
                    <td>{{ $row->date->format('M j') }}</td>
                    <td>{{ $row->date->format('D') }}</td>
                    <td>{{ $r && $r->time_in ? ph_datetime($r->time_in, 'h:i') : '—' }}</td>
                    <td>{{ $r && $r->time_out ? ph_datetime($r->time_out, 'h:i') : '—' }}</td>
                    <td>{{ $r ? $r->totalHoursLabel() : '—' }}</td>
                    <td>{{ $r ? $r->minutesLabel($r->late_minutes) : '—' }}</td>
                    <td>{{ $r ? $r->minutesLabel($r->undertime_minutes) : '—' }}</td>
                    <td>{{ $r ? $r->statusLabel() : ucfirst(str_replace('_',' ', $row->status)) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
