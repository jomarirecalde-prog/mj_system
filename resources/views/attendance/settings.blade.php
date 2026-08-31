@extends('layouts.app')

@section('title', 'Attendance Settings')

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
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Attendance Settings</h1>
                <p class="aa-page-header__desc">Configure attendance rules, schedules, scanner behavior, and holidays.</p>
                <p class="aa-page-header__meta"><span>Timezone: Asia/Manila</span></p>
            </div>
        </div>
    </header>

    <div class="aa-layout-split">
        <div class="card">
            <div class="card__header"><h2 class="card__title">Rules &amp; Defaults</h2></div>
            <div class="card__body">
                <form method="post" action="{{ route('attendance.settings.update') }}" data-aa-submit>
                    @csrf
                    @method('PUT')

                    <section class="aa-form-section" aria-labelledby="aa-rules-section">
                        <h2 class="aa-form-section__title" id="aa-rules-section">Attendance Rules</h2>
                        <div class="aa-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="grace_period">Grace Period (minutes)</label>
                                <input type="number" name="grace_period_minutes" id="grace_period" class="form-control" min="0" max="120" value="{{ $settings['grace_period_minutes'] }}" required>
                                <p class="aa-form-hint">Employees are considered on time until the configured grace period ends. Example: 8:00 + 15 min grace → Present until 8:15; Late from 8:16.</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="scan_cooldown">Scan Cooldown (seconds)</label>
                                <input type="number" name="scan_cooldown_seconds" id="scan_cooldown" class="form-control" min="0" max="600" value="{{ $settings['scan_cooldown_seconds'] }}" required>
                                <p class="aa-form-hint">Minimum time between consecutive scans from the same employee.</p>
                            </div>
                        </div>
                    </section>

                    <section class="aa-form-section" aria-labelledby="aa-hours-section">
                        <h2 class="aa-form-section__title" id="aa-hours-section">Default Work Hours</h2>
                        <div class="aa-settings-timeline" aria-hidden="true">
                            <div>{{ $fmtTime($settings['default_time_in']) }}</div>
                            <div class="aa-settings-timeline__arrow">↓</div>
                            <div>{{ $fmtTime($settings['default_break_start']) }} Lunch</div>
                            <div class="aa-settings-timeline__arrow">↓</div>
                            <div>{{ $fmtTime($settings['default_break_end']) }}</div>
                            <div class="aa-settings-timeline__arrow">↓</div>
                            <div>{{ $fmtTime($settings['default_time_out']) }}</div>
                        </div>
                        <div class="aa-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="default_time_in">Default Time In</label>
                                <input type="time" name="default_time_in" id="default_time_in" class="form-control" value="{{ $settings['default_time_in'] }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="default_time_out">Default Time Out</label>
                                <input type="time" name="default_time_out" id="default_time_out" class="form-control" value="{{ $settings['default_time_out'] }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="default_break_start">Break Start</label>
                                <input type="time" name="default_break_start" id="default_break_start" class="form-control" value="{{ $settings['default_break_start'] }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="default_break_end">Break End</label>
                                <input type="time" name="default_break_end" id="default_break_end" class="form-control" value="{{ $settings['default_break_end'] }}">
                            </div>
                        </div>
                    </section>

                    <section class="aa-form-section" aria-labelledby="aa-options-section">
                        <h2 class="aa-form-section__title" id="aa-options-section">Attendance Options</h2>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <label class="aa-toggle">
                                <input type="checkbox" name="treat_holiday_as_rest" value="1" @checked($settings['treat_holiday_as_rest'] === '1')>
                                <span class="aa-toggle__track" aria-hidden="true"></span>
                                <span>Treat holidays as rest days</span>
                            </label>
                            <label class="aa-toggle">
                                <input type="checkbox" name="location_capture" value="1" @checked($settings['location_capture'] === '1')>
                                <span class="aa-toggle__track" aria-hidden="true"></span>
                                <span>Capture location when available</span>
                            </label>
                            <label class="aa-toggle">
                                <input type="checkbox" name="require_reason_on_correction" value="1" @checked($settings['require_reason_on_correction'] === '1')>
                                <span class="aa-toggle__track" aria-hidden="true"></span>
                                <span>Require reason on DTR correction</span>
                            </label>
                        </div>
                    </section>

                    <div class="aa-form-actions">
                        <button class="btn btn--primary" type="submit">
                            <span class="aa-btn-text">Save Attendance Settings</span>
                            <span class="aa-btn-spinner" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__header"><h2 class="card__title">Holidays</h2></div>
            <div class="card__body">
                <form method="post" action="{{ route('attendance.settings.holidays.store') }}" class="aa-form-grid mb-2" data-aa-submit>
                    @csrf
                    <div class="form-group"><label class="form-label" for="holiday_name">Name</label><input name="name" id="holiday_name" class="form-control" required></div>
                    <div class="form-group"><label class="form-label" for="holiday_date">Date</label><input type="date" name="holiday_date" id="holiday_date" class="form-control" required></div>
                    <div class="form-group"><label class="form-label" for="holiday_type">Type</label>
                        <select name="type" id="holiday_type" class="form-select">
                            <option value="regular">Regular Holiday</option>
                            <option value="special">Special Holiday</option>
                        </select>
                    </div>
                    <div class="form-group aa-form-grid--1" style="grid-column:1/-1;"><label class="form-label" for="holiday_desc">Description</label><input name="description" id="holiday_desc" class="form-control"></div>
                    <div><button class="btn btn--secondary" type="submit"><span class="aa-btn-text">Add Holiday</span><span class="aa-btn-spinner" aria-hidden="true"></span></button></div>
                </form>

                @if($holidays->isEmpty())
                    <div class="aa-empty" style="padding:1.5rem;"><p class="aa-empty__text">No holidays configured.</p></div>
                @else
                    <div class="aa-shift-grid">
                        @foreach($holidays as $h)
                            <article class="aa-shift-card">
                                <div class="aa-shift-card__name">{{ $h->name }}</div>
                                <div class="aa-shift-card__hours">{{ $h->holiday_date->format('M d, Y') }}</div>
                                <div class="aa-shift-card__meta">
                                    <span class="aa-holiday-badge aa-holiday-badge--{{ $h->type }}">{{ $h->type === 'regular' ? 'Regular Holiday' : 'Special Holiday' }}</span>
                                    @if($h->description)
                                        <div>{{ $h->description }}</div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
