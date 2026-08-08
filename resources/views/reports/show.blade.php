@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="page-header">
    <div><h1>{{ $title }}</h1><p class="page-header__meta">{{ $rows->count() }} row(s)</p></div>
    <div class="btn-group">
        <a href="{{ route('reports.index') }}" class="btn btn--secondary">All reports</a>
        <a href="{{ route('reports.export', array_merge(['type' => $type, 'format' => 'pdf'], request()->query())) }}" class="btn btn--secondary">PDF</a>
        <a href="{{ route('reports.export', array_merge(['type' => $type, 'format' => 'excel'], request()->query())) }}" class="btn btn--secondary">Excel</a>
        <a href="{{ route('reports.export', array_merge(['type' => $type, 'format' => 'csv'], request()->query())) }}" class="btn btn--secondary">CSV</a>
        <a href="{{ route('reports.export', array_merge(['type' => $type, 'format' => 'print'], request()->query())) }}" class="btn btn--primary" target="_blank">Print</a>
    </div>
</div>

@if($type === 'stock-movement')
<div class="card mb-2"><div class="card__body">
    <form method="get" action="{{ route('reports.show', $type) }}" class="filters">
        <div class="form-group"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}"></div>
        <div class="form-group"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}"></div>
        <button type="submit" class="btn btn--secondary">Apply dates</button>
    </form>
</div></div>
@endif

<div class="card">
    <div class="card__body table-wrap">
        @if($rows->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No data for this report</p></div>
        @else
            @php
                $moneyCols = ['Value', 'Total Value', 'Unit Cost'];
            @endphp
            <table class="data-table">
                <thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $i => $cell)
                            <td>
                                @if(isset($headers[$i]) && in_array($headers[$i], $moneyCols, true) && is_numeric($cell))
                                    {{ money($cell) }}
                                @else
                                    {{ $cell ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
