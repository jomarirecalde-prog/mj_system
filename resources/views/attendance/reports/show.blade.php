@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="page-header">
    <div><h1>{{ $title }}</h1></div>
    <div class="btn-group">
        <a class="btn btn--secondary" href="{{ route('attendance.reports.export', ['type' => $type, 'format' => 'pdf'] + request()->query()) }}">PDF</a>
        <a class="btn btn--secondary" href="{{ route('attendance.reports.export', ['type' => $type, 'format' => 'excel'] + request()->query()) }}">Excel</a>
        <a class="btn btn--secondary" href="{{ route('attendance.reports.export', ['type' => $type, 'format' => 'csv'] + request()->query()) }}">CSV</a>
        <a class="btn btn--ghost" href="{{ route('attendance.reports.export', ['type' => $type, 'format' => 'print'] + request()->query()) }}" target="_blank">Print</a>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            @if(in_array($type, ['daily','department'], true))
                <div class="form-group"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ request('date', now('Asia/Manila')->toDateString()) }}"></div>
            @endif
            @if($type === 'monthly')
                <div class="form-group"><label class="form-label">Month</label><input type="month" name="month" class="form-control" value="{{ request('month', now('Asia/Manila')->format('Y-m')) }}"></div>
            @endif
            @if(in_array($type, ['late','absence','undertime','overtime'], true))
                <div class="form-group"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from', now('Asia/Manila')->startOfMonth()->toDateString()) }}"></div>
                <div class="form-group"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to', now('Asia/Manila')->toDateString()) }}"></div>
            @endif
            <button class="btn btn--secondary" type="submit">Apply</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" class="text-muted">No data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
