@extends('layouts.app')

@section('title', 'Correct DTR')

@section('content')
<div class="page-header"><div><h1>DTR Correction / Adjustment</h1><p class="page-header__meta">Original values are kept for auditing</p></div></div>

<div class="card">
    <div class="card__body">
        <form method="post" action="{{ route('attendance.corrections.store') }}" class="form-grid">
            @csrf
            @if($record)
                <input type="hidden" name="attendance_record_id" value="{{ $record->id }}">
                <div class="form-group" style="grid-column:1/-1">
                    <p><strong>{{ $record->user?->displayName() }}</strong> · {{ $record->attendance_date?->format('M d, Y') }}</p>
                    <p class="text-muted">Current Time In: {{ $record->time_in ? ph_datetime($record->time_in, 'Y-m-d H:i') : '—' }} · Time Out: {{ $record->time_out ? ph_datetime($record->time_out, 'Y-m-d H:i') : '—' }} · Status: {{ $record->statusLabel() }}</p>
                </div>
            @else
                <div class="form-group"><label class="form-label">Employee</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Date</label><input type="date" name="attendance_date" class="form-control" value="{{ now('Asia/Manila')->toDateString() }}" required></div>
            @endif
            <div class="form-group"><label class="form-label">Corrected Time In</label><input type="datetime-local" name="time_in" class="form-control" value="{{ $record && $record->time_in ? $record->time_in->timezone('Asia/Manila')->format('Y-m-d\TH:i') : '' }}"></div>
            <div class="form-group"><label class="form-label">Corrected Time Out</label><input type="datetime-local" name="time_out" class="form-control" value="{{ $record && $record->time_out ? $record->time_out->timezone('Asia/Manila')->format('Y-m-d\TH:i') : '' }}"></div>
            <div class="form-group"><label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Keep current</option>
                    @foreach(['present','late','absent','on_leave','official_business','half_day','undertime','incomplete','rest_day'] as $s)
                        <option value="{{ $s }}" @selected(optional($record)->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">Remarks</label><input type="text" name="remarks" class="form-control" value="{{ optional($record)->remarks }}"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Reason (required)</label><textarea name="reason" class="form-control" rows="3" required placeholder="Why is this correction needed?"></textarea></div>
            <div><button class="btn btn--primary" type="submit">Save correction</button>
                <a href="{{ route('attendance.corrections.index') }}" class="btn btn--ghost">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
