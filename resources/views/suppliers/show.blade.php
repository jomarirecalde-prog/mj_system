@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<div class="page-header"><div><h1>{{ $supplier->name }}</h1></div><div class="btn-group"><a href="{{ route('suppliers.index') }}" class="btn btn--secondary">Back</a>@if(auth()->user()->isAdmin())<a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn--primary">Edit</a>@endif</div></div>
<div class="card"><div class="card__body"><dl class="dl-grid">
<div class="dl-item"><dt>Contact</dt><dd>{{ $supplier->contact_person ?? '—' }}</dd></div>
<div class="dl-item"><dt>Email</dt><dd>{{ $supplier->email ?? '—' }}</dd></div>
<div class="dl-item"><dt>Phone</dt><dd>{{ $supplier->phone ?? '—' }}</dd></div>
<div class="dl-item"><dt>Status</dt><dd>{{ $supplier->is_active ? 'Active' : 'Inactive' }}</dd></div>
</dl>@if($supplier->address)<p class="mt-2">{{ $supplier->address }}</p>@endif</div></div>
@endsection
