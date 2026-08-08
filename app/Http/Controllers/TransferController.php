<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function form(InventoryItem $item): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        $locations = InventoryLocation::query()->where('is_active', true)->orderBy('name')->get();
        $departments = Department::query()->where('is_active', true)->orderBy('name')->get();
        $users = User::query()->where('status', 'active')->orderBy('name')->get();

        return view('transfer.create', compact('item', 'locations', 'departments', 'users'));
    }

    public function store(TransferRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validated();

        $toLocation = isset($data['to_location_id'])
            ? InventoryLocation::query()->find($data['to_location_id'])
            : null;

        $this->inventoryService->transfer($item, array_merge($data, [
            'to_location_label' => $toLocation?->name,
        ]), $request->user());

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Transfer completed successfully.');
    }
}
