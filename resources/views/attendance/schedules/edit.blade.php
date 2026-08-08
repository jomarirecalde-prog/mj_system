@extends('layouts.app')

@section('title', 'Edit Schedule')

@section('content')
<div class="page-header"><div><h1>Edit schedule</h1><p class="page-header__meta">{{ $schedule->user?->displayName() }}</p></div></div>
<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('attendance.schedules.update', $schedule) }}" class="form-grid">
            @csrf @method('PUT')
            <div class="form-group"><label class="form-label">Employee</label>
                <select name="user_id" class="form-select" required>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected($schedule->user_id===$e->id)>{{ $e->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Shift</label>
                <select name="shift_id" class="form-select">
                    <option value="">Custom / Regular</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected($schedule->shift_id===$shift->id)>{{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Time In</label><input type="time" name="time_in" class="form-control" value="{{ substr((string)$schedule->time_in,0,5) }}"></div>
            <div class="form-group"><label class="form-label">Time Out</label><input type="time" name="time_out" class="form-control" value="{{ substr((string)$schedule->time_out,0,5) }}"></div>
            <div class="form-group"><label class="form-label">Break Start</label><input type="time" name="break_start" class="form-control" value="{{ $schedule->break_start ? substr((string)$schedule->break_start,0,5) : '' }}"></div>
            <div class="form-group"><label class="form-label">Break End</label><input type="time" name="break_end" class="form-control" value="{{ $schedule->break_end ? substr((string)$schedule->break_end,0,5) : '' }}"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Work days</label>
                @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',0=>'Sun'] as $num=>$label)
                    <label class="form-hint" style="margin-right:1rem;"><input type="checkbox" name="work_days[]" value="{{ $num }}" @checked(in_array($num, $schedule->work_days ?? [], true))> {{ $label }}</label>
                @endforeach
            </div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Rest days</label>
                @foreach([0=>'Sun',6=>'Sat',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri'] as $num=>$label)
                    <label class="form-hint" style="margin-right:1rem;"><input type="checkbox" name="rest_days[]" value="{{ $num }}" @checked(in_array($num, $schedule->rest_days ?? [], true))> {{ $label }}</label>
                @endforeach
            </div>
            <div class="form-group"><label class="form-label">Effective from</label><input type="date" name="effective_from" class="form-control" value="{{ optional($schedule->effective_from)->format('Y-m-d') }}"></div>
            <div class="form-group"><label class="form-label">Effective to</label><input type="date" name="effective_to" class="form-control" value="{{ optional($schedule->effective_to)->format('Y-m-d') }}"></div>
            <div class="form-group"><label class="form-label"><input type="checkbox" name="is_active" value="1" @checked($schedule->is_active)> Active</label></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ $schedule->notes }}</textarea></div>
            <div><button class="btn btn--primary" type="submit">Update</button>
                <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
