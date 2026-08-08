<?php

namespace App\Http\Controllers;

use App\Http\Requests\BorrowRequest;
use App\Http\Requests\ReturnRequest;
use App\Models\BorrowingRecord;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BorrowController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function borrowForm(InventoryItem $item): View|RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        if ($item->isConsumable()) {
            return redirect()
                ->route('stock.out.form', $item)
                ->with('info', 'Consumable items are issued or consumed, not borrowed.');
        }

        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();

        return view('borrow.create', compact('item', 'departments'));
    }

    public function borrow(BorrowRequest $request, InventoryItem $item): RedirectResponse
    {
        $record = $this->inventoryService->borrow($item, $request->validated(), $request->user());

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', sprintf('Item borrowed by %s.', $record->borrower_name));
    }

    public function returnForm(BorrowingRecord $record): View|RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $record->load('item');

        if ($record->status === 'returned') {
            return redirect()
                ->route('inventory.show', $record->item)
                ->with('info', 'This borrowing record has already been returned.');
        }

        return view('borrow.return', compact('record'));
    }

    public function returnItem(ReturnRequest $request, BorrowingRecord $record): RedirectResponse
    {
        $this->inventoryService->returnItem($record, $request->validated(), $request->user());

        $record->load('item');

        return redirect()
            ->route('inventory.show', $record->item)
            ->with('success', 'Item returned successfully.');
    }
}
