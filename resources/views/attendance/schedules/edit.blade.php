@extends('layouts.app')

@section('title', 'Edit Employee Schedule')

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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Edit Employee Schedule</h1>
                <div class="aa-page-header__meta">
                    <span>{{ $schedule->user?->displayName() }}</span>
                    <span>{{ $schedule->user?->employee_id }}</span>
                    @include('partials.attendance-status-badge', [
                        'status' => $schedule->is_active ? 'active' : 'inactive',
                    ])
                </div>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Back</a>
        </div>
    </header>

    <div class="card">
        <div class="card__body">
            <form method="post" action="{{ route('attendance.schedules.update', $schedule) }}" data-aa-schedule-form data-aa-submit>
                @csrf
                @method('PUT')

                <section class="aa-form-section" aria-labelledby="aa-emp-section">
                    <h2 class="aa-form-section__title" id="aa-emp-section">Employee</h2>
                    <div class="form-group">
                        <label class="form-label" for="user_id">Employee</label>
                        <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" @selected(old('user_id', $schedule->user_id) == $e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
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
                                <option value="{{ $shift->id }}" @selected(old('shift_id', $schedule->shift_id) == $shift->id)>{{ $shift->name }}</option>
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
                            <input type="time" name="time_in" id="time_in" class="form-control" value="{{ old('time_in', substr((string) $schedule->time_in, 0, 5)) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_out">Time Out</label>
                            <input type="time" name="time_out" id="time_out" class="form-control" value="{{ old('time_out', substr((string) $schedule->time_out, 0, 5)) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="break_start">Break Start</label>
                            <input type="time" name="break_start" id="break_start" class="form-control" value="{{ old('break_start', $schedule->break_start ? substr((string) $schedule->break_start, 0, 5) : '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="break_end">Break End</label>
                            <input type="time" name="break_end" id="break_end" class="form-control" value="{{ old('break_end', $schedule->break_end ? substr((string) $schedule->break_end, 0, 5) : '') }}">
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
                        'selected' => old('work_days', $schedule->work_days ?? []),
                        'label' => 'Work days',
                    ])
                </section>

                <section class="aa-form-section" aria-labelledby="aa-restdays-section">
                    <h2 class="aa-form-section__title" id="aa-restdays-section">Rest Days</h2>
                    @include('partials.attendance-day-chips', [
                        'name' => 'rest_days',
                        'days' => $restDayOrder,
                        'selected' => old('rest_days', $schedule->rest_days ?? []),
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
                            <input type="date" name="effective_from" id="effective_from" class="form-control" value="{{ old('effective_from', optional($schedule->effective_from)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="effective_to">Effective To</label>
                            <input type="date" name="effective_to" id="effective_to" class="form-control" value="{{ old('effective_to', optional($schedule->effective_to)->format('Y-m-d')) }}">
                            <p class="aa-form-hint">Leave Effective To blank if the schedule has no planned end date.</p>
                        </div>
                    </div>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-active-section">
                    <h2 class="aa-form-section__title" id="aa-active-section">Active Schedule</h2>
                    <label class="aa-toggle">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $schedule->is_active))>
                        <span class="aa-toggle__track" aria-hidden="true"></span>
                        <span>Active schedule</span>
                    </label>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-notes-section">
                    <h2 class="aa-form-section__title" id="aa-notes-section">Notes</h2>
                    <div class="form-group">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $schedule->notes) }}</textarea>
                    </div>
                </section>

                <div class="aa-form-actions">
                    <button class="btn btn--primary" type="submit">
                        <span class="aa-btn-text">Update Schedule</span>
                        <span class="aa-btn-spinner" aria-hidden="true"></span>
                    </button>
                    <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
