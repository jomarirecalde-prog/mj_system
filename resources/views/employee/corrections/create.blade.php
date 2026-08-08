@extends('layouts.employee')

@section('title', 'New Correction Request')

@section('content')
<div class="page-header">
    <div>
        <h1>Submit DTR Correction Request</h1>
        <p class="page-header__meta">Requests go to Staff / Super Admin for review</p>
    </div>
    <a href="{{ route('employee.corrections.index') }}" class="btn btn--ghost">Back</a>
</div>

<div class="card" style="max-width:640px;">
    <div class="card__body">
        <form method="post" action="{{ route('employee.corrections.store') }}">
            @csrf
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="attendance_date">Date</label>
                <input type="date" name="attendance_date" id="attendance_date" class="form-control" value="{{ old('attendance_date', $date) }}" required max="{{ now('Asia/Manila')->toDateString() }}">
                @error('attendance_date')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="issue_type">Issue</label>
                <select name="issue_type" id="issue_type" class="form-select" required>
                    @foreach([
                        'missing_time_in' => 'Forgot to Time In / Missing Time In',
                        'missing_time_out' => 'Forgot to Time Out / Missing Time Out',
                        'incorrect_time_in' => 'Incorrect Time In',
                        'incorrect_time_out' => 'Incorrect Time Out',
                        'other' => 'Other',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('issue_type')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="requested_time_in">Requested Time In</label>
                <input type="time" name="requested_time_in" id="requested_time_in" class="form-control" value="{{ old('requested_time_in') }}">
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="requested_time_out">Requested Time Out</label>
                <input type="time" name="requested_time_out" id="requested_time_out" class="form-control" value="{{ old('requested_time_out') }}">
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label" for="reason">Reason</label>
                <textarea name="reason" id="reason" class="form-control" rows="4" required maxlength="1000">{{ old('reason') }}</textarea>
                @error('reason')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            @if($record)
                <p class="text-muted" style="margin-bottom:1rem;">Current record: In {{ $record->time_in ? ph_datetime($record->time_in, 'h:i A') : '—' }} · Out {{ $record->time_out ? ph_datetime($record->time_out, 'h:i A') : '—' }} · {{ $record->statusLabel() }}</p>
            @endif
            <button type="submit" class="btn btn--primary">Submit request</button>
        </form>
    </div>
</div>
@endsection
