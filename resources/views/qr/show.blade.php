@extends('layouts.app')

@section('title', 'QR — '.$item->part_number)

@section('content')
<div class="page-header">
    <div>
        <h1>QR code</h1>
        <p class="page-header__meta">{{ $item->labeledName() }}</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('inventory.show', $item) }}" class="btn btn--secondary">Back to item</a>
        <a href="{{ route('qr.print.single', $item) }}" class="btn btn--primary" target="_blank">Print</a>
        <a href="{{ route('qr.download', $item) }}" class="btn btn--secondary">Download</a>
    </div>
</div>

<div class="card" style="max-width:420px;margin:0 auto;">
    <div class="card__body qr-preview">
        <div>{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(240)->margin(2)->generate($item->qr_code) !!}</div>
        <p style="font-weight:600;margin-top:1rem;">{{ $item->part_number }}</p>
        <p class="text-muted">{{ $item->name }}</p>
        <p class="text-muted">Item Code: {{ $item->item_code }}</p>
        <p class="text-muted">{{ $item->qr_code }}</p>
        <p class="text-muted" style="font-size:0.85rem;">Public URL: {{ route('qr.public', $item->qr_code) }}</p>
    </div>
</div>
@endsection
