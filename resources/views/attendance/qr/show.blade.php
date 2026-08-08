@extends('layouts.app')

@section('title', 'Employee QR')

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $user->displayName() }}</h1>
        <p class="page-header__meta">{{ $user->employee_id }} · {{ $user->department }} · {{ $user->position }} · {{ ucfirst($user->status) }}</p>
    </div>
    <a href="{{ route('attendance.qr.index') }}" class="btn btn--ghost">Back</a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Active QR code</h2></div>
        <div class="card__body" style="text-align:center;">
            @if($qr && $qrImage)
                <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="Employee QR" style="width:220px;height:220px;object-fit:contain;">
                <p style="font-size:1.15rem;font-weight:700;margin-top:.75rem;">{{ $qr->code }}</p>
                <div class="btn-group" style="justify-content:center;flex-wrap:wrap;">
                    <a href="{{ route('attendance.qr.download', $user) }}" class="btn btn--secondary">Download</a>
                    <a href="{{ route('attendance.qr.print', $user) }}" class="btn btn--secondary" target="_blank">Print</a>
                    <form method="post" action="{{ route('attendance.qr.regenerate', $user) }}" onsubmit="return confirm('Regenerate QR? Old code will be disabled.');">@csrf<button class="btn btn--ghost" type="submit">Regenerate</button></form>
                    <form method="post" action="{{ route('attendance.qr.disable', $user) }}" onsubmit="return confirm('Disable this QR code?');">@csrf<button class="btn btn--ghost" type="submit">Disable</button></form>
                </div>
            @else
                <p class="text-muted mb-2">No active QR code.</p>
                <form method="post" action="{{ route('attendance.qr.generate', $user) }}">@csrf<button class="btn btn--primary" type="submit">Generate QR code</button></form>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">QR history</h2></div>
        <div class="card__body table-wrap">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Status</th><th>Generated</th><th>Disabled</th></tr></thead>
                <tbody>
                @forelse($user->qrCodes as $code)
                    <tr>
                        <td>{{ $code->code }}</td>
                        <td>{{ ucfirst($code->status) }}</td>
                        <td>{{ ph_datetime($code->generated_at) ?? '—' }}</td>
                        <td>{{ ph_datetime($code->disabled_at) ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No QR history.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
