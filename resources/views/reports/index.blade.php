@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="page-header">
    <div><h1>Reports</h1><p class="page-header__meta">Generate and export inventory insights</p></div>
</div>

<div class="grid grid--2">
    @foreach($reports as $slug => $label)
        <a href="{{ route('reports.show', $slug) }}" class="card" style="text-decoration:none;color:inherit;">
            <div class="card__body">
                <h2 class="card__title">{{ $label }}</h2>
                <p class="text-muted mb-0" style="font-size:0.9rem;">View data and export PDF, Excel, or CSV</p>
            </div>
        </a>
    @endforeach
</div>
@endsection
