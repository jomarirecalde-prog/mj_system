<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Http\Requests\InventoryStoreRequest;
use App\Http\Requests\InventoryUpdateRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\Department;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request): View|JsonResponse
    {
        $query = $this->filteredQuery($request);

        if ($request->ajax() || $request->expectsJson()) {
            $items = $query->paginate((int) $request->input('per_page', 15));

            return response()->json($items);
        }

        $categories = InventoryCategory::query()->where('is_active', true)->orderBy('name')->get();
        $locations = InventoryLocation::query()->where('is_active', true)->orderBy('name')->get();

        return view('inventory.index', compact('categories', 'locations'));
    }

    public function create(): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('inventory.create', $this->formLookups());
    }

    public function store(InventoryStoreRequest $request): RedirectResponse
    {
        $item = $this->inventoryService->createItem($request->validated(), $request->user());

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Inventory item created successfully.');
    }

    public function show(InventoryItem $item): View
    {
        $item->load([
            'category',
            'location',
            'department',
            'supplier',
            'assignee',
            'creator',
            'history' => fn ($q) => $q->with('user')->latest('occurred_at')->limit(20),
            'borrowings' => fn ($q) => $q->latest()->limit(10),
            'transactions' => fn ($q) => $q->latest()->limit(10),
            'transfers' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('inventory.show', compact('item'));
    }

    public function edit(InventoryItem $item): View
    {
        $this->authorizeRoles(['admin', 'staff']);

        return view('inventory.edit', array_merge(['item' => $item], $this->formLookups()));
    }

    public function update(InventoryUpdateRequest $request, InventoryItem $item): RedirectResponse
    {
        $this->inventoryService->updateItem($item, $request->validated(), $request->user());

        return redirect()
            ->route('inventory.show', $item)
            ->with('success', 'Inventory item updated successfully.');
    }

    public function archive(Request $request, InventoryItem $item): RedirectResponse|JsonResponse
    {
        $this->authorizeRoles(['admin', 'staff']);

        $this->inventoryService->archiveItem($item, $request->user(), $request->input('remarks'));

        if ($request->expectsJson()) {
            return $this->jsonSuccess(['message' => 'Item archived.']);
        }

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Item archived successfully.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $items = $this->filteredQuery($request)->get();

        $filename = 'inventory_'.now('Asia/Manila')->format('Y-m-d_His').'.xlsx';

        return Excel::download(new InventoryExport($items), $filename);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formLookups(): array
    {
        return [
            'categories' => InventoryCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => InventoryLocation::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'users' => User::query()->where('status', 'active')->orderBy('name')->get(),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<InventoryItem>
     */
    protected function filteredQuery(Request $request)
    {
        $query = InventoryItem::query()
            ->with(['category', 'location', 'department'])
            ->search($request->input('search'));

        if (! $request->boolean('include_archived')) {
            $query->active();
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->input('condition'));
        }

        if ($request->filled('inventory_type')) {
            $query->where('inventory_type', $request->input('inventory_type'));
        }

        if ($request->boolean('low_stock')) {
            $query->where('inventory_type', InventoryItem::TYPE_CONSUMABLE)
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->where('reorder_level', '>', 0);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'item_code', 'quantity', 'status', 'created_at', 'total_value'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        return $query->orderBy($sort, $direction);
    }
}
