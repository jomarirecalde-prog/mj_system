@extends('layouts.app')

@section('title', 'Attendance Reports')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@section('content')
@php
    $reportMeta = [
        'daily' => [
            'desc' => 'View attendance records for a selected date.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        ],
        'monthly' => [
            'desc' => 'Review attendance for a selected month.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        ],
        'late' => [
            'desc' => 'Review late arrivals.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
        'absence' => [
            'desc' => 'Review absences.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>',
        ],
        'undertime' => [
            'desc' => 'Review undertime records.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>',
        ],
        'overtime' => [
            'desc' => 'Review overtime records.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
        ],
        'department' => [
            'desc' => 'Review attendance by department.',
            'icon' => '<path stroke-linecap="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        ],
    ];
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">Attendance Reports</h1>
                <p class="aa-page-header__desc">Generate and export attendance reports.</p>
            </div>
        </div>
    </header>

    <div class="aa-report-grid">
        @foreach($reports as $type => $title)
            @php $meta = $reportMeta[$type] ?? ['desc' => $title, 'icon' => '<path stroke-linecap="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>']; @endphp
            <a href="{{ route('attendance.reports.show', $type) }}" class="aa-report-card">
                <span class="aa-report-card__icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $meta['icon'] !!}</svg>
                </span>
                <h2 class="aa-report-card__title">{{ $title }}</h2>
                <p class="aa-report-card__desc">{{ $meta['desc'] }}</p>
                <span class="aa-report-card__action">Open Report →</span>
            </a>
        @endforeach
    </div>
</div>
@endsection
