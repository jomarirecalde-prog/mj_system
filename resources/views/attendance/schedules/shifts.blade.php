@extends('layouts.app')

@section('title', 'Shifts')

@section('content')
<div class="page-header">
    <div><h1>Attendance Shifts</h1><p class="page-header__meta">Shift A / B / C templates</p></div>
    <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Back to schedules</a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Existing shifts</h2></div>
        <div class="card__body table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Code</th><th>In</th><th>Out</th><th>Break</th></tr></thead>
                <tbody>
                @foreach($shifts as $s)
                    <tr>
                        <td>{{ $s->name }}</td>
                        <td>{{ $s->code }}</td>
                        <td>{{ substr($s->time_in,0,5) }}</td>
                        <td>{{ substr($s->time_out,0,5) }}</td>
                        <td>{{ $s->break_start ? substr($s->break_start,0,5).'–'.substr($s->break_end,0,5) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Add shift</h2></div>
        <div class="card__body">
            <form method="post" action="{{ route('attendance.shifts.store') }}" class="form-grid">
                @csrf
                <div class="form-group"><label class="form-label">Name</label><input name="name" class="form-control" required placeholder="Shift D"></div>
                <div class="form-group"><label class="form-label">Code</label><input name="code" class="form-control" placeholder="SHIFT-D"></div>
                <div class="form-group"><label class="form-label">Time In</label><input type="time" name="time_in" class="form-control" required value="08:00"></div>
                <div class="form-group"><label class="form-label">Time Out</label><input type="time" name="time_out" class="form-control" required value="17:00"></div>
                <div class="form-group"><label class="form-label">Break Start</label><input type="time" name="break_start" class="form-control" value="12:00"></div>
                <div class="form-group"><label class="form-label">Break End</label><input type="time" name="break_end" class="form-control" value="13:00"></div>
                <div class="form-group"><label class="form-label">Grace (min)</label><input type="number" name="grace_period_minutes" class="form-control" min="0" max="120" placeholder="Use global"></div>
                <div class="form-group" style="grid-column:1/-1"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div><button class="btn btn--primary" type="submit">Create shift</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
