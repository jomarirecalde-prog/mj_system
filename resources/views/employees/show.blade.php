@extends('layouts.app')

@section('title', $employee->displayName())

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $employee->displayName() }}</h1>
        <p class="page-header__meta">{{ $employee->employee_id }} · {{ $employee->department }} · {{ $employee->position ?? 'No position' }}</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn--secondary">Edit</a>
            <a href="{{ route('attendance.qr.show', $employee) }}" class="btn btn--secondary">QR code</a>
        @endif
        <a href="{{ route('employees.index') }}" class="btn btn--ghost">Back</a>
    </div>
</div>

<div class="grid-2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Employee details</h2></div>
        <div class="card__body">
            <dl class="dl-grid">
                <div class="dl-item"><dt>Employee ID</dt><dd>{{ $employee->employee_id }}</dd></div>
                <div class="dl-item"><dt>Full name</dt><dd>{{ $employee->displayName() }}</dd></div>
                <div class="dl-item"><dt>Department</dt><dd>{{ $employee->department ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Position</dt><dd>{{ $employee->position ?? '—' }}</dd></div>
                <div class="dl-item"><dt>Status</dt><dd>
                    <span class="badge {{ $employee->status === 'active' ? 'badge--available' : 'badge--archived' }}">{{ ucfirst($employee->status) }}</span>
                </dd></div>
                <div class="dl-item"><dt>Email</dt><dd>{{ $employee->email }}</dd></div>
                <div class="dl-item"><dt>System role</dt><dd>{{ ucfirst($employee->role) }}</dd></div>
                <div class="dl-item"><dt>Schedule</dt><dd>{{ $employee->activeSchedule?->scheduleLabel() ?? 'Default' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card__header"><h2 class="card__title">Attendance QR</h2></div>
        <div class="card__body">
            @if($employee->activeQrCode)
                <p style="font-size:1.15rem;font-weight:700;">{{ $employee->activeQrCode->code }}</p>
                <p class="text-muted">Active · generated {{ ph_datetime($employee->activeQrCode->generated_at) }}</p>
                @if(auth()->user()->isAdmin())
                    <div class="btn-group mt-1">
                        <a href="{{ route('attendance.qr.show', $employee) }}" class="btn btn--primary btn--sm">Manage QR</a>
                        <a href="{{ route('attendance.qr.print', $employee) }}" class="btn btn--secondary btn--sm" target="_blank">Print</a>
                    </div>
                @endif
            @else
                <p class="text-muted">No active QR code.</p>
                @if(auth()->user()->isAdmin())
                    <form method="post" action="{{ route('attendance.qr.generate', $employee) }}" class="mt-1">@csrf
                        <button type="submit" class="btn btn--primary btn--sm">Generate QR</button>
                    </form>
                @endif
            @endif

            <div class="btn-group mt-2" style="flex-wrap:wrap;">
                <a href="{{ route('attendance.monthly', ['employee_id' => $employee->id]) }}" class="btn btn--ghost btn--sm">Monthly DTR</a>
                <a href="{{ route('attendance.records', ['employee_id' => $employee->id]) }}" class="btn btn--ghost btn--sm">DTR records</a>
            </div>
        </div>
    </div>
</div>
@endsection
