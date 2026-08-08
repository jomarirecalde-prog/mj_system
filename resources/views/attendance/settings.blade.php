@extends('layouts.app')

@section('title', 'Attendance Settings')

@section('content')
<div class="page-header"><div><h1>Attendance Settings</h1><p class="page-header__meta">Grace period, schedules, scanner cooldown · Asia/Manila</p></div></div>

<div class="grid-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Rules &amp; defaults</h2></div>
        <div class="card__body">
            <form method="post" action="{{ route('attendance.settings.update') }}" class="form-grid">
                @csrf @method('PUT')
                <div class="form-group"><label class="form-label">Grace period (minutes)</label>
                    <input type="number" name="grace_period_minutes" class="form-control" min="0" max="120" value="{{ $settings['grace_period_minutes'] }}" required>
                    <p class="form-hint">8:00 + 15 min grace → Present until 8:15; Late from 8:16</p>
                </div>
                <div class="form-group"><label class="form-label">Scan cooldown (seconds)</label>
                    <input type="number" name="scan_cooldown_seconds" class="form-control" min="0" max="600" value="{{ $settings['scan_cooldown_seconds'] }}" required>
                </div>
                <div class="form-group"><label class="form-label">Default Time In</label><input type="time" name="default_time_in" class="form-control" value="{{ $settings['default_time_in'] }}" required></div>
                <div class="form-group"><label class="form-label">Default Time Out</label><input type="time" name="default_time_out" class="form-control" value="{{ $settings['default_time_out'] }}" required></div>
                <div class="form-group"><label class="form-label">Break Start</label><input type="time" name="default_break_start" class="form-control" value="{{ $settings['default_break_start'] }}"></div>
                <div class="form-group"><label class="form-label">Break End</label><input type="time" name="default_break_end" class="form-control" value="{{ $settings['default_break_end'] }}"></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-hint"><input type="checkbox" name="treat_holiday_as_rest" value="1" @checked($settings['treat_holiday_as_rest']==='1')> Treat holidays as rest days</label><br>
                    <label class="form-hint"><input type="checkbox" name="location_capture" value="1" @checked($settings['location_capture']==='1')> Capture location when available</label><br>
                    <label class="form-hint"><input type="checkbox" name="require_reason_on_correction" value="1" @checked($settings['require_reason_on_correction']==='1')> Require reason on DTR correction</label>
                </div>
                <div><button class="btn btn--primary" type="submit">Save settings</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Holidays</h2></div>
        <div class="card__body">
            <form method="post" action="{{ route('attendance.settings.holidays.store') }}" class="form-grid mb-2">
                @csrf
                <div class="form-group"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Date</label><input type="date" name="holiday_date" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Type</label>
                    <select name="type" class="form-select"><option value="regular">Regular</option><option value="special">Special</option></select>
                </div>
                <div class="form-group"><label class="form-label">Description</label><input name="description" class="form-control"></div>
                <div><button class="btn btn--secondary" type="submit">Add holiday</button></div>
            </form>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Name</th><th>Type</th></tr></thead>
                    <tbody>
                    @forelse($holidays as $h)
                        <tr><td>{{ $h->holiday_date->format('M d, Y') }}</td><td>{{ $h->name }}</td><td>{{ ucfirst($h->type) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No holidays configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
