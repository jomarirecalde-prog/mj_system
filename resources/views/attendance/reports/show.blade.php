@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $queryParams = request()->query();
    $exportBase = fn ($format) => route('attendance.reports.export', ['type' => $type, 'format' => $format] + $queryParams);
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">{{ $title }}</h1>
                <p class="aa-page-header__desc">
                    <a href="{{ route('attendance.reports.index') }}" class="btn btn--ghost btn--sm" style="padding:0;">← All Reports</a>
                </p>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a class="btn btn--primary" href="{{ $exportBase('pdf') }}">PDF</a>
            <div class="aa-export" data-aa-export>
                <button type="button" class="btn btn--secondary" data-aa-export-toggle aria-expanded="false" aria-haspopup="true">Export ▾</button>
                <div class="aa-export__menu" role="menu">
                    <a href="{{ $exportBase('pdf') }}" role="menuitem">PDF</a>
                    <a href="{{ $exportBase('excel') }}" role="menuitem">Excel</a>
                    <a href="{{ $exportBase('csv') }}" role="menuitem">CSV</a>
                    <a href="{{ $exportBase('print') }}" role="menuitem" target="_blank" rel="noopener">Print</a>
                </div>
            </div>
            <a class="btn btn--ghost" href="{{ $exportBase('print') }}" target="_blank" rel="noopener">Print</a>
        </div>
    </header>

    <div class="card aa-filters">
        <div class="card__body">
            <form id="aa-filters-report" method="get" action="{{ route('attendance.reports.show', $type) }}">
                <div class="aa-filters__top">
                    <div class="aa-filters__grid" style="flex:1;">
                        @if(in_array($type, ['daily','department'], true))
                            <div class="form-group">
                                <label class="form-label" for="aa-report-date">Date</label>
                                <input type="date" name="date" id="aa-report-date" class="form-control" value="{{ request('date', now('Asia/Manila')->toDateString()) }}">
                            </div>
                        @endif
                        @if($type === 'monthly')
                            <div class="form-group">
                                <label class="form-label" for="aa-report-month">Month</label>
                                <input type="month" name="month" id="aa-report-month" class="form-control" value="{{ request('month', now('Asia/Manila')->format('Y-m')) }}">
                            </div>
                        @endif
                        @if(in_array($type, ['late','absence','undertime','overtime'], true))
                            <div class="form-group">
                                <label class="form-label" for="aa-report-from">From</label>
                                <input type="date" name="date_from" id="aa-report-from" class="form-control" value="{{ request('date_from', now('Asia/Manila')->startOfMonth()->toDateString()) }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="aa-report-to">To</label>
                                <input type="date" name="date_to" id="aa-report-to" class="form-control" value="{{ request('date_to', now('Asia/Manila')->toDateString()) }}">
                            </div>
                        @endif
                    </div>
                    <div class="aa-filters__actions">
                        <button type="submit" class="btn btn--primary">Apply</button>
                        <button type="button" class="btn btn--ghost" id="aa-filters-report-clear">Reset</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__body">
            @if($rows->isEmpty())
                <div class="aa-empty">
                    <svg class="aa-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h2 class="aa-empty__title">No attendance data</h2>
                    <p class="aa-empty__text">No attendance data is available for the selected period.</p>
                </div>
            @else
                <div class="aa-table-wrap">
                    <table class="aa-table">
                        <thead>
                        <tr>@foreach($headers as $h)<th scope="col">{{ $h }}</th>@endforeach</tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
