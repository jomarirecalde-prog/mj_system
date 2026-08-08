@extends('layouts.app')

@section('title', 'Add Schedule')

@section('content')
<div class="page-header"><div><h1>Add employee schedule</h1></div></div>
<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('attendance.schedules.store') }}" class="form-grid">
            @csrf
            <div class="form-group"><label class="form-label">Employee</label>
                <select name="user_id" class="form-select" required>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Shift template (optional)</label>
                <select name="shift_id" class="form-select">
                    <option value="">Custom / Regular</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }} ({{ substr($shift->time_in,0,5) }}–{{ substr($shift->time_out,0,5) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Time In</label><input type="time" name="time_in" class="form-control" value="08:00"></div>
            <div class="form-group"><label class="form-label">Time Out</label><input type="time" name="time_out" class="form-control" value="17:00"></div>
            <div class="form-group"><label class="form-label">Break Start</label><input type="time" name="break_start" class="form-control" value="12:00"></div>
            <div class="form-group"><label class="form-label">Break End</label><input type="time" name="break_end" class="form-control" value="13:00"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Work days</label>
                <div class="btn-group" style="flex-wrap:wrap;">
                    @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',0=>'Sun'] as $num=>$label)
                        <label class="form-hint" style="margin-right:1rem;"><input type="checkbox" name="work_days[]" value="{{ $num }}" @checked(in_array($num,[1,2,3,4,5],true))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Rest days</label>
                <div class="btn-group" style="flex-wrap:wrap;">
                    @foreach([0=>'Sun',6=>'Sat',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri'] as $num=>$label)
                        <label class="form-hint" style="margin-right:1rem;"><input type="checkbox" name="rest_days[]" value="{{ $num }}" @checked(in_array($num,[0,6],true))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>
            <div class="form-group"><label class="form-label">Effective from</label><input type="date" name="effective_from" class="form-control"></div>
            <div class="form-group"><label class="form-label">Effective to</label><input type="date" name="effective_to" class="form-control"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <div><button class="btn btn--primary" type="submit">Save schedule</button>
                <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
