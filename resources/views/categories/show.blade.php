@extends('layouts.app')
@section('title', $category->name)
@section('content')
<div class="page-header">
    <div><h1>{{ $category->name }}</h1><p class="page-header__meta">{{ $category->items_count ?? 0 }} linked items</p></div>
    <div class="btn-group">
        <a href="{{ route('categories.index') }}" class="btn btn--secondary">Back</a>
        @if(auth()->user()->isAdmin())<a href="{{ route('categories.edit', $category) }}" class="btn btn--primary">Edit</a>@endif
    </div>
</div>
<div class="card"><div class="card__body">
<dl class="dl-grid">
<div class="dl-item"><dt>Code</dt><dd>{{ $category->code ?? '—' }}</dd></div>
<div class="dl-item"><dt>Status</dt><dd>{{ $category->is_active ? 'Active' : 'Inactive' }}</dd></div>
</dl>
@if($category->description)<p class="mt-2">{{ $category->description }}</p>@endif
</div></div>
@endsection
