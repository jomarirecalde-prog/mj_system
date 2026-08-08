@extends('layouts.app')
@section('title', $department->name)
@section('content')
<div class="page-header"><div><h1>{{ $department->name }}</h1></div><div class="btn-group"><a href="{{ route('departments.index') }}" class="btn btn--secondary">Back</a>@if(auth()->user()->isAdmin())<a href="{{ route('departments.edit', $department) }}" class="btn btn--primary">Edit</a>@endif</div></div>
<div class="card"><div class="card__body"><dl class="dl-grid"><div class="dl-item"><dt>Code</dt><dd>{{ $department->code ?? '—' }}</dd></div><div class="dl-item"><dt>Status</dt><dd>{{ $department->is_active ? 'Active' : 'Inactive' }}</dd></div></dl>@if($department->description)<p class="mt-2">{{ $department->description }}</p>@endif</div></div>
@endsection
