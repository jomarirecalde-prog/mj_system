<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\QrScanLog;
use App\Services\QrCodeService;
use App\Support\PartNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrController extends Controller
{
    public function __construct(protected QrCodeService $qrCodeService) {}

    public function publicProfile(string $qr_code): RedirectResponse
    {
        $item = $this->findItemByScanPayload($qr_code);

        if ($item === null) {
            abort(404, 'Item not found.');
        }

        if (! auth()->check()) {
            session(['url.intended' => route('inventory.show', $item)]);

            return redirect()->route('login')->with('info', 'Please sign in to view this item.');
        }

        return redirect()->route('inventory.show', $item);
    }

    public function show(InventoryItem $item): View
    {
        return view('qr.show', compact('item'));
    }

    public function download(InventoryItem $item, Request $request): StreamedResponse
    {
        $format = $request->input('format', setting('qr_format', 'svg'));

        return $this->qrCodeService->download(
            $item->qr_code,
            $item->part_number.'.'.$format,
            $format,
        );
    }

    public function print(InventoryItem $item): View
    {
        $format = extension_loaded('imagick') ? 'png' : 'svg';
        $qrImage = base64_encode($this->qrCodeService->generateImage($item->qr_code, $format, 200));
        $qrMime = $format === 'png' ? 'image/png' : 'image/svg+xml';

        return view('qr.print', compact('item', 'qrImage', 'qrMime'));
    }

    public function batchForm(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $items = InventoryItem::query()->active()->orderBy('part_number')->get(['id', 'part_number', 'item_code', 'name', 'qr_code']);

        return view('qr.batch', compact('items'));
    }

    public function batchGenerate(Request $request): View|RedirectResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:inventory_items,id'],
            'layout' => ['required', 'in:1,2,4,8,label'],
        ]);

        $items = InventoryItem::query()
            ->whereIn('id', $request->input('item_ids'))
            ->orderBy('part_number')
            ->get();

        $layout = $request->input('layout');
        $format = extension_loaded('imagick') ? 'png' : 'svg';
        $qrMime = $format === 'png' ? 'image/png' : 'image/svg+xml';
        $payloads = $items->map(fn (InventoryItem $item) => [
            'item' => $item,
            'qr_image' => base64_encode($this->qrCodeService->generateImage($item->qr_code, $format, 180)),
            'qr_mime' => $qrMime,
        ]);

        return view('qr.print', compact('payloads', 'layout', 'qrMime'));
    }

    public function scan(): View
    {
        return view('qr.scan');
    }

    public function scanLookup(Request $request): JsonResponse
    {
        $request->validate([
            'qr_payload' => ['required', 'string', 'max:500'],
        ]);

        $payload = trim($request->input('qr_payload'));
        $user = $request->user();

        if ($payload === '') {
            $this->logScan(null, $payload, false, 'Invalid QR payload.', $user?->id, $request);

            return $this->jsonError('Invalid QR code.', 422, ['code' => 'invalid']);
        }

        $item = $this->findItemByScanPayload($payload);

        if ($item === null) {
            $this->logScan(null, $payload, false, 'Item not found.', $user?->id, $request);

            return $this->jsonError('No inventory item matches this QR code.', 404, ['code' => 'not_found']);
        }

        if ($item->isArchived()) {
            $this->logScan($item->id, $payload, false, 'Item is archived.', $user?->id, $request);

            return $this->jsonError('This item is archived.', 410, ['code' => 'archived']);
        }

        $this->logScan($item->id, $payload, true, 'Scan successful.', $user?->id, $request);

        $item->load(['category', 'location', 'department', 'assignee']);

        $lastTransaction = $item->transactions()->latest('transaction_date')->first();
        $activeBorrow = $item->borrowings()->where('status', 'borrowed')->latest('date_borrowed')->first();

        return $this->jsonSuccess([
            'item' => $item,
            'last_transaction' => $lastTransaction,
            'current_holder' => $activeBorrow?->borrower_name ?? $item->assignee?->displayName(),
            'redirect_url' => route('inventory.show', $item),
            'actions' => [
                'view' => route('inventory.show', $item),
                'history' => route('inventory.show', $item).'#transactions',
                'stock_in' => route('stock.in.form', $item),
                'issue' => route('stock.out.form', $item),
                'consume' => route('stock.out.form', ['item' => $item, 'mode' => 'consumption']),
                'return' => route('stock.return.form', $item),
                'borrow' => route('borrow.create', $item),
                'transfer' => route('transfer.create', $item),
                'adjust' => route('stock.adjust.form', $item),
            ],
            'inventory_type' => $item->inventory_type,
            'is_consumable' => $item->isConsumable(),
            'is_low_stock' => $item->isLowStock(),
        ]);
    }

    protected function findItemByScanPayload(string $payload): ?InventoryItem
    {
        $payload = trim($payload);
        $normalized = PartNumber::normalize($payload);

        return InventoryItem::query()
            ->where(function ($query) use ($payload, $normalized) {
                $query->where('qr_code', $payload)
                    ->orWhere('part_number', $normalized)
                    ->orWhere('item_code', $payload);

                if ($normalized !== '' && $normalized !== $payload) {
                    $query->orWhere('item_code', $normalized);
                }
            })
            ->first();
    }

    protected function logScan(?int $itemId, string $payload, bool $success, string $message, ?int $userId, Request $request): void
    {
        QrScanLog::query()->create([
            'inventory_item_id' => $itemId,
            'qr_payload' => $payload,
            'success' => $success,
            'message' => $message,
            'scanned_by' => $userId,
            'ip_address' => $request->ip(),
        ]);
    }
}
