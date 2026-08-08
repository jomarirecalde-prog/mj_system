@extends('layouts.app')

@section('title', 'Attendance Reports')

@section('content')
<div class="page-header"><div><h1>Attendance Reports</h1><p class="page-header__meta">Daily, monthly, late, absence, undertime, overtime, department</p></div></div>
<div class="card">
    <div class="card__body">
        <div class="stat-grid">
            @foreach($reports as $type => $title)
                <a href="{{ route('attendance.reports.show', $type) }}" class="stat-card" style="text-decoration:none;color:inherit;">
                    <div class="stat-card__label">{{ ucfirst($type) }}</div>
                    <div class="stat-card__value" style="font-size:1rem;">{{ $title }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
