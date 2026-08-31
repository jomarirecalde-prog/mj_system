@extends('layouts.employee')

@section('title', 'Official Time Request')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/official-time.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/official-time.js') }}" defer></script>
@endpush

@section('content')
@php
    use App\Models\OfficialTimeRequest as OTR;
    $fmt = fn (?string $t) => OTR::formatTimeField($t);
    $scheduleType = ucfirst($current['schedule_type'] ?? 'regular');
@endphp

<div class="page-header">
    <div>
        <h1>Official Time Request</h1>
        <p class="page-header__meta">Request an official working time or schedule adjustment for approval by the Super Admin.</p>
    </div>
</div>

<div class="card mb-2 ot-current-card">
    <div class="card__header">
        <h2 class="card__title">Current Official Time</h2>
        <span class="ot-badge ot-badge--approved"><span class="ot-badge__icon" aria-hidden="true">✓</span><span class="ot-badge__text">Active</span></span>
    </div>
    <div class="card__body">
        <dl class="dl-grid">
            <div class="dl-item"><dt>Time In</dt><dd id="ot-current-in">{{ $fmt($current['time_in']) }}</dd></div>
            <div class="dl-item"><dt>Time Out</dt><dd id="ot-current-out">{{ $fmt($current['time_out']) }}</dd></div>
            <div class="dl-item"><dt>Break</dt><dd id="ot-current-break">
                @if(!empty($current['break_start']) && !empty($current['break_end']))
                    {{ $fmt($current['break_start']) }} – {{ $fmt($current['break_end']) }}
                @else
                    —
                @endif
            </dd></div>
            <div class="dl-item"><dt>Schedule Type</dt><dd>{{ $scheduleType }}</dd></div>
        </dl>
    </div>
</div>

<div class="card mb-2" id="ot-request-form-card">
    <div class="card__header">
        <h2 class="card__title">Request Official Time</h2>
        <button type="button" class="btn btn--primary btn--sm" id="ot-toggle-form" aria-expanded="true" aria-controls="ot-request-form">+ Request Official Time</button>
    </div>
    <div class="card__body" id="ot-request-form">
        <form method="post" action="{{ route('employee.official-time.store') }}" id="ot-form" novalidate>
            @csrf

            <fieldset class="ot-fieldset">
                <legend class="ot-fieldset__legend">Effective Period</legend>
                <div class="ot-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="effective_from">Effective From</label>
                        <input type="date" name="effective_from" id="effective_from" class="form-control" value="{{ old('effective_from') }}" required>
                        @error('effective_from')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group" id="ot-effective-to-group">
                        <label class="form-label" for="effective_to">Effective To</label>
                        <input type="date" name="effective_to" id="effective_to" class="form-control" value="{{ old('effective_to') }}">
                        @error('effective_to')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_permanent" id="is_permanent" value="1" @checked(old('is_permanent'))>
                        <span>No end date (permanent official time)</span>
                    </label>
                </div>
            </fieldset>

            <fieldset class="ot-fieldset">
                <legend class="ot-fieldset__legend">Working Time</legend>
                <div class="ot-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="requested_time_in">Requested Time In</label>
                        <input type="time" name="requested_time_in" id="requested_time_in" class="form-control" value="{{ old('requested_time_in', substr($current['time_in'] ?? '08:00', 0, 5)) }}" required>
                        @error('requested_time_in')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="requested_time_out">Requested Time Out</label>
                        <input type="time" name="requested_time_out" id="requested_time_out" class="form-control" value="{{ old('requested_time_out', substr($current['time_out'] ?? '17:00', 0, 5)) }}" required>
                        @error('requested_time_out')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="ot-fieldset">
                <legend class="ot-fieldset__legend">Break</legend>
                <div class="ot-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="requested_break_start">Requested Break Start</label>
                        <input type="time" name="requested_break_start" id="requested_break_start" class="form-control" value="{{ old('requested_break_start', !empty($current['break_start']) ? substr($current['break_start'], 0, 5) : '') }}">
                        @error('requested_break_start')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="requested_break_end">Requested Break End</label>
                        <input type="time" name="requested_break_end" id="requested_break_end" class="form-control" value="{{ old('requested_break_end', !empty($current['break_end']) ? substr($current['break_end'], 0, 5) : '') }}">
                        @error('requested_break_end')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </fieldset>

            <div class="ot-visual-summary" id="ot-visual-summary" aria-live="polite">
                <h3 class="ot-visual-summary__title">Requested Official Time</h3>
                <div class="ot-timeline">
                    <div class="ot-timeline__node" data-ot-summary="in">—</div>
                    <div class="ot-timeline__arrow">↓</div>
                    <div class="ot-timeline__node ot-timeline__node--break" data-ot-summary="break-start">—</div>
                    <div class="ot-timeline__label">Break</div>
                    <div class="ot-timeline__arrow">↓</div>
                    <div class="ot-timeline__node ot-timeline__node--break" data-ot-summary="break-end">—</div>
                    <div class="ot-timeline__arrow">↓</div>
                    <div class="ot-timeline__node" data-ot-summary="out">—</div>
                </div>
            </div>

            <fieldset class="ot-fieldset">
                <legend class="ot-fieldset__legend">Reason</legend>
                <div class="form-group">
                    <label class="form-label" for="reason">Reason / Justification</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" required maxlength="1000">{{ old('reason') }}</textarea>
                    @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Additional Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                    @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            <div class="ot-comparison" id="ot-comparison">
                <h3 class="ot-comparison__title">Review Changes</h3>
                <div class="ot-comparison__grid">
                    <div class="ot-comparison__col">
                        <h4>Current Official Time</h4>
                        <p class="ot-comparison__range" id="ot-compare-current-range">{{ $fmt($current['time_in']) }} → {{ $fmt($current['time_out']) }}</p>
                        <ul class="ot-comparison__changes">
                            <li><span>Time In</span> <strong id="ot-compare-current-in">{{ $fmt($current['time_in']) }}</strong></li>
                            <li><span>Time Out</span> <strong id="ot-compare-current-out">{{ $fmt($current['time_out']) }}</strong></li>
                            <li><span>Break</span> <strong id="ot-compare-current-break">
                                @if(!empty($current['break_start']) && !empty($current['break_end']))
                                    {{ $fmt($current['break_start']) }} – {{ $fmt($current['break_end']) }}
                                @else — @endif
                            </strong></li>
                        </ul>
                    </div>
                    <div class="ot-comparison__col ot-comparison__col--requested">
                        <h4>Requested Official Time</h4>
                        <p class="ot-comparison__range" id="ot-compare-requested-range">—</p>
                        <ul class="ot-comparison__changes">
                            <li><span>Time In</span> <strong id="ot-compare-requested-in">—</strong></li>
                            <li><span>Time Out</span> <strong id="ot-compare-requested-out">—</strong></li>
                            <li><span>Break</span> <strong id="ot-compare-requested-break">—</strong></li>
                        </ul>
                    </div>
                </div>
                <div class="ot-comparison__highlights" id="ot-comparison-highlights"></div>
            </div>

            <div class="ot-form-actions">
                <button type="submit" class="btn btn--primary" id="ot-submit-btn">
                    <span class="ot-btn-text">Submit Request</span>
                    <span class="ot-btn-loading" hidden>Submitting…</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">My Official Time Requests</h2>
        @if($pendingCount > 0)
            <span class="ot-badge ot-badge--pending"><span class="ot-badge__icon" aria-hidden="true">●</span><span class="ot-badge__text">{{ $pendingCount }} Pending</span></span>
        @endif
    </div>
    <div class="card__body">
        @if($requests->isEmpty())
            <div class="ot-empty">
                <p class="ot-empty__title">No official time requests yet.</p>
                <p class="ot-empty__text">Request an official time adjustment when you need one.</p>
                <button type="button" class="btn btn--primary" data-ot-scroll-form>Request Official Time</button>
            </div>
        @else
            <div class="table-wrap ot-table-desktop">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Effective Period</th>
                        <th>Current Time</th>
                        <th>Requested Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Reviewed</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($requests as $req)
                        <tr>
                            <td>{{ $req->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</td>
                            <td>{{ $req->effectivePeriodLabel() }}</td>
                            <td>{{ $req->timeRangeLabel('current') }}</td>
                            <td>{{ $req->timeRangeLabel('requested') }}</td>
                            <td>{{ Str::limit($req->reason, 60) }}</td>
                            <td>@include('partials.official-time-status-badge', ['status' => $req->status])</td>
                            <td>{{ $req->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td>{{ $req->reviewed_at ? $req->reviewed_at->timezone('Asia/Manila')->format('M d, Y h:i A') : '—' }}</td>
                            <td><a href="{{ route('employee.official-time.show', $req) }}" class="btn btn--ghost btn--sm">View Details</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @include('partials.pagination', ['paginator' => $requests])
            </div>

            <div class="ot-mobile-cards">
                @foreach($requests as $req)
                    <article class="ot-card">
                        <div class="ot-card__head">
                            <h3 class="ot-card__title">Official Time Request</h3>
                            @include('partials.official-time-status-badge', ['status' => $req->status])
                        </div>
                        <dl class="ot-card__dl">
                            <div><dt>Effective</dt><dd>{{ $req->effectivePeriodLabel() }}</dd></div>
                            <div><dt>Current</dt><dd>{{ $req->timeRangeLabel('current') }}</dd></div>
                            <div><dt>Requested</dt><dd>{{ $req->timeRangeLabel('requested') }}</dd></div>
                            <div><dt>Reason</dt><dd>{{ Str::limit($req->reason, 80) }}</dd></div>
                        </dl>
                        <a href="{{ route('employee.official-time.show', $req) }}" class="btn btn--secondary btn--block">View Details</a>
                    </article>
                @endforeach
                @include('partials.pagination', ['paginator' => $requests])
            </div>
        @endif
    </div>
</div>
@endsection
