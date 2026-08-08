@extends('layouts.app')
@section('title', 'Suppliers')
@section('content')
<div class="page-header"><div><h1>Suppliers</h1></div>@if(auth()->user()->isAdmin())<a href="{{ route('suppliers.create') }}" class="btn btn--primary">Add supplier</a>@endif</div>
<div class="card mb-2"><div class="card__body"><form method="get" class="filters"><div class="form-group"><label class="form-label">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div><button type="submit" class="btn btn--secondary">Search</button></form></div></div>
<div class="card"><div class="card__body table-wrap">
@if($suppliers->isEmpty())<div class="empty-state"><p class="empty-state__title">No suppliers</p></div>
@else<table class="data-table"><thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($suppliers as $row)<tr>
<td><a href="{{ route('suppliers.show', $row) }}">{{ $row->name }}</a></td>
<td>{{ $row->contact_person ?? '—' }}</td><td>{{ $row->email ?? '—' }}</td>
<td><span class="badge {{ $row->is_active ? 'badge--available' : 'badge--archived' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>@if(auth()->user()->isAdmin())<a href="{{ route('suppliers.edit', $row) }}" class="btn btn--ghost btn--sm">Edit</a>@endif</td>
</tr>@endforeach</tbody></table>@include('partials.pagination', ['paginator' => $suppliers->withQueryString()])@endif
</div></div>
@endsection
