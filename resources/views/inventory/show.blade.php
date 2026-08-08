@extends('layouts.app')

@section('title', $item->item_code)

@section('content')
<div class="page-header">
    <div>
        <h1>{{ $item->name }}</h1>
        <p class="page-header__meta">
            {{ $item->item_code }}
            · @include('partials.status-badge', ['status' => $item->status])
        </p>
    </div>
    <div class="btn-group">
        <a href="{{ route('inventory.index') }}" class="btn btn--secondary">Back</a>
        @if(auth()->user()->canModifyInventory())
            <a href="{{ route('inventory.edit', $item) }}" class="btn btn--secondary">Edit</a>
            <a href="{{ route('stock.in.form', $item) }}" class="btn btn--secondary">Stock in</a>
            @if($item->isConsumable())
                <a href="{{ route('stock.out.form', $item) }}" class="btn btn--secondary">Issue</a>
                <a href="{{ route('stock.out.form', ['item' => $item, 'mode' => 'consumption']) }}" class="btn btn--secondary">Consume</a>
                <a href="{{ route('stock.return.form', $item) }}" class="btn btn--secondary">Return</a>
            @else
                <a href="{{ route('borrow.create', $item) }}" class="btn btn--secondary">Borrow</a>
            @endif
            <a href="{{ route('transfer.create', $item) }}" class="btn btn--secondary">Transfer</a>
            <a href="{{ route('stock.adjust.form', $item) }}" class="btn btn--secondary">Adjust</a>
            <a href="{{ route('qr.show', $item) }}" class="btn btn--primary">QR code</a>
            <form action="{{ route('inventory.archive', $item) }}" method="post" data-confirm="Archive this item?" data-confirm-title="Archive item" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn--danger">Archive</button>
            </form>
        @endif
    </div>
</div>

<div class="profile-grid">
    <div class="card">
        <div class="card__header"><h2 class="card__title">QR code</h2></div>
        <div class="card__body qr-preview">
            <div>{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->margin(1)->generate($item->qr_code) !!}</div>
            <p class="text-muted" style="font-size:0.85rem;margin-top:0.75rem;">{{ $item->qr_code }}</p>
            @if(auth()->user()->canModifyInventory())
                <div class="btn-group mt-1" style="justify-content:center;">
                    <a href="{{ route('qr.print.single', $item) }}" class="btn btn--secondary btn--sm" target="_blank">Print label</a>
                    <a href="{{ route('qr.download', $item) }}" class="btn btn--ghost btn--sm">Download</a>
                </div>
            @endif
        </div>
    </div>
    <div>
        <div class="card mb-2">
            <div class="card__header"><h2 class="card__title">Details</h2></div>
            <div class="card__body">
                <dl class="dl-grid">
                    <div class="dl-item"><dt>Inventory type</dt><dd>{{ $item->isAsset() ? 'Non-consumable / Asset' : 'Consumable' }}</dd></div>
                    <div class="dl-item"><dt>Category</dt><dd>{{ $item->category?->name ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Location</dt><dd>{{ $item->location?->name ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Department</dt><dd>{{ $item->department?->name ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Condition</dt><dd>{{ $item->condition }}</dd></div>
                    <div class="dl-item"><dt>Brand / Model</dt><dd>{{ trim(($item->brand ?? '').' '.($item->model ?? '')) ?: '—' }}</dd></div>
                    <div class="dl-item"><dt>Serial</dt><dd>{{ $item->serial_number ?? '—' }}</dd></div>
                    <div class="dl-item">
                        <dt>Available quantity</dt>
                        <dd>
                            <strong>{{ $item->quantity }} {{ $item->unit }}</strong>
                            @if($item->isLowStock())
                                <span class="badge badge--warn" style="margin-left:0.35rem;">⚠️ Low Stock</span>
                            @endif
                        </dd>
                    </div>
                    <div class="dl-item"><dt>Assigned to</dt><dd>{{ $item->assignee?->displayName() ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Supplier</dt><dd>{{ $item->supplier?->name ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Acquired</dt><dd>{{ $item->date_acquired?->format('M d, Y') ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Warranty</dt><dd>{{ $item->warranty_expiration?->format('M d, Y') ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Minimum stock</dt><dd>{{ $item->minimum_stock }}</dd></div>
                    <div class="dl-item"><dt>Reorder level</dt><dd>{{ $item->reorder_level }}</dd></div>
                </dl>
                @if($item->description)
                    <p class="mt-2"><strong>Description</strong><br>{{ $item->description }}</p>
                @endif
                @if($item->remarks)
                    <p class="mt-1 text-muted"><strong>Remarks</strong><br>{{ $item->remarks }}</p>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card__header"><h2 class="card__title">Financial</h2></div>
            <div class="card__body">
                <dl class="dl-grid">
                    <div class="dl-item"><dt>Unit cost</dt><dd>{{ money($item->unit_cost) }}</dd></div>
                    <div class="dl-item"><dt>Selling price</dt><dd>{{ money($item->effectiveSellingPrice()) }}</dd></div>
                    <div class="dl-item"><dt>Total value</dt><dd>{{ money($item->total_value) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="grid grid--2 mt-2" id="transactions">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Transaction ledger</h2></div>
        <div class="card__body table-wrap">
            @if($item->transactions->isEmpty())
                <div class="empty-state"><p class="text-muted">No transactions recorded.</p></div>
            @else
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Before → After</th><th>Remarks</th></tr></thead>
                    <tbody>
                    @foreach($item->transactions as $tx)
                        <tr>
                            <td>{{ $tx->transaction_date?->format('Y-m-d') }}</td>
                            <td>{{ \App\Support\InventoryTransactionType::label($tx->type) }}</td>
                            <td>{{ $tx->quantity }}</td>
                            <td class="text-muted">{{ $tx->quantity_before }} → {{ $tx->quantity_after }}</td>
                            <td>{{ Str::limit($tx->remarks, 40) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Borrowing history</h2></div>
        <div class="card__body table-wrap">
            @if($item->borrowings->isEmpty())
                <div class="empty-state"><p class="text-muted">No borrowing records.</p></div>
            @else
                <table class="data-table">
                    <thead><tr><th>Borrower</th><th>Status</th><th>Borrowed</th><th></th></tr></thead>
                    <tbody>
                    @foreach($item->borrowings as $br)
                        <tr>
                            <td>{{ $br->borrower_name }}</td>
                            <td>{{ ucfirst($br->status) }}</td>
                            <td>{{ $br->date_borrowed?->format('Y-m-d') }}</td>
                            <td>
                                @if($br->status === 'borrowed' && auth()->user()->canModifyInventory())
                                    <a href="{{ route('borrow.return', $br) }}" class="btn btn--sm btn--primary">Return</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<div class="card mt-2">
    <div class="card__header"><h2 class="card__title">Change history</h2></div>
    <div class="card__body table-wrap">
        @if($item->history->isEmpty())
            <div class="empty-state"><p class="text-muted">No history entries.</p></div>
        @else
            <table class="data-table">
                <thead><tr><th>When</th><th>Type</th><th>From</th><th>To</th><th>User</th></tr></thead>
                <tbody>
                @foreach($item->history as $h)
                    <tr>
                        <td>{{ ph_datetime($h->occurred_at ?? $h->created_at) }}</td>
                        <td>{{ $h->transaction_type }}</td>
                        <td>{{ Str::limit($h->from_value, 30) }}</td>
                        <td>{{ Str::limit($h->to_value, 30) }}</td>
                        <td>{{ $h->user?->displayName() ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
