@extends('layouts.app')

@section('title', 'Add Employee Schedule')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $shiftJson = $shifts->mapWithKeys(fn ($s) => [
        $s->id => [
            'name' => $s->name,
            'time_in' => substr((string) $s->time_in, 0, 5),
            'time_out' => substr((string) $s->time_out, 0, 5),
            'break_start' => $s->break_start ? substr((string) $s->break_start, 0, 5) : '',
            'break_end' => $s->break_end ? substr((string) $s->break_end, 0, 5) : '',
        ],
    ]);
    $workDays = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
    $restDayOrder = [0 => 'Sun', 6 => 'Sat', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri'];
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Add Employee Schedule</h1>
                <p class="aa-page-header__desc">Assign working hours, days, and effective period for an employee.</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Cancel</a>
        </div>
    </header>

    <div class="card">
        <div class="card__body">
            <form method="post" action="{{ route('attendance.schedules.store') }}" data-aa-schedule-form data-aa-submit>
                @csrf

                <section class="aa-form-section" aria-labelledby="aa-emp-section">
                    <h2 class="aa-form-section__title" id="aa-emp-section">Employee</h2>
                    <div class="form-group">
                        <label class="form-label" for="user_id">Employee</label>
                        <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-shift-section">
                    <h2 class="aa-form-section__title" id="aa-shift-section">Shift Template</h2>
                    <div class="form-group">
                        <label class="form-label" for="shift_id">Shift template</label>
                        <select name="shift_id" id="shift_id" class="form-select" data-aa-shift-select data-shifts='@json($shiftJson)'>
                            <option value="">Custom / Regular</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->name }} ({{ substr($shift->time_in, 0, 5) }}–{{ substr($shift->time_out, 0, 5) }})</option>
                            @endforeach
                        </select>
                        <p class="aa-form-hint">Select a shift template or choose Custom / Regular.</p>
                        <p class="aa-form-hint" data-aa-shift-preview hidden></p>
                    </div>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-hours-section">
                    <h2 class="aa-form-section__title" id="aa-hours-section">Working Hours</h2>
                    <div class="aa-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="time_in">Time In</label>
                            <input type="time" name="time_in" id="time_in" class="form-control @error('time_in') is-invalid @enderror" value="{{ old('time_in', '08:00') }}">
                            @error('time_in')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_out">Time Out</label>
                            <input type="time" name="time_out" id="time_out" class="form-control @error('time_out') is-invalid @enderror" value="{{ old('time_out', '17:00') }}">
                            @error('time_out')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="break_start">Break Start</label>
                            <input type="time" name="break_start" id="break_start" class="form-control" value="{{ old('break_start', '12:00') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="break_end">Break End</label>
                            <input type="time" name="break_end" id="break_end" class="form-control" value="{{ old('break_end', '13:00') }}">
                        </div>
                    </div>
                    <p class="aa-form-warning" data-aa-warn-time hidden></p>
                    <p class="aa-form-warning" data-aa-warn-break hidden></p>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-workdays-section">
                    <h2 class="aa-form-section__title" id="aa-workdays-section">Work Days</h2>
                    @include('partials.attendance-day-chips', [
                        'name' => 'work_days',
                        'days' => $workDays,
                        'selected' => old('work_days', [1, 2, 3, 4, 5]),
                        'label' => 'Work days',
                    ])
                </section>

                <section class="aa-form-section" aria-labelledby="aa-restdays-section">
                    <h2 class="aa-form-section__title" id="aa-restdays-section">Rest Days</h2>
                    @include('partials.attendance-day-chips', [
                        'name' => 'rest_days',
                        'days' => $restDayOrder,
                        'selected' => old('rest_days', [0, 6]),
                        'rest' => true,
                        'label' => 'Rest days',
                    ])
                    <p class="aa-form-warning" data-aa-warn-days hidden></p>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-effective-section">
                    <h2 class="aa-form-section__title" id="aa-effective-section">Effective Period</h2>
                    <div class="aa-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="effective_from">Effective From</label>
                            <input type="date" name="effective_from" id="effective_from" class="form-control" value="{{ old('effective_from') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="effective_to">Effective To</label>
                            <input type="date" name="effective_to" id="effective_to" class="form-control" value="{{ old('effective_to') }}">
                            <p class="aa-form-hint">Leave Effective To blank if the schedule has no planned end date.</p>
                        </div>
                    </div>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-notes-section">
                    <h2 class="aa-form-section__title" id="aa-notes-section">Notes</h2>
                    <div class="form-group">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </section>

                <div class="aa-form-actions">
                    <button class="btn btn--primary" type="submit">
                        <span class="aa-btn-text">Save Schedule</span>
                        <span class="aa-btn-spinner" aria-hidden="true"></span>
                    </button>
                    <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
