@extends('layouts.app')
@section('title', $location->name)
@section('content')
<div class="page-header"><div><h1>{{ $location->name }}</h1></div>
<div class="btn-group"><a href="{{ route('locations.index') }}" class="btn btn--secondary">Back</a>@if(auth()->user()->isAdmin())<a href="{{ route('locations.edit', $location) }}" class="btn btn--primary">Edit</a>@endif</div></div>
<div class="card"><div class="card__body"><dl class="dl-grid">
<div class="dl-item"><dt>Code</dt><dd>{{ $location->code ?? '—' }}</dd></div>
<div class="dl-item"><dt>Building</dt><dd>{{ $location->building ?? '—' }}</dd></div>
<div class="dl-item"><dt>Office</dt><dd>{{ $location->office ?? '—' }}</dd></div>
<div class="dl-item"><dt>Floor</dt><dd>{{ $location->floor ?? '—' }}</dd></div>
</dl>@if($location->description)<p class="mt-2">{{ $location->description }}</p>@endif</div></div>
@endsection
