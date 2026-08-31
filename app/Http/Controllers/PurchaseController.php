<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseStoreRequest;
use App\Http\Requests\PurchaseUpdateRequest;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(protected PurchaseService $purchaseService) {}

    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'receiver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('purchase_number', 'like', $term)
                        ->orWhere('purchase_order_number', 'like', $term)
                        ->orWhere('invoice_number', 'like', $term);
                });
            })
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(20);

        return view('purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('purchases.create', $this->formData());
    }

    public function store(PurchaseStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['items'] ?? [];
        unset($data['items']);

        $purchase = $this->purchaseService->create($data, $lines, $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase created. Inventory will increase only after it is marked Received.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['items.item', 'supplier', 'receiver', 'creator']);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        if ($purchase->isReceived() || $purchase->isCancelled()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'Received or cancelled purchases cannot be edited.');
        }

        $purchase->load('items');

        return view('purchases.edit', array_merge($this->formData(), compact('purchase')));
    }

    public function update(PurchaseUpdateRequest $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validated();
        $lines = $data['items'] ?? null;
        unset($data['items']);

        $this->purchaseService->update($purchase, $data, $lines, $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase updated.');
    }

    public function markOrdered(Purchase $purchase, Request $request): RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);
        $this->purchaseService->markOrdered($purchase, $request->user());

        return back()->with('success', 'Purchase marked as Ordered.');
    }

    public function receiveForm(Purchase $purchase): View|RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        if (! $purchase->canReceive()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'This purchase cannot be received.');
        }

        $purchase->load(['items.item', 'supplier']);

        return view('purchases.receive', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $data = $request->validate([
            'received_date' => ['nullable', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['numeric', 'gt:0'],
        ]);

        $this->purchaseService->receive($purchase, $data, $request->user());

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase received. Inventory quantities have been increased.');
    }

    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $data = $request->validate([
            'remarks' => ['nullable', 'string'],
        ]);

        $this->purchaseService->cancel($purchase, $request->user(), $data['remarks'] ?? null);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Purchase cancelled. No inventory changes were made.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $inventoryItems = InventoryItem::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'item_code', 'name', 'unit', 'unit_cost', 'quantity', 'inventory_type']);

        return [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'inventoryItems' => $inventoryItems,
            'itemOptions' => $inventoryItems->map(fn ($i) => [
                'id' => $i->id,
                'code' => $i->item_code,
                'name' => $i->name,
                'stock' => $i->quantity,
                'unit' => $i->unit,
                'cost' => (float) $i->unit_cost,
            ])->values(),
            'nextNumber' => $this->purchaseService->nextPurchaseNumber(),
        ];
    }
}
