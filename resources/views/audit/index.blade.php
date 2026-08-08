@extends('layouts.app')

@section('title', 'Audit logs')

@section('content')
<div class="page-header"><div><h1>Audit logs</h1><p class="page-header__meta">System activity trail</p></div></div>

<div class="card mb-2"><div class="card__body">
    <form method="get" class="filters">
        <div class="form-group"><label class="form-label">Module</label><input type="text" name="module" class="form-control" value="{{ request('module') }}"></div>
        <div class="form-group"><label class="form-label">Action</label><input type="text" name="action" class="form-control" value="{{ request('action') }}"></div>
        <button type="submit" class="btn btn--secondary">Filter</button>
    </form>
</div></div>

<div class="card"><div class="card__body table-wrap">
@if($logs->isEmpty())<div class="empty-state"><p class="empty-state__title">No audit entries</p></div>
@else
<table class="data-table">
<thead><tr><th>When</th><th>User</th><th>Module</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
<tbody>
@foreach($logs as $log)
<tr>
<td>{{ ph_datetime($log->created_at) }}</td>
<td>{{ $log->user?->displayName() ?? 'System' }}</td>
<td>{{ $log->module }}</td>
<td>{{ $log->action }}</td>
<td style="max-width:280px;font-size:0.85rem;">{{ Str::limit($log->new_value ?? $log->previous_value, 80) }}</td>
<td>{{ $log->ip_address }}</td>
</tr>
@endforeach
</tbody></table>
@include('partials.pagination', ['paginator' => $logs->withQueryString()])
@endif
</div></div>
@endsection
