<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosCheckoutRequest;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(protected PosService $posService) {}

    public function terminal(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('pos.terminal', [
            'nextNumber' => $this->posService->nextSaleNumber(),
        ]);
    }

    public function searchItems(Request $request): JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $term = trim((string) $request->string('q'));

        $items = InventoryItem::query()
            ->active()
            ->where('inventory_type', InventoryItem::TYPE_CONSUMABLE)
            ->when($term !== '', fn ($q) => $q->search($term))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'item_code', 'qr_code', 'name', 'unit', 'quantity', 'unit_cost', 'selling_price', 'reorder_level']);

        return $this->jsonSuccess([
            'items' => $items->map(fn (InventoryItem $item) => $this->posItemPayload($item)),
        ]);
    }

    /**
     * Resolve a camera / wedge QR payload and return a sellable POS item.
     */
    public function scanItem(Request $request): JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $data = $request->validate([
            'qr_payload' => ['required', 'string', 'max:500'],
        ]);

        $payload = $this->normalizeQrPayload($data['qr_payload']);

        if ($payload === '') {
            return $this->jsonError('Invalid QR code.', 422, ['code' => 'invalid']);
        }

        $item = InventoryItem::query()
            ->where(function ($q) use ($payload) {
                $q->where('qr_code', $payload)
                    ->orWhere('item_code', $payload);
            })
            ->first();

        if ($item === null) {
            return $this->jsonError('No inventory item matches this QR code.', 404, ['code' => 'not_found']);
        }

        if ($item->isArchived()) {
            return $this->jsonError('This item is archived and cannot be sold.', 410, ['code' => 'archived']);
        }

        if (! $item->isConsumable()) {
            return $this->jsonError(
                'Asset items cannot be sold through POS. Use Borrow / Return instead.',
                422,
                ['code' => 'asset'],
            );
        }

        if ($item->isOutOfStock()) {
            return $this->jsonError(
                sprintf('Insufficient Inventory. Only %s units are currently available.', rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') ?: '0'),
                422,
                ['code' => 'out_of_stock', 'item' => $this->posItemPayload($item)],
            );
        }

        return $this->jsonSuccess([
            'item' => $this->posItemPayload($item),
            'message' => 'Item scanned.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function posItemPayload(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'qr_code' => $item->qr_code,
            'name' => $item->name,
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
            'selling_price' => $item->effectiveSellingPrice(),
            'is_low_stock' => $item->isLowStock(),
            'is_out_of_stock' => $item->isOutOfStock(),
            'inventory_type' => $item->inventory_type,
        ];
    }

    protected function normalizeQrPayload(string $payload): string
    {
        $payload = trim($payload);

        if ($payload === '') {
            return '';
        }

        // Accept full public profile URLs: /i/{qr_code}
        if (preg_match('~/i/([^/?#]+)~i', $payload, $matches)) {
            return rawurldecode($matches[1]);
        }

        // Accept inventory show URLs if somehow encoded.
        if (preg_match('~/inventory/(\d+)~i', $payload, $matches)) {
            $item = InventoryItem::query()->find((int) $matches[1]);

            return $item?->qr_code ?? $payload;
        }

        return $payload;
    }

    public function checkout(PosCheckoutRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $lines = $data['items'] ?? [];
        unset($data['items']);

        $sale = $this->posService->checkout($data, $lines, $request->user());

        if ($request->expectsJson()) {
            return $this->jsonSuccess([
                'sale' => $sale,
                'redirect' => route('pos.show', $sale),
                'message' => 'Sale completed. Stock has been deducted.',
            ]);
        }

        return redirect()
            ->route('pos.show', $sale)
            ->with('success', 'Sale completed. Stock has been deducted.');
    }

    public function index(Request $request): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $sales = Sale::query()
            ->with(['cashier'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->string('payment_method')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('sale_number', 'like', $term)
                        ->orWhere('customer_name', 'like', $term);
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('sale_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('sale_date', '<=', $request->date('date_to')))
            ->latest('sale_date')
            ->latest('id')
            ->paginate(20);

        return view('pos.index', [
            'sales' => $sales,
            'paymentMethods' => Sale::paymentMethods(),
        ]);
    }

    public function show(Sale $sale): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $sale->load(['items.item', 'cashier', 'voider']);

        return view('pos.show', compact('sale'));
    }

    public function receipt(Sale $sale): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $sale->load(['items.item', 'cashier']);

        return view('pos.receipt', compact('sale'));
    }

    public function void(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $data = $request->validate([
            'void_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->posService->void($sale, $request->user(), $data['void_reason'] ?? null);

        return redirect()
            ->route('pos.show', $sale)
            ->with('success', 'Sale voided. Stock quantities have been restored.');
    }
}
