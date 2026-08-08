@extends('layouts.print')

@section('title', 'Print QR labels')

@section('content')
@php
    $org = setting('organization_name', 'QR Inventory System');
    $defaultMime = $qrMime ?? 'image/svg+xml';
@endphp

<div class="no-print" style="padding:1rem;text-align:center;">
    <button type="button" onclick="window.print()" class="btn btn--primary">Print</button>
</div>

<div class="print-sheet">
@if(isset($payloads) && isset($layout))
    <div class="qr-print-grid qr-print-grid--{{ $layout }}">
        @foreach($payloads as $payload)
            @php
                $printItem = $payload['item'];
                $mime = $payload['qr_mime'] ?? $defaultMime;
            @endphp
            <div class="qr-label">
                <div class="qr-label__org">{{ $org }}</div>
                <img src="data:{{ $mime }};base64,{{ $payload['qr_image'] }}" alt="QR" width="140" height="140">
                <div class="qr-label__name">{{ \Illuminate\Support\Str::limit($printItem->name, 48) }}</div>
                <div class="qr-label__code">{{ $printItem->item_code }}</div>
                <div class="qr-label__name">S/N: {{ $printItem->serial_number ?: '—' }}</div>
                <div class="qr-label__code">{{ $printItem->qr_code }}</div>
            </div>
        @endforeach
    </div>
@elseif(isset($item) && isset($qrImage))
    <div class="qr-print-grid qr-print-grid--1">
        <div class="qr-label">
            <div class="qr-label__org">{{ $org }}</div>
            <img src="data:{{ $defaultMime }};base64,{{ $qrImage }}" alt="QR" width="180" height="180">
            <div class="qr-label__name">{{ $item->name }}</div>
            <div class="qr-label__code">{{ $item->item_code }}</div>
            <div class="qr-label__name">S/N: {{ $item->serial_number ?: '—' }}</div>
            <div class="qr-label__code">{{ $item->qr_code }}</div>
        </div>
    </div>
@endif
</div>
@endsection
