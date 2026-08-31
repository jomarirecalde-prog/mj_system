@extends('layouts.employee')

@section('title', 'My Schedule')

@section('content')
@php
    use App\Support\EmployeeAttendancePresenter;
    use Carbon\Carbon;
    $fmt = function (?string $t) {
        if (!$t) return '—';
        $t = substr($t, 0, 5);
        return Carbon::createFromFormat('H:i', $t, 'Asia/Manila')->format('g:i A');
    };
@endphp
<div class="page-header">
    <div>
        <h1>My Schedule</h1>
        <p class="page-header__meta">Read-only view of your assigned work schedule</p>
    </div>
</div>

<div class="card mb-2">
    <div class="card__header"><h2 class="card__title">Current Schedule</h2></div>
    <div class="card__body">
        <p class="emp-schedule-days">{{ $workDays ?: '—' }}</p>
        <dl class="dl-grid">
            <div class="dl-item"><dt>Time In</dt><dd>{{ $fmt($resolved['time_in'] ?? null) }}</dd></div>
            <div class="dl-item"><dt>Time Out</dt><dd>{{ $fmt($resolved['time_out'] ?? null) }}</dd></div>
            <div class="dl-item"><dt>Break</dt><dd>
                @if(!empty($resolved['break_start']) && !empty($resolved['break_end']))
                    {{ $fmt($resolved['break_start']) }} – {{ $fmt($resolved['break_end']) }}
                @else
                    —
                @endif
            </dd></div>
            <div class="dl-item"><dt>Rest Days</dt><dd>{{ $restDays ?: '—' }}</dd></div>
            <div class="dl-item"><dt>Shift</dt><dd>{{ $schedule?->shift?->name ?? ($resolved['shift_name'] ?? 'Default') }}</dd></div>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card__header"><h2 class="card__title">Upcoming schedule changes</h2></div>
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Effective From</th><th>Effective To</th><th>Schedule</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse($upcoming as $row)
                <tr>
                    <td>{{ $row->effective_from?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $row->effective_to?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $row->scheduleLabel() }}</td>
                    <td>{{ $row->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No upcoming schedule changes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
