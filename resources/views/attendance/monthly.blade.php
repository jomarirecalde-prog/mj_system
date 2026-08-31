@extends('layouts.app')

@section('title', 'Monthly DTR')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $dayIcon = function ($status) {
        return match ($status) {
            'present' => ['✓', 'Present', 'present'],
            'late' => ['!', 'Late', 'late'],
            'absent' => ['×', 'Absent', 'absent'],
            'on_leave', 'official_business' => ['▣', 'Leave', 'leave'],
            'rest_day' => ['○', 'Rest', 'rest'],
            'incomplete' => ['⚠', 'Incomplete', 'incomplete'],
            'undertime' => ['⚠', 'Undertime', 'incomplete'],
            default => ['·', ucfirst(str_replace('_', ' ', $status)), 'default'],
        };
    };
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Monthly DTR</h1>
                <p class="aa-page-header__desc">Per-employee monthly attendance summary.</p>
            </div>
        </div>
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form method="get" action="{{ route('attendance.monthly') }}" class="aa-filters__top" style="align-items:flex-end;">
                <div class="aa-filters__grid" style="flex:1;">
                    <div class="form-group">
                        <label class="form-label" for="monthly-employee">Employee</label>
                        <select name="employee_id" id="monthly-employee" class="form-select">
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" @selected(optional($employee)->id === $e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="monthly-month">Month</label>
                        <input type="month" name="month" id="monthly-month" class="form-control" value="{{ $month }}">
                    </div>
                </div>
                <div class="aa-filters__actions">
                    <button type="submit" class="btn btn--primary">Load Report</button>
                    @if($employee)
                        <a class="btn btn--secondary" href="{{ route('attendance.reports.export', ['type' => 'monthly', 'format' => 'pdf', 'employee_id' => $employee->id, 'month' => $month]) }}">Export PDF</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if($employee)
        <div class="aa-identity-card" style="margin-bottom:var(--aa-space-xl);">
            <div>
                <div class="aa-cell-primary" style="font-size:1.1rem;">{{ $employee->displayName() }}</div>
                <div class="aa-cell-secondary">{{ $employee->employee_id }} · {{ $employee->department ?? '—' }}</div>
                <div class="aa-cell-secondary">{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</div>
            </div>
        </div>

        <div class="aa-summary aa-summary--primary mb-2">
            <div class="aa-summary__card aa-summary__card--approved">
                <div class="aa-summary__label">Present</div>
                <div class="aa-summary__value">{{ $totals['present'] }}</div>
            </div>
            <div class="aa-summary__card aa-summary__card--absent">
                <div class="aa-summary__label">Absent</div>
                <div class="aa-summary__value">✕ {{ $totals['absent'] }}</div>
            </div>
            <div class="aa-summary__card aa-summary__card--late">
                <div class="aa-summary__label">Total Late</div>
                <div class="aa-summary__value">{{ intdiv($totals['late'], 60) }}h {{ $totals['late'] % 60 }}m</div>
            </div>
            <div class="aa-summary__card">
                <div class="aa-summary__label">Total Undertime</div>
                <div class="aa-summary__value">{{ intdiv($totals['undertime'], 60) }}h {{ $totals['undertime'] % 60 }}m</div>
            </div>
            <div class="aa-summary__card">
                <div class="aa-summary__label">Total Overtime</div>
                <div class="aa-summary__value">{{ intdiv($totals['overtime'], 60) }}h {{ $totals['overtime'] % 60 }}m</div>
            </div>
            <div class="aa-summary__card">
                <div class="aa-summary__label">Hours Worked</div>
                <div class="aa-summary__value">{{ intdiv($totals['hours'], 60) }}h {{ $totals['hours'] % 60 }}m</div>
            </div>
        </div>

        <div class="card mb-2">
            <div class="card__header"><h2 class="card__title">Month Overview</h2></div>
            <div class="card__body">
                <div class="aa-month-grid" aria-label="Monthly attendance overview">
                    @foreach($records as $row)
                        @php
                            $status = $row->record ? $row->record->status : $row->status;
                            [$icon, $label, $class] = $dayIcon($status);
                            $isWeekend = $row->date->isWeekend();
                        @endphp
                        <div class="aa-month-day aa-month-day--{{ $class }} {{ $isWeekend ? 'aa-month-day--weekend' : '' }}" title="{{ $row->date->format('M j') }} — {{ $label }}">
                            <div class="aa-month-day__num">{{ $row->date->format('j') }}</div>
                            <div class="aa-month-day__icon" aria-hidden="true">{{ $icon }}</div>
                            <span class="sr-only">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="aa-form-hint" style="margin:0;">✓ Present · ! Late · × Absent · ▣ Leave · ○ Rest Day · ⚠ Incomplete</p>
            </div>
        </div>

        <div class="card">
            <div class="card__header"><h2 class="card__title">Daily Records</h2></div>
            <div class="card__body">
                <div class="aa-table-wrap aa-table-desktop">
                    <table class="aa-table">
                        <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Day</th>
                            <th scope="col">Time In</th>
                            <th scope="col">Time Out</th>
                            <th scope="col">Hours</th>
                            <th scope="col">Late</th>
                            <th scope="col">Undertime</th>
                            <th scope="col">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($records as $row)
                            @php $r = $row->record; $status = $r ? $r->status : $row->status; @endphp
                            <tr class="{{ $row->date->isWeekend() ? 'aa-month-day--weekend' : '' }}">
                                <td>{{ $row->date->format('M j') }}</td>
                                <td>{{ $row->date->format('D') }}</td>
                                <td>{{ $r && $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—' }}</td>
                                <td>{{ $r && $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—' }}</td>
                                <td>{{ $r ? $r->totalHoursLabel() : '—' }}</td>
                                <td>{{ $r ? $r->minutesLabel($r->late_minutes) : '—' }}</td>
                                <td>{{ $r ? $r->minutesLabel($r->undertime_minutes) : '—' }}</td>
                                <td>@include('partials.attendance-record-status-badge', ['status' => $status])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="aa-mobile-cards">
                    @foreach($records as $row)
                        @php $r = $row->record; $status = $r ? $r->status : $row->status; @endphp
                        <article class="aa-card-row">
                            <div class="aa-card-row__head">
                                <div>
                                    <div class="aa-cell-primary">{{ $row->date->format('M j, Y') }} ({{ $row->date->format('D') }})</div>
                                </div>
                                @include('partials.attendance-record-status-badge', ['status' => $status])
                            </div>
                            <div class="aa-card-row__grid">
                                <div><span class="aa-card-row__label">Time In / Out</span> {{ $r && $r->time_in ? ph_datetime($r->time_in, 'h:i A') : '—' }} → {{ $r && $r->time_out ? ph_datetime($r->time_out, 'h:i A') : '—' }}</div>
                                <div><span class="aa-card-row__label">Hours</span> {{ $r ? $r->totalHoursLabel() : '—' }}</div>
                                <div><span class="aa-card-row__label">Late / Undertime</span> {{ $r ? $r->minutesLabel($r->late_minutes) : '—' }} / {{ $r ? $r->minutesLabel($r->undertime_minutes) : '—' }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
