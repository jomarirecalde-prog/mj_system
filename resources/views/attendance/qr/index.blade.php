@extends('layouts.app')

@section('title', 'Employee QR Codes')

@section('content')
<div class="page-header"><div><h1>Employee QR Codes</h1><p class="page-header__meta">Secure identifiers only (e.g. EMP-2026-000001)</p></div></div>

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
            <thead><tr><th>Employee</th><th>Employee ID</th><th>Department</th><th>Position</th><th>QR Code</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($employees as $e)
                <tr>
                    <td>{{ $e->displayName() }}</td>
                    <td>{{ $e->employee_id }}</td>
                    <td>{{ $e->department ?? '—' }}</td>
                    <td>{{ $e->position ?? '—' }}</td>
                    <td>{{ $e->activeQrCode?->code ?? '—' }}</td>
                    <td>{{ $e->activeQrCode ? 'Active' : 'None' }}</td>
                    <td>
                        <a href="{{ route('attendance.qr.show', $e) }}" class="btn btn--ghost btn--sm">Manage</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @include('partials.pagination', ['paginator' => $employees->withQueryString()])
    </div>
</div>
@endsection
