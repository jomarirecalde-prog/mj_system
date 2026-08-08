@extends('layouts.app')
@section('title', 'Sale history')
@section('content')
<div class="page-header">
    <div>
        <h1>Sale history</h1>
        <p class="page-header__meta">Completed and voided POS transactions</p>
    </div>
    <a href="{{ route('pos.terminal') }}" class="btn btn--primary">Open POS</a>
</div>

<div class="card mb-2">
    <div class="card__body">
        <form method="get" class="filters">
            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Sale # / customer">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['completed','voided'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Payment</label>
                <select name="payment_method" class="form-select">
                    <option value="">All</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ \App\Models\Sale::paymentLabel($method) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="form-group">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <button type="submit" class="btn btn--secondary">Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body table-wrap">
        @if($sales->isEmpty())
            <div class="empty-state"><p class="empty-state__title">No sales yet</p></div>
        @else
            <table class="data-table">
                <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th>Cashier</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($sales as $row)
                    <tr>
                        <td><a href="{{ route('pos.show', $row) }}">{{ $row->sale_number }}</a></td>
                        <td>{{ $row->sale_date?->format('Y-m-d') }}</td>
                        <td>{{ $row->customer_name ?: 'Walk-in' }}</td>
                        <td>{{ \App\Models\Sale::paymentLabel($row->payment_method) }}</td>
                        <td>{{ money($row->total_amount) }}</td>
                        <td>{{ $row->cashier?->displayName() ?? '—' }}</td>
                        <td>
                            <span class="badge badge--{{ $row->status === 'completed' ? 'available' : 'archived' }}">
                                {{ ucfirst($row->status) }}
                            </span>
                        </td>
                        <td><a href="{{ route('pos.show', $row) }}" class="btn btn--ghost btn--sm">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @include('partials.pagination', ['paginator' => $sales->withQueryString()])
        @endif
    </div>
</div>
@endsection
