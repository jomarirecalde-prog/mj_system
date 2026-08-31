@extends('layouts.print')

@section('title', 'Print Employee QR — ' . $user->displayName())

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
<style>
    @media print {
        body { margin: 0; background: #fff; }
    }
</style>
@endpush

@section('content')
<div class="aa-print-qr">
    @if(setting('organization_name'))
        <div class="aa-print-qr__org">{{ setting('organization_name') }}</div>
    @endif
    <div class="aa-print-qr__badge">Attendance QR</div>
    <h1 class="aa-print-qr__name">{{ $user->displayName() }}</h1>
    <p class="aa-print-qr__meta">
        {{ $user->employee_id }}<br>
        {{ $user->department ?? '—' }} · {{ $user->position ?? '—' }}
    </p>
    <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="Employee attendance QR code" class="aa-print-qr__image">
    <div class="aa-print-qr__code">{{ $qr->code }}</div>
    <div class="aa-print-qr__notice">
        <p>Use this QR code for attendance scanning.</p>
        <p><strong>Do not share or reproduce this QR code.</strong></p>
    </div>
</div>
@endsection
