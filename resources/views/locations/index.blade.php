@extends('layouts.app')
@section('title', 'Locations')
@section('content')
<div class="page-header"><div><h1>Locations</h1><p class="page-header__meta">Storage and office locations</p></div>
@if(auth()->user()->isAdmin())<a href="{{ route('locations.create') }}" class="btn btn--primary">Add location</a>@endif</div>
<div class="card mb-2"><div class="card__body"><form method="get" class="filters"><div class="form-group"><label class="form-label" for="search">Search</label><input type="search" name="search" class="form-control" value="{{ request('search') }}"></div><button type="submit" class="btn btn--secondary">Search</button></form></div></div>
<div class="card"><div class="card__body table-wrap">
@if($locations->isEmpty())<div class="empty-state"><p class="empty-state__title">No locations</p></div>
@else
<table class="data-table"><thead><tr><th>Name</th><th>Building</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($locations as $row)<tr>
<td><a href="{{ route('locations.show', $row) }}">{{ $row->name }}</a></td>
<td>{{ $row->building ?? '—' }}</td>
<td><span class="badge {{ $row->is_active ? 'badge--available' : 'badge--archived' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
<td>@if(auth()->user()->isAdmin())<a href="{{ route('locations.edit', $row) }}" class="btn btn--ghost btn--sm">Edit</a>@endif</td>
</tr>@endforeach
</tbody></table>@include('partials.pagination', ['paginator' => $locations->withQueryString()])@endif
</div></div>
@endsection
