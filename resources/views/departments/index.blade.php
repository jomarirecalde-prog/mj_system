@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<div class="page-header"><div><h1>Departments</h1></div>@if(auth()->user()->isAdmin())<a href="{{ route('departments.create') }}" class="btn btn--primary">Add department</a>@endif</div>
<div class="card mb-2"><div class="card__body"><form method="get" class="filters"><div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div><button type="submit" class="btn btn--secondary">Search</button></form></div></div>
<div class="card"><div class="card__body table-wrap">
@if($departments->isEmpty())<div class="empty-state"><p class="empty-state__title">No departments</p></div>
@else<table class="data-table"><thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($departments as $row)<tr>
<td><a href="{{ route('departments.show', $row) }}">{{ $row->name }}</a></td><td>{{ $row->code ?? '—' }}</td>
<td><span class="badge {{ $row->is_active ? 'badge--available' : 'badge--archived' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>@if(auth()->user()->isAdmin())<a href="{{ route('departments.edit', $row) }}" class="btn btn--ghost btn--sm">Edit</a>@endif</td>
</tr>@endforeach</tbody></table>@include('partials.pagination', ['paginator' => $departments->withQueryString()])@endif
</div></div>
@endsection
