@extends('layouts.print')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
<style>
    @media print {
        body { margin: 0; background: #fff; }
        @page { margin: 1.5cm; }
    }
</style>
@endpush

@section('content')
<div class="aa-print-report">
    <header class="aa-print-report__header">
        @if(setting('organization_name'))
            <div class="aa-print-report__org">{{ setting('organization_name') }}</div>
        @endif
        <h1 class="aa-print-report__title">Attendance Report</h1>
        <p class="aa-print-report__meta" style="margin:0;font-weight:600;">{{ $title }}</p>
        <p class="aa-print-report__meta">
            Generated: {{ now('Asia/Manila')->format('M d, Y h:i A') }}<br>
            Timezone: Asia/Manila
        </p>
    </header>

    <table>
        <thead>
            <tr>@foreach($headers as $h)<th scope="col">{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($headers) }}">No attendance data available for the selected period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
