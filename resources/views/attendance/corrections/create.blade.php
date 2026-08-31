@extends('layouts.app')

@section('title', 'DTR Correction / Adjustment')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">DTR Correction / Adjustment</h1>
                <p class="aa-page-header__desc">Create a controlled attendance correction while preserving the original record for auditing.</p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.corrections.index') }}" class="btn btn--ghost">Cancel</a>
        </div>
    </header>

    <div class="aa-alert-audit" role="note">
        <span class="aa-alert-audit__icon" aria-hidden="true">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <div>
            <strong>Audit preservation</strong><br>
            Original values are kept in the adjustment history. A required reason is recorded for every correction.
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            <form method="post" action="{{ route('attendance.corrections.store') }}" data-aa-submit>
                @csrf

                @if($record)
                    <input type="hidden" name="attendance_record_id" value="{{ $record->id }}">

                    <section class="aa-form-section" aria-labelledby="aa-record-section">
                        <h2 class="aa-form-section__title" id="aa-record-section">Attendance Record</h2>
                        <div class="aa-record-compare">
                            <div class="aa-record-panel">
                                <h3 class="aa-record-panel__title">Current Record</h3>
                                <div class="aa-record-panel__row"><span>Employee</span><span>{{ $record->user?->displayName() }}</span></div>
                                <div class="aa-record-panel__row"><span>Employee ID</span><span>{{ $record->user?->employee_id }}</span></div>
                                <div class="aa-record-panel__row"><span>Date</span><span>{{ $record->attendance_date?->format('M d, Y') }}</span></div>
                                <div class="aa-record-panel__row"><span>Time In</span><span>{{ $record->time_in ? ph_datetime($record->time_in, 'h:i A') : '—' }}</span></div>
                                <div class="aa-record-panel__row"><span>Time Out</span><span>{{ $record->time_out ? ph_datetime($record->time_out, 'h:i A') : '—' }}</span></div>
                                <div class="aa-record-panel__row"><span>Status</span><span>{{ $record->statusLabel() }}</span></div>
                            </div>
                            <div class="aa-record-panel aa-record-panel--corrected">
                                <h3 class="aa-record-panel__title">Corrected Record</h3>
                                <p class="aa-form-hint" style="margin:0;">Enter corrected values in the sections below.</p>
                            </div>
                        </div>
                    </section>
                @else
                    <section class="aa-form-section" aria-labelledby="aa-record-section">
                        <h2 class="aa-form-section__title" id="aa-record-section">Attendance Record</h2>
                        <div class="aa-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="user_id">Employee</label>
                                <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                    <option value="">Select employee</option>
                                    @foreach($employees as $e)
                                        <option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->displayName() }} ({{ $e->employee_id }})</option>
                                    @endforeach
                                </select>
                                @error('user_id')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="attendance_date">Attendance Date</label>
                                <input type="date" name="attendance_date" id="attendance_date" class="form-control @error('attendance_date') is-invalid @enderror" value="{{ old('attendance_date', now('Asia/Manila')->toDateString()) }}" required>
                                @error('attendance_date')<div class="form-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>
                @endif

                <section class="aa-form-section" aria-labelledby="aa-corrected-section">
                    <h2 class="aa-form-section__title" id="aa-corrected-section">Corrected Values</h2>
                    <div class="aa-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="time_in">Corrected Time In</label>
                            <input type="datetime-local" name="time_in" id="time_in" class="form-control @error('time_in') is-invalid @enderror" value="{{ old('time_in', $record && $record->time_in ? $record->time_in->timezone('Asia/Manila')->format('Y-m-d\TH:i') : '') }}">
                            <p class="aa-form-hint">Use Philippine Standard Time.</p>
                            @error('time_in')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_out">Corrected Time Out</label>
                            <input type="datetime-local" name="time_out" id="time_out" class="form-control @error('time_out') is-invalid @enderror" value="{{ old('time_out', $record && $record->time_out ? $record->time_out->timezone('Asia/Manila')->format('Y-m-d\TH:i') : '') }}">
                            <p class="aa-form-hint">Use Philippine Standard Time.</p>
                            @error('time_out')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">Keep current</option>
                                @foreach(['present','late','absent','on_leave','official_business','half_day','undertime','incomplete','rest_day'] as $s)
                                    <option value="{{ $s }}" @selected(old('status', optional($record)->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-remarks-section">
                    <h2 class="aa-form-section__title" id="aa-remarks-section">Remarks</h2>
                    <div class="form-group">
                        <label class="form-label" for="remarks">Remarks</label>
                        <input type="text" name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror" value="{{ old('remarks', optional($record)->remarks) }}" placeholder="Optional notes on the attendance record">
                        @error('remarks')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </section>

                <section class="aa-form-section" aria-labelledby="aa-reason-section">
                    <h2 class="aa-form-section__title" id="aa-reason-section">Audit Reason</h2>
                    <div class="aa-field-emphasis form-group aa-field-required">
                        <label class="form-label" for="reason">Reason</label>
                        <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" required placeholder="Why is this correction needed? This is required for audit compliance.">{{ old('reason') }}</textarea>
                        @error('reason')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </section>

                <div class="aa-form-actions">
                    <button class="btn btn--primary" type="submit">
                        <span class="aa-btn-text">Save Correction</span>
                        <span class="aa-btn-spinner" aria-hidden="true"></span>
                    </button>
                    <a href="{{ route('attendance.corrections.index') }}" class="btn btn--ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
