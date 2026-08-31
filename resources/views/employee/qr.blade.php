@extends('layouts.employee')

@section('title', 'My QR Code')

@section('content')
<div class="page-header">
    <div>
        <h1>My QR Code</h1>
        <p class="page-header__meta">Scan this code at the attendance station to log your time in and out.</p>
    </div>
</div>

<div class="card emp-qr-card">
    <div class="card__body">
        @if($qr && $qrImage)
            <div class="emp-qr-card__frame">
                <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="My QR Code">
            </div>
            <p class="emp-qr-card__id">{{ $user->employee_id }}</p>
            <p class="text-muted">{{ $user->displayName() }}</p>
            <div class="btn-group emp-qr-card__actions">
                <a href="{{ route('employee.qr.download') }}" class="btn btn--secondary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="margin-right:0.35rem;"><path stroke-linecap="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download
                </a>
                <a href="{{ route('employee.qr.print') }}" class="btn btn--secondary" target="_blank">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="margin-right:0.35rem;"><path stroke-linecap="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </a>
            </div>
            <div class="emp-qr-card__hint">
                Keep this QR code private. Do not share it with others — it is linked to your employee account and used for attendance tracking.
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state__title">No QR code assigned</div>
                <p>Please contact your administrator to have a QR code assigned to your account.</p>
            </div>
        @endif
    </div>
</div>
@endsection
