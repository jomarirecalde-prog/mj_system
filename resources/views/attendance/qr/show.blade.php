@extends('layouts.app')

@section('title', 'Employee QR — ' . $user->displayName())

@push('styles')
<link rel="stylesheet" href="{{ asset('css/attendance-admin.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/attendance-admin.js') }}" defer></script>
@endpush

@section('content')
@php
    $initials = strtoupper(collect(preg_split('/\s+/', trim($user->displayName())) ?: [])->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->join(''));
    $hasPhoto = !empty($user->profile_picture);
@endphp
<div class="aa-module">
    <header class="aa-page-header">
        <div class="aa-page-header__left">
            <span class="aa-page-header__icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </span>
            <div>
                <h1 class="aa-page-header__title">{{ $user->displayName() }}</h1>
                <div class="aa-page-header__meta">
                    <span>{{ $user->employee_id }}</span>
                    <span>{{ $user->department ?? '—' }}</span>
                    <span>{{ $user->position ?? '—' }}</span>
                    @include('partials.attendance-status-badge', ['status' => $user->status === 'active' ? 'active' : 'inactive', 'label' => ucfirst($user->status)])
                </div>
            </div>
        </div>
        <div class="aa-page-header__actions">
            <a href="{{ route('attendance.qr.index') }}" class="btn btn--ghost">Back</a>
        </div>
    </header>

    <div class="aa-identity-card">
        @if($hasPhoto)
            <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="" class="aa-identity-card__avatar">
        @else
            <span class="aa-identity-card__initials" aria-hidden="true">{{ $initials ?: '?' }}</span>
        @endif
        <div>
            <div class="aa-cell-primary" style="font-size:1.1rem;">{{ $user->displayName() }}</div>
            <div class="aa-cell-secondary">{{ $user->employee_id }}</div>
            <div class="aa-cell-secondary">{{ $user->department ?? '—' }} · {{ $user->position ?? '—' }}</div>
            <div style="margin-top:.5rem;">
                @include('partials.attendance-status-badge', ['status' => $user->status === 'active' ? 'active' : 'inactive', 'label' => ucfirst($user->status) . ' employment'])
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card__header"><h2 class="card__title">Active QR Code</h2></div>
            <div class="card__body aa-qr-focus">
                @if($qr && $qrImage)
                    <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="QR code for {{ $user->displayName() }}" class="aa-qr-focus__image">
                    <div class="aa-qr-focus__code">QR Code: {{ $qr->code }}</div>
                    @include('partials.attendance-status-badge', ['status' => 'active'])
                    <div class="aa-qr-actions">
                        <a href="{{ route('attendance.qr.download', $user) }}" class="btn btn--primary">Download QR</a>
                        <a href="{{ route('attendance.qr.print', $user) }}" class="btn btn--secondary" target="_blank" rel="noopener">Print QR</a>
                    </div>
                @else
                    <div class="aa-empty" style="padding:1.5rem;">
                        <h3 class="aa-empty__title">No active QR code</h3>
                        <p class="aa-empty__text">No active QR code exists for this employee.</p>
                        <form method="post" action="{{ route('attendance.qr.generate', $user) }}" data-aa-submit style="margin-top:1rem;">
                            @csrf
                            <button class="btn btn--primary" type="submit">
                                <span class="aa-btn-text">Generate QR Code</span>
                                <span class="aa-btn-spinner" aria-hidden="true"></span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @if($qr && $qrImage)
                <div class="card__body aa-danger-zone">
                    <h3 class="aa-danger-zone__title">QR Security Actions</h3>
                    <p class="aa-danger-zone__desc">These actions affect attendance scanning for this employee.</p>
                    <div class="aa-danger-zone__actions">
                        <form method="post" action="{{ route('attendance.qr.regenerate', $user) }}" data-aa-confirm="Regenerating will disable the old QR code. Continue?">
                            @csrf
                            <button class="btn btn--ghost" type="submit">Regenerate</button>
                        </form>
                        <form method="post" action="{{ route('attendance.qr.disable', $user) }}" data-aa-confirm="Disabling this QR will prevent it from being used for attendance scanning. Continue?">
                            @csrf
                            <button class="btn btn--danger" type="submit">Disable</button>
                        </form>
                    </div>
                    <p class="aa-form-hint" style="margin-top:.75rem;margin-bottom:0;">Regenerating will disable the old QR code. Disabling prevents attendance scanning.</p>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card__header"><h2 class="card__title">QR History</h2></div>
            <div class="card__body">
                @if($user->qrCodes->isEmpty())
                    <div class="aa-empty" style="padding:1.5rem;">
                        <p class="aa-empty__text">No QR history available.</p>
                    </div>
                @else
                    <div class="aa-table-wrap aa-table-desktop">
                        <table class="aa-table">
                            <thead>
                            <tr>
                                <th scope="col">QR Code</th>
                                <th scope="col">Status</th>
                                <th scope="col">Generated</th>
                                <th scope="col">Disabled</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($user->qrCodes as $code)
                                <tr>
                                    <td><code>{{ $code->code }}</code></td>
                                    <td>
                                        @include('partials.attendance-status-badge', [
                                            'status' => $code->isActive() ? 'active' : 'disabled',
                                        ])
                                    </td>
                                    <td>{{ ph_datetime($code->generated_at) ?? '—' }}</td>
                                    <td>{{ ph_datetime($code->disabled_at) ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="aa-mobile-cards">
                        @foreach($user->qrCodes as $code)
                            <article class="aa-card-row">
                                <div class="aa-card-row__head">
                                    <code>{{ $code->code }}</code>
                                    @include('partials.attendance-status-badge', [
                                        'status' => $code->isActive() ? 'active' : 'disabled',
                                    ])
                                </div>
                                <div class="aa-card-row__grid">
                                    <div><span class="aa-card-row__label">Generated</span> {{ ph_datetime($code->generated_at) ?? '—' }}</div>
                                    <div><span class="aa-card-row__label">Disabled</span> {{ ph_datetime($code->disabled_at) ?? '—' }}</div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
