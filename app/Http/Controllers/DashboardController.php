<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\QrScanLog;
use App\Models\Sale;
use App\Support\InventoryTransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = $this->buildStats();
        $stockMovement = $this->buildStockMovementSummary();

        $recentItems = InventoryItem::query()
            ->active()
            ->with(['category', 'location'])
            ->latest()
            ->limit(8)
            ->get();

        $recentTransactions = InventoryTransaction::query()
            ->with(['item'])
            ->latest()
            ->limit(10)
            ->get();

        $recentScans = QrScanLog::query()
            ->with(['item', 'scanner'])
            ->latest()
            ->limit(10)
            ->get();

        $lowStockItems = InventoryItem::query()
            ->active()
            ->where('inventory_type', InventoryItem::TYPE_CONSUMABLE)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        $chartData = $this->buildChartData();

        return view('dashboard.index', compact(
            'stats',
            'stockMovement',
            'recentItems',
            'recentTransactions',
            'recentScans',
            'lowStockItems',
            'chartData',
        ));
    }

    public function charts(): JsonResponse
    {
        return $this->jsonSuccess(['charts' => $this->buildChartData()]);
    }

    /**
     * @return array<string, int|float>
     */
    protected function buildStats(): array
    {
        $base = InventoryItem::query()->active();
        $today = now('Asia/Manila')->toDateString();

        $totalItems = (clone $base)->count();
        $totalAvailableStock = (float) (clone $base)->sum('quantity');
        $consumables = (clone $base)->where('inventory_type', InventoryItem::TYPE_CONSUMABLE)->count();
        $assets = (clone $base)->where('inventory_type', InventoryItem::TYPE_ASSET)->count();
        $available = (clone $base)->where('status', 'Available')->count();
        $borrowed = (clone $base)->where('status', 'Borrowed')->count();
        $outOfStock = (clone $base)
            ->where(function ($q) {
                $q->where('status', 'Out of Stock')->orWhere('quantity', '<=', 0);
            })
            ->count();

        $lowStock = (clone $base)
            ->where('inventory_type', InventoryItem::TYPE_CONSUMABLE)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->count();

        $issuedToday = (float) InventoryTransaction::query()
            ->whereDate('transaction_date', $today)
            ->whereIn('type', [
                InventoryTransactionType::ISSUE,
                InventoryTransactionType::CONSUMPTION,
                InventoryTransactionType::STOCK_OUT,
                InventoryTransactionType::SALE,
            ])
            ->sum('quantity');

        $purchasedToday = (float) InventoryTransaction::query()
            ->whereDate('transaction_date', $today)
            ->whereIn('type', [
                InventoryTransactionType::PURCHASE,
                InventoryTransactionType::STOCK_IN,
            ])
            ->sum('quantity');

        $soldToday = (float) InventoryTransaction::query()
            ->whereDate('transaction_date', $today)
            ->where('type', InventoryTransactionType::SALE)
            ->sum('quantity');

        $salesToday = (float) Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('sale_date', $today)
            ->sum('total_amount');

        $totalPurchaseCost = (float) Purchase::query()
            ->where('status', Purchase::STATUS_RECEIVED)
            ->sum('total_cost');

        $totalSalesRevenue = (float) Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->sum('total_amount');

        $totalValue = (float) (clone $base)->sum('total_value');

        return [
            'total_items' => $totalItems,
            'total_available_stock' => $totalAvailableStock,
            'consumables' => $consumables,
            'assets' => $assets,
            'available' => $available,
            'borrowed' => $borrowed,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'issued_today' => $issuedToday,
            'purchased_today' => $purchasedToday,
            'sold_today' => $soldToday,
            'sales_today' => $salesToday,
            'total_purchase_cost' => $totalPurchaseCost,
            'total_sales_revenue' => $totalSalesRevenue,
            'total_value' => $totalValue,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildStockMovementSummary(): array
    {
        $yearStart = now('Asia/Manila')->startOfYear()->toDateString();
        $today = now('Asia/Manila')->toDateString();

        $items = InventoryItem::query()
            ->active()
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'item_code', 'quantity', 'unit']);

        if ($items->isEmpty()) {
            return [];
        }

        $itemIds = $items->pluck('id');

        $transactions = InventoryTransaction::query()
            ->whereIn('inventory_item_id', $itemIds)
            ->whereBetween('transaction_date', [$yearStart, $today])
            ->get(['inventory_item_id', 'type', 'quantity', 'quantity_before', 'quantity_after']);

        $grouped = $transactions->groupBy('inventory_item_id');

        return $items->map(function (InventoryItem $item) use ($grouped) {
            $rows = $grouped->get($item->id, collect());

            $issued = (float) $rows->whereIn('type', [
                InventoryTransactionType::ISSUE,
                InventoryTransactionType::CONSUMPTION,
                InventoryTransactionType::STOCK_OUT,
                InventoryTransactionType::SALE,
                InventoryTransactionType::TRANSFER_OUT,
                InventoryTransactionType::DAMAGED,
                InventoryTransactionType::LOST,
                InventoryTransactionType::DISPOSAL,
            ])->sum('quantity');

            $purchased = (float) $rows->whereIn('type', [
                InventoryTransactionType::PURCHASE,
                InventoryTransactionType::STOCK_IN,
                InventoryTransactionType::INITIAL_STOCK,
                InventoryTransactionType::TRANSFER_IN,
                InventoryTransactionType::RETURN,
                InventoryTransactionType::SALE_RETURN,
            ])->sum('quantity');

            $adjusted = (float) $rows
                ->where('type', InventoryTransactionType::ADJUSTMENT)
                ->sum(function ($tx) {
                    return (float) $tx->quantity_after - (float) $tx->quantity_before;
                });

            $current = (float) $item->quantity;
            $beginning = $current - $purchased + $issued - $adjusted;

            return [
                'item' => $item,
                'beginning' => $beginning,
                'purchased' => $purchased,
                'issued' => $issued,
                'adjusted' => $adjusted,
                'current' => $current,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildChartData(): array
    {
        $byCategory = InventoryItem::query()
            ->active()
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->category?->name ?? 'Uncategorized',
                'value' => (int) $row->total,
            ])
            ->values();

        $byLocation = InventoryItem::query()
            ->active()
            ->select('location_id', DB::raw('count(*) as total'))
            ->groupBy('location_id')
            ->with('location:id,name')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->location?->name ?? 'Unassigned',
                'value' => (int) $row->total,
            ])
            ->values();

        $byCondition = InventoryItem::query()
            ->active()
            ->select('condition', DB::raw('count(*) as total'))
            ->groupBy('condition')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->condition,
                'value' => (int) $row->total,
            ])
            ->values();

        $year = now('Asia/Manila')->year;

        $stockTx = InventoryTransaction::query()
            ->whereYear('transaction_date', $year)
            ->whereIn('type', [
                InventoryTransactionType::STOCK_IN,
                InventoryTransactionType::PURCHASE,
                InventoryTransactionType::STOCK_OUT,
                InventoryTransactionType::ISSUE,
                InventoryTransactionType::CONSUMPTION,
                InventoryTransactionType::SALE,
                InventoryTransactionType::SALE_RETURN,
            ])
            ->get(['transaction_date', 'type', 'quantity']);

        $monthlyStock = $stockTx
            ->map(function ($row) {
                $bucket = in_array($row->type, [
                    InventoryTransactionType::STOCK_IN,
                    InventoryTransactionType::PURCHASE,
                    InventoryTransactionType::SALE_RETURN,
                ], true) ? 'stock_in' : 'stock_out';

                return [
                    'month' => (int) $row->transaction_date->format('n'),
                    'type' => $bucket,
                    'quantity' => (float) $row->quantity,
                ];
            })
            ->groupBy(fn ($row) => $row['month'].':'.$row['type'])
            ->map(fn ($group, $key) => [
                'month' => (int) explode(':', $key)[0],
                'type' => explode(':', $key)[1],
                'total_qty' => (float) collect($group)->sum('quantity'),
            ])
            ->values()
            ->sortBy('month')
            ->values();

        $borrowTx = InventoryTransaction::query()
            ->whereYear('transaction_date', $year)
            ->whereIn('type', [InventoryTransactionType::BORROW, InventoryTransactionType::RETURN])
            ->get(['transaction_date', 'type']);

        $monthlyBorrow = $borrowTx
            ->groupBy(fn ($row) => $row->transaction_date->format('n').':'.$row->type)
            ->map(fn ($group, $key) => [
                'month' => (int) explode(':', $key)[0],
                'type' => explode(':', $key)[1],
                'total_count' => $group->count(),
            ])
            ->values()
            ->sortBy('month')
            ->values();

        return [
            'by_category' => $byCategory,
            'by_location' => $byLocation,
            'by_condition' => $byCondition,
            'monthly_stock' => $monthlyStock,
            'borrowed_vs_returned' => $monthlyBorrow,
        ];
    }
}
