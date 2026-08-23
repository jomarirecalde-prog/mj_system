@extends('layouts.app')

@section('title', 'DTR Corrections')

@section('content')
<div class="page-header">
    <div><h1>DTR Corrections</h1><p class="page-header__meta">Manual adjustments with full audit history</p></div>
    <div class="btn-group">
        <a href="{{ route('attendance.correction-requests.index') }}" class="btn btn--secondary">
            Employee requests @if(($pendingCount ?? 0) > 0) ({{ $pendingCount }}) @endif
        </a>
        <a href="{{ route('attendance.corrections.create') }}" class="btn btn--primary">New correction</a>
    </div>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div>
            <button class="btn btn--secondary" type="submit">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        <table class="data-table">
            <thead><tr><th>Employee</th><th>Field</th><th>Original</th><th>Corrected</th><th>Reason</th><th>By</th><th>When</th></tr></thead>
            <tbody>
            @forelse($adjustments as $adj)
                <tr>
                    <td>{{ $adj->employee?->displayName() }}</td>
                    <td>{{ $adj->field_name }}</td>
                    <td>{{ $adj->original_value ?? '—' }}</td>
                    <td>{{ $adj->corrected_value ?? '—' }}</td>
                    <td>{{ $adj->reason }}</td>
                    <td>{{ $adj->corrector?->displayName() }}</td>
                    <td>{{ ph_datetime($adj->corrected_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No corrections yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $adjustments->withQueryString()])
    </div>
</div>
@endsection
