@extends('layouts.app')
@section('title', 'Purchases')
@section('content')
<div class="page-header">
    <div>
        <h1>Purchases / Receiving</h1>
        <p class="page-header__meta">Stock increases only when a purchase is marked Received</p>
    </div>
    @if(auth()->user()->canModifyInventory())
        <a href="{{ route('purchases.create') }}" class="btn btn--primary">New purchase</a>
    @endif
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="PO / invoice / number">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['pending','ordered','received','cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        @if($purchases->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No purchases yet</p></div>
        @else
            <table class="data-table">
                <thead>
                <tr>
                    <th>Purchase #</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>PO / Invoice</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($purchases as $row)
                    <tr>
                        <td><a href="{{ route('purchases.show', $row) }}">{{ $row->purchase_number }}</a></td>
                        <td>{{ $row->purchase_date?->format('Y-m-d') }}</td>
                        <td>{{ $row->supplier?->name ?? '—' }}</td>
                        <td>{{ $row->purchase_order_number ?? '—' }} / {{ $row->invoice_number ?? '—' }}</td>
                        <td>{{ money($row->total_cost) }}</td>
                        <td><span class="badge badge--{{ $row->status === 'received' ? 'available' : ($row->status === 'cancelled' ? 'archived' : 'warn') }}">{{ ucfirst($row->status) }}</span></td>
                        <td><a href="{{ route('purchases.show', $row) }}" class="btn btn--ghost btn--sm">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @include('partials.pagination', ['paginator' => $purchases->withQueryString()])
        @endif
    </div>
</div>
@endsection
