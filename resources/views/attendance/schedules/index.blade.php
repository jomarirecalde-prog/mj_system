@extends('layouts.app')

@section('title', 'Employee Schedules')

@section('content')
<div class="page-header">
    <div>
        <h1>Employee Schedules</h1>
        <p class="page-header__meta">Regular schedules and shift assignments</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('attendance.schedules.create') }}" class="btn btn--primary">Add schedule</a>
            <a href="{{ route('attendance.shifts.index') }}" class="btn btn--secondary">Manage shifts</a>
        @endif
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Employee"></div>
            <button class="btn btn--secondary" type="submit">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Employee</th><th>Type</th><th>Shift</th><th>Time In</th><th>Time Out</th><th>Break</th><th>Rest Days</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @forelse($schedules as $s)
                <tr>
                    <td>{{ $s->user?->displayName() }}<br><span class="text-muted" style="font-size:.8rem;">{{ $s->user?->employee_id }}</span></td>
                    <td>{{ ucfirst($s->schedule_type) }}</td>
                    <td>{{ $s->shift?->name ?? '—' }}</td>
                    <td>{{ substr((string)$s->time_in,0,5) }}</td>
                    <td>{{ substr((string)$s->time_out,0,5) }}</td>
                    <td>{{ $s->break_start ? substr((string)$s->break_start,0,5).'–'.substr((string)$s->break_end,0,5) : '—' }}</td>
                    <td>
                        @php
                            $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                            $rest = collect($s->rest_days ?? [])->map(fn($d) => $days[$d] ?? $d)->implode(', ');
                        @endphp
                        {{ $rest ?: '—' }}
                    </td>
                    <td>{{ $s->is_active ? 'Yes' : 'No' }}</td>
                    <td>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('attendance.schedules.edit', $s) }}" class="btn btn--ghost btn--sm">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No schedules yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $schedules->withQueryString()])
    </div>
</div>
@endsection
