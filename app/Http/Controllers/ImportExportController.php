<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
        $this->middleware(['auth', 'active', 'role:admin']);
    }

    public function importForm(): View
    {
        return view('import.index');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = [];

        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle !== false) {
                $header = fgetcsv($handle);
                while (($line = fgetcsv($handle)) !== false) {
                    if ($header !== false) {
                        $rows[] = array_combine($header, $line);
                    }
                }
                fclose($handle);
            }
        } else {
            return back()->with('error', 'Excel import preview is not implemented yet. Please upload a CSV file.');
        }

        Session::put('import_preview_rows', $rows);

        return view('import.preview', compact('rows'));
    }

    public function confirm(Request $request): RedirectResponse
    {
        /** @var list<array<string, mixed>>|null $rows */
        $rows = Session::get('import_preview_rows');

        if ($rows === null || $rows === []) {
            return redirect()->route('import.index')->with('error', 'No import data found. Upload a file first.');
        }

        $created = 0;

        foreach ($rows as $row) {
            if (empty($row['item_code'] ?? null) || empty($row['name'] ?? null)) {
                continue;
            }

            $this->inventoryService->createItem([
                'item_code' => $row['item_code'],
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'inventory_type' => $row['inventory_type'] ?? InventoryItem::TYPE_CONSUMABLE,
                'quantity' => $row['quantity'] ?? 0,
                'unit' => $row['unit'] ?? 'pcs',
                'unit_cost' => $row['unit_cost'] ?? 0,
                'minimum_stock' => $row['minimum_stock'] ?? 0,
                'reorder_level' => $row['reorder_level'] ?? 0,
                'condition' => $row['condition'] ?? 'New',
                'status' => $row['status'] ?? 'Available',
            ], $request->user());

            $created++;
        }

        Session::forget('import_preview_rows');

        return redirect()
            ->route('inventory.index')
            ->with('success', sprintf('%d item(s) imported successfully.', $created));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $items = InventoryItem::query()
            ->active()
            ->with(['category', 'location', 'department'])
            ->orderBy('item_code')
            ->get();

        $filename = 'inventory_export_'.now('Asia/Manila')->format('Y-m-d_His').'.xlsx';

        return Excel::download(new InventoryExport($items), $filename);
    }
}
