@extends('layouts.employee')

@section('title', 'My QR Code')

@section('content')
<div class="page-header">
    <div>
        <h1>My QR Code</h1>
        <p class="page-header__meta">Use this QR code at the attendance scanner. You cannot change your employee ID or QR identifier.</p>
    </div>
</div>

<div class="card" style="max-width:480px;">
    <div class="card__body" style="text-align:center;">
        @if($qr && $qrImage)
            <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="My QR Code" style="width:240px;height:240px;object-fit:contain;">
            <p style="font-size:1.1rem;font-weight:700;margin-top:.75rem;">{{ $user->employee_id }}</p>
            <p class="text-muted">{{ $user->displayName() }}</p>
            <div class="btn-group" style="justify-content:center;flex-wrap:wrap;margin-top:1rem;">
                <a href="{{ route('employee.qr.download') }}" class="btn btn--secondary">Download</a>
                <a href="{{ route('employee.qr.print') }}" class="btn btn--secondary" target="_blank">Print</a>
            </div>
        @else
            <p class="text-muted">No active QR code is assigned yet. Please contact an administrator.</p>
        @endif
    </div>
</div>
@endsection
