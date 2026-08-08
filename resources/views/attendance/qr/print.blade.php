@extends('layouts.print')

@section('title', 'Print Employee QR')

@section('content')
<div style="text-align:center;padding:2rem;">
    <h1 style="margin-bottom:.25rem;">{{ $user->displayName() }}</h1>
    <p>{{ $user->employee_id }} · {{ $user->department }} · {{ $user->position }}</p>
    <img src="data:{{ $qrMime }};base64,{{ $qrImage }}" alt="QR" style="width:240px;height:240px;margin:1rem auto;">
    <p style="font-size:1.25rem;font-weight:700;">{{ $qr->code }}</p>
    <p class="text-muted">Attendance QR · Do not share sensitive data</p>
</div>
<script>window.print()</script>
@endsection
