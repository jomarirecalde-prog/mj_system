<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConditionController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function update(Request $request, InventoryItem $item): RedirectResponse|JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $validated = $request->validate([
            'condition' => ['required', 'string', Rule::in(['New', 'Good', 'Fair', 'Damaged', 'For Maintenance', 'Lost', 'Disposed'])],
            'remarks' => ['nullable', 'string'],
        ]);

        $this->inventoryService->updateCondition(
            $item,
            $validated['condition'],
            $request->user(),
            $validated['remarks'] ?? null,
        );

        if ($request->expectsJson()) {
            return $this->jsonSuccess(['message' => 'Condition updated.']);
        }

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Item condition updated.');
    }
}
