<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Requests\StockReturnRequest;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Services\InventoryService;
use App\Support\InventoryTransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function stockInForm(InventoryItem $item): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();

        return view('stock.in', compact('item', 'suppliers'));
    }

    public function stockIn(StockInRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validated();
        $quantity = (float) $data['quantity'];

        $this->inventoryService->stockIn($item, $quantity, $request->user(), [
            'transaction_date' => $data['transaction_date'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Stock in recorded. Quantity increased automatically.');
    }

    public function stockOutForm(InventoryItem $item): View|RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        if ($item->isAsset()) {
            return redirect()
                ->route('borrow.create', $item)
                ->with('info', 'Asset items are borrowed, not issued. Use Borrow instead.');
        }

        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();
        $mode = request('mode', 'issue');

        return view('stock.out', compact('item', 'departments', 'mode'));
    }

    public function stockOut(StockOutRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validated();
        $quantity = (float) $data['quantity'];
        $type = $data['transaction_type'] ?? InventoryTransactionType::ISSUE;

        $context = [
            'type' => $type,
            'transaction_date' => $data['transaction_date'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'recipient' => $data['recipient'] ?? null,
            'to_person' => $data['recipient'] ?? null,
        ];

        if ($type === InventoryTransactionType::CONSUMPTION) {
            $this->inventoryService->consume($item, $quantity, $request->user(), $context);
            $message = 'Consumption recorded. Quantity deducted automatically.';
        } else {
            $this->inventoryService->issue($item, $quantity, $request->user(), $context);
            $message = 'Issue recorded. Quantity deducted automatically.';
        }

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', $message);
    }

    public function returnForm(InventoryItem $item): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();

        return view('stock.return', compact('item', 'departments'));
    }

    public function returnStock(StockReturnRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validated();

        $this->inventoryService->returnStock($item, (float) $data['quantity'], $request->user(), [
            'transaction_date' => $data['transaction_date'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'from_person' => $data['recipient'] ?? null,
        ]);

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Return recorded. Quantity increased automatically.');
    }

    public function adjustForm(InventoryItem $item): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('stock.adjust', compact('item'));
    }

    public function adjust(StockAdjustmentRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validated();

        $this->inventoryService->adjust($item, (float) $data['new_quantity'], $request->user(), [
            'transaction_date' => $data['transaction_date'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ]);

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Stock adjustment recorded. Quantity updated via transaction ledger.');
    }
}
