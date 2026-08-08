@extends('layouts.app')

@section('title', 'QR Scan Logs')

@section('content')
<div class="page-header"><div><h1>QR Attendance Scan Logs</h1><p class="page-header__meta">Every scan attempt with result</p></div></div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div>
            <div class="form-group"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ request('date') }}"></div>
            <div class="form-group"><label class="form-label">Result</label>
                <select name="result" class="form-select">
                    <option value="">All</option>
                    @foreach(['success','late','already_in','already_out','invalid','inactive','cooldown'] as $r)
                        <option value="{{ $r }}" @selected(request('result')===$r)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn--secondary" type="submit">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Scan ID</th><th>Employee</th><th>QR Code</th><th>Action</th><th>Date</th><th>Time</th><th>Scanner</th><th>Device</th><th>Result</th><th>Remarks</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->employee?->displayName() ?? '—' }}</td>
                    <td>{{ $log->qr_code }}</td>
                    <td>{{ $log->action ? str_replace('_',' ', ucfirst($log->action)) : '—' }}</td>
                    <td>{{ optional($log->scan_date)->format('M d, Y') }}</td>
                    <td>{{ $log->scan_time ? \Carbon\Carbon::parse($log->scan_time)->format('h:i:s A') : '—' }}</td>
                    <td>{{ $log->scanner?->displayName() ?? '—' }}</td>
                    <td>{{ $log->device ?? '—' }}</td>
                    <td>{{ $log->result }}</td>
                    <td>{{ $log->remarks }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-muted">No scan logs.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $logs->withQueryString()])
    </div>
</div>
@endsection
