@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="page-header__meta">Inventory health driven by live transaction history</p>
    </div>
    @if(auth()->user()->canModifyInventory())
        <div class="btn-group">
            <a href="{{ route('purchases.create') }}" class="btn btn--primary">New purchase</a>
            <a href="{{ route('pos.terminal') }}" class="btn btn--secondary">POS</a>
            <a href="{{ route('inventory.create') }}" class="btn btn--secondary">Add item</a>
            <a href="{{ route('qr.scan') }}" class="btn btn--secondary">Scan QR</a>
        </div>
    @endif
</div>

<div class="grid grid--stats mb-2">
    <div class="stat-card stat-card--accent">
        <div class="stat-card__label">Total items</div>
        <div class="stat-card__value">{{ number_format($stats['total_items']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Total available stock</div>
        <div class="stat-card__value">{{ number_format($stats['total_available_stock'], 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Consumable items</div>
        <div class="stat-card__value">{{ number_format($stats['consumables']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Total assets</div>
        <div class="stat-card__value">{{ number_format($stats['assets']) }}</div>
    </div>
    <div class="stat-card stat-card--warn">
        <div class="stat-card__label">Low stock items</div>
        <div class="stat-card__value">{{ number_format($stats['low_stock']) }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__label">Out of stock items</div>
        <div class="stat-card__value">{{ number_format($stats['out_of_stock']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Items issued today</div>
        <div class="stat-card__value">{{ number_format($stats['issued_today'], 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Items purchased today</div>
        <div class="stat-card__value">{{ number_format($stats['purchased_today'], 0) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Units sold today</div>
        <div class="stat-card__value">{{ number_format($stats['sold_today'] ?? 0, 0) }}</div>
    </div>
    <div class="stat-card stat-card--ok">
        <div class="stat-card__label">Sales today</div>
        <div class="stat-card__value" style="font-size:1.15rem;">{{ money($stats['sales_today'] ?? 0) }}</div>
    </div>
    <div class="stat-card stat-card--accent">
        <div class="stat-card__label">Total purchase cost</div>
        <div class="stat-card__value" style="font-size:1.15rem;">{{ money($stats['total_purchase_cost']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Total sales revenue</div>
        <div class="stat-card__value" style="font-size:1.15rem;">{{ money($stats['total_sales_revenue'] ?? 0) }}</div>
    </div>
    <div class="stat-card stat-card--ok">
        <div class="stat-card__label">Inventory value</div>
        <div class="stat-card__value" style="font-size:1.15rem;">{{ money($stats['total_value']) }}</div>
    </div>
</div>

@if($stats['low_stock'] > 0)
    <div class="card mb-2" style="border-color:#b45309;">
        <div class="card__header"><h2 class="card__title">⚠️ Low Stock — {{ $stats['low_stock'] }} item(s)</h2></div>
        <div class="card__body table-wrap">
            <table class="data-table">
                <thead><tr><th>Item</th><th>Available</th><th>Reorder level</th><th></th></tr></thead>
                <tbody>
                @forelse($lowStockItems as $row)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.show', $row) }}">{{ $row->name }}</a>
                            <div class="text-muted" style="font-size:0.8rem;">{{ $row->part_number }}</div>
                        </td>
                        <td>{{ $row->quantity }} {{ $row->unit }}</td>
                        <td>{{ $row->reorder_level }}</td>
                        <td><a href="{{ route('purchases.create') }}" class="btn btn--ghost btn--sm">Reorder</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No low-stock rows to list.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card mb-2">
    <div class="card__header"><h2 class="card__title">Stock movement summary (YTD)</h2></div>
    <div class="card__body table-wrap">
        @if(empty($stockMovement))
            <div class="empty-state"><p class="text-muted">No items available.</p></div>
        @else
            <table class="data-table">
                <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Beginning</th>
                    <th class="text-right">Purchased</th>
                    <th class="text-right">Issued</th>
                    <th class="text-right">Adjusted</th>
                    <th class="text-right">Current</th>
                </tr>
                </thead>
                <tbody>
                @foreach($stockMovement as $row)
                    <tr>
                        <td>
                            <a href="{{ route('inventory.show', $row['item']) }}">{{ $row['item']->name }}</a>
                            <div class="text-muted" style="font-size:0.8rem;">{{ $row['item']->part_number }}</div>
                        </td>
                        <td class="text-right">{{ number_format($row['beginning'], 2) }}</td>
                        <td class="text-right">+{{ number_format($row['purchased'], 2) }}</td>
                        <td class="text-right">-{{ number_format($row['issued'], 2) }}</td>
                        <td class="text-right">{{ $row['adjusted'] >= 0 ? '+' : '' }}{{ number_format($row['adjusted'], 2) }}</td>
                        <td class="text-right"><strong>{{ number_format($row['current'], 2) }}</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="grid grid--2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Items by category</h2></div>
        <div class="card__body"><div class="chart-wrap"><canvas id="chart-category"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Items by location</h2></div>
        <div class="card__body"><div class="chart-wrap"><canvas id="chart-location"></canvas></div></div>
    </div>
</div>

<div class="grid grid--2 mb-2">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Condition breakdown</h2></div>
        <div class="card__body"><div class="chart-wrap"><canvas id="chart-condition"></canvas></div></div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Stock movement ({{ now('Asia/Manila')->year }})</h2></div>
        <div class="card__body"><div class="chart-wrap"><canvas id="chart-stock"></canvas></div></div>
    </div>
</div>

<div class="grid grid--3">
    <div class="card">
        <div class="card__header"><h2 class="card__title">Recent items</h2></div>
        <div class="card__body table-wrap">
            @if($recentItems->isEmpty())
                <div class="empty-state"><p class="empty-state__title">No items yet</p></div>
            @else
                <table class="data-table">
                    <thead><tr><th>Part Number</th><th>Name</th><th>Qty</th></tr></thead>
                    <tbody>
                    @foreach($recentItems as $row)
                        <tr>
                            <td><a href="{{ route('inventory.show', $row) }}">{{ $row->part_number }}</a></td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->quantity }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Recent transactions</h2></div>
        <div class="card__body table-wrap">
            @if($recentTransactions->isEmpty())
                <div class="empty-state"><p class="empty-state__title">No transactions</p></div>
            @else
                <table class="data-table">
                    <thead><tr><th>Type</th><th>Item</th><th>Qty</th></tr></thead>
                    <tbody>
                    @foreach($recentTransactions as $tx)
                        <tr>
                            <td>{{ \App\Support\InventoryTransactionType::label($tx->type) }}</td>
                            <td>@if($tx->item)<a href="{{ route('inventory.show', $tx->item) }}">{{ $tx->item->part_number }}</a>@else — @endif</td>
                            <td>{{ $tx->quantity }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card__header"><h2 class="card__title">Recent scans</h2></div>
        <div class="card__body table-wrap">
            @if($recentScans->isEmpty())
                <div class="empty-state"><p class="empty-state__title">No scans logged</p></div>
            @else
                <table class="data-table">
                    <thead><tr><th>Result</th><th>Item</th><th>When</th></tr></thead>
                    <tbody>
                    @foreach($recentScans as $scan)
                        <tr>
                            <td><span class="badge {{ $scan->success ? 'badge--available' : 'badge--out' }}">{{ $scan->success ? 'OK' : 'Fail' }}</span></td>
                            <td>{{ $scan->item?->part_number ?? '—' }}</td>
                            <td class="text-muted">{{ ph_datetime($scan->created_at) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const chartData = @json($chartData);
    const colors = ['#0f766e', '#115e59', '#14b8a6', '#0d9488', '#134e4a', '#2dd4bf', '#5eead4', '#64748b'];
    const monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function doughnut(id, rows) {
        const el = document.getElementById(id);
        if (!el || !rows.length) return;
        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: rows.map(r => r.label),
                datasets: [{ data: rows.map(r => r.value), backgroundColor: colors }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    doughnut('chart-category', chartData.by_category || []);
    doughnut('chart-location', chartData.by_location || []);
    doughnut('chart-condition', chartData.by_condition || []);

    const stockRows = chartData.monthly_stock || [];
    const stockIn = monthLabels.map((_, i) => {
        const m = i + 1;
        const row = stockRows.find(r => r.month === m && r.type === 'stock_in');
        return row ? row.total_qty : 0;
    });
    const stockOut = monthLabels.map((_, i) => {
        const m = i + 1;
        const row = stockRows.find(r => r.month === m && r.type === 'stock_out');
        return row ? row.total_qty : 0;
    });

    const stockEl = document.getElementById('chart-stock');
    if (stockEl) {
        new Chart(stockEl, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    { label: 'Purchased / Stock in', data: stockIn, backgroundColor: '#15803d' },
                    { label: 'Issued / Consumed', data: stockOut, backgroundColor: '#b45309' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }
})();
</script>
@endpush
