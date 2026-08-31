@extends('layouts.app')

@section('title', 'Attendance Shifts')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '—';
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Attendance Shifts</h1>
                <p class="aa-page-header__desc">Create and manage reusable work-shift templates.</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.schedules.index') }}" class="btn btn--ghost">Back to Schedules</a>
        </div>
    </header>

    <div class="aa-layout-split">
        <div class="card">
            <div class="card__header"><h2 class="card__title">Existing Shifts</h2></div>
            <div class="card__body">
                @if($shifts->isEmpty())
                    <div class="aa-empty" style="padding:1.5rem;">
                        <p class="aa-empty__text">No shift templates have been created yet.</p>
                    </div>
                @else
                    <div class="aa-shift-grid">
                        @foreach($shifts as $s)
                            <article class="aa-shift-card">
                                <h3 class="aa-shift-card__name">{{ $s->name }}</h3>
                                <div class="aa-shift-card__hours">{{ $fmtTime($s->time_in) }} → {{ $fmtTime($s->time_out) }}</div>
                                <div class="aa-shift-card__meta">
                                    @if($s->break_start)
                                        <div><strong>Break:</strong> {{ $fmtTime($s->break_start) }} → {{ $fmtTime($s->break_end) }}</div>
                                    @endif
                                    @if($s->code)
                                        <div><strong>Code:</strong> {{ $s->code }}</div>
                                    @endif
                                    @if($s->grace_period_minutes !== null)
                                        <div><strong>Grace:</strong> {{ $s->grace_period_minutes }} minutes</div>
                                    @else
                                        <div><strong>Grace:</strong> Global default</div>
                                    @endif
                                    @if($s->description)
                                        <div>{{ $s->description }}</div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card__header"><h2 class="card__title">Add Shift</h2></div>
            <div class="card__body">
                <form method="post" action="{{ route('attendance.shifts.store') }}" data-aa-submit>
                    @csrf

                    <section class="aa-form-section" aria-labelledby="aa-shift-info">
                        <h2 class="aa-form-section__title" id="aa-shift-info">Shift Information</h2>
                        <div class="aa-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="shift_name">Name</label>
                                <input name="name" id="shift_name" class="form-control @error('name') is-invalid @enderror" required placeholder="Shift D" value="{{ old('name') }}">
                                @error('name')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="shift_code">Code</label>
                                <input name="code" id="shift_code" class="form-control @error('code') is-invalid @enderror" placeholder="SHIFT-D" value="{{ old('code') }}">
                                @error('code')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group aa-form-grid--1" style="grid-column:1/-1;">
                                <label class="form-label" for="shift_description">Description</label>
                                <textarea name="description" id="shift_description" class="form-control" rows="2">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="aa-form-section" aria-labelledby="aa-shift-hours">
                        <h2 class="aa-form-section__title" id="aa-shift-hours">Working Hours</h2>
                        <div class="aa-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="shift_time_in">Time In</label>
                                <input type="time" name="time_in" id="shift_time_in" class="form-control" required value="{{ old('time_in', '08:00') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="shift_time_out">Time Out</label>
                                <input type="time" name="time_out" id="shift_time_out" class="form-control" required value="{{ old('time_out', '17:00') }}">
                            </div>
                        </div>
                    </section>

                    <section class="aa-form-section" aria-labelledby="aa-shift-break">
                        <h2 class="aa-form-section__title" id="aa-shift-break">Break</h2>
                        <div class="aa-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="shift_break_start">Break Start</label>
                                <input type="time" name="break_start" id="shift_break_start" class="form-control" value="{{ old('break_start', '12:00') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="shift_break_end">Break End</label>
                                <input type="time" name="break_end" id="shift_break_end" class="form-control" value="{{ old('break_end', '13:00') }}">
                            </div>
                        </div>
                    </section>

                    <section class="aa-form-section" aria-labelledby="aa-shift-grace">
                        <h2 class="aa-form-section__title" id="aa-shift-grace">Attendance Rule</h2>
                        <div class="form-group">
                            <label class="form-label" for="shift_grace">Grace Period (minutes)</label>
                            <input type="number" name="grace_period_minutes" id="shift_grace" class="form-control" min="0" max="120" placeholder="Use global" value="{{ old('grace_period_minutes') }}">
                            <p class="aa-form-hint">Leave blank to use the global grace period.</p>
                        </div>
                    </section>

                    <div class="aa-form-actions">
                        <button class="btn btn--primary" type="submit">
                            <span class="aa-btn-text">Create Shift</span>
                            <span class="aa-btn-spinner" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
