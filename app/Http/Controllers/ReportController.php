<?php

namespace App\Http\Controllers;

use App\Models\BorrowingRecord;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * @var array<string, string>
     */
    protected array $reportTitles = [
        'inventory' => 'Inventory Summary',
        'by-category' => 'Inventory by Category',
        'by-location' => 'Inventory by Location',
        'borrowed' => 'Borrowed Items',
        'low-stock' => 'Low Stock Items',
        'damaged' => 'Damaged Items',
        'lost' => 'Lost Items',
        'stock-movement' => 'Stock Movement',
        'valuation' => 'Inventory Valuation',
        'qr-inventory' => 'QR Inventory List',
    ];

    public function index(): View
    {
        return view('reports.index', ['reports' => $this->reportTitles]);
    }

    public function show(string $type, Request $request): View
    {
        $this->assertReportType($type);

        [$headers, $rows, $title] = $this->buildReport($type, $request);

        return view('reports.show', compact('type', 'headers', 'rows', 'title'));
    }

    public function export(string $type, Request $request): BinaryFileResponse|StreamedResponse|Response|View
    {
        $this->assertReportType($type);

        $format = $request->input('format', 'pdf');
        [$headers, $rows, $title] = $this->buildReport($type, $request);

        return match ($format) {
            'excel' => $this->exportExcel($title, $headers, $rows),
            'csv' => $this->exportCsv($title, $headers, $rows),
            'print' => view('reports.print', compact('type', 'headers', 'rows', 'title')),
            default => $this->exportPdf($title, $headers, $rows),
        };
    }

    protected function assertReportType(string $type): void
    {
        if (! array_key_exists($type, $this->reportTitles)) {
            abort(404, 'Unknown report type.');
        }
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function buildReport(string $type, Request $request): array
    {
        $title = $this->reportTitles[$type];

        return match ($type) {
            'inventory' => $this->inventoryReport($title),
            'by-category' => $this->byCategoryReport($title),
            'by-location' => $this->byLocationReport($title),
            'borrowed' => $this->borrowedReport($title),
            'low-stock' => $this->lowStockReport($title),
            'damaged' => $this->conditionReport($title, 'Damaged'),
            'lost' => $this->conditionReport($title, 'Lost'),
            'stock-movement' => $this->stockMovementReport($title, $request),
            'valuation' => $this->valuationReport($title),
            'qr-inventory' => $this->qrInventoryReport($title),
            default => [[], collect(), $title],
        };
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function inventoryReport(string $title): array
    {
        $headers = ['Item Code', 'Name', 'Category', 'Location', 'Qty', 'Status', 'Condition', 'Value'];

        $rows = InventoryItem::query()
            ->active()
            ->with(['category', 'location'])
            ->orderBy('item_code')
            ->get()
            ->map(fn ($item) => [
                $item->item_code,
                $item->name,
                $item->category?->name,
                $item->location?->name,
                $item->quantity,
                $item->status,
                $item->condition,
                $item->total_value,
            ]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function byCategoryReport(string $title): array
    {
        $headers = ['Category', 'Item Count', 'Total Quantity', 'Total Value'];

        $rows = InventoryItem::query()
            ->active()
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($group) {
                /** @var InventoryItem $first */
                $first = $group->first();

                return [
                    $first->category?->name ?? 'Uncategorized',
                    $group->count(),
                    $group->sum('quantity'),
                    $group->sum('total_value'),
                ];
            })
            ->values();

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function byLocationReport(string $title): array
    {
        $headers = ['Location', 'Item Count', 'Total Quantity', 'Total Value'];

        $rows = InventoryItem::query()
            ->active()
            ->with('location')
            ->get()
            ->groupBy('location_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    $first->location?->name ?? 'Unassigned',
                    $group->count(),
                    $group->sum('quantity'),
                    $group->sum('total_value'),
                ];
            })
            ->values();

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function borrowedReport(string $title): array
    {
        $headers = ['Item Code', 'Item Name', 'Borrower', 'Date Borrowed', 'Expected Return', 'Department'];

        $rows = BorrowingRecord::query()
            ->where('status', 'borrowed')
            ->with(['item', 'department'])
            ->latest('date_borrowed')
            ->get()
            ->map(fn ($record) => [
                $record->item?->item_code,
                $record->item?->name,
                $record->borrower_name,
                $record->date_borrowed?->format('Y-m-d'),
                $record->expected_return_date?->format('Y-m-d'),
                $record->department?->name,
            ]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function lowStockReport(string $title): array
    {
        $headers = ['Item Code', 'Name', 'Quantity', 'Reorder Level', 'Location'];

        $rows = InventoryItem::query()
            ->active()
            ->with('location')
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->orderBy('quantity')
            ->get()
            ->map(fn ($item) => [
                $item->item_code,
                $item->name,
                $item->quantity,
                $item->reorder_level,
                $item->location?->name,
            ]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function conditionReport(string $title, string $condition): array
    {
        $headers = ['Item Code', 'Name', 'Quantity', 'Status', 'Location'];

        $rows = InventoryItem::query()
            ->active()
            ->where('condition', $condition)
            ->with('location')
            ->orderBy('item_code')
            ->get()
            ->map(fn ($item) => [
                $item->item_code,
                $item->name,
                $item->quantity,
                $item->status,
                $item->location?->name,
            ]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function stockMovementReport(string $title, Request $request): array
    {
        $headers = ['Date', 'Type', 'Item', 'Quantity', 'Before', 'After', 'Remarks'];

        $query = InventoryTransaction::query()
            ->with('item')
            ->orderByDesc('transaction_date');

        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('to_date'));
        }

        $rows = $query->limit(500)->get()->map(fn ($tx) => [
            $tx->transaction_date?->format('Y-m-d'),
            $tx->type,
            $tx->item?->item_code,
            $tx->quantity,
            $tx->quantity_before,
            $tx->quantity_after,
            $tx->remarks,
        ]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function valuationReport(string $title): array
    {
        $headers = ['Item Code', 'Name', 'Quantity', 'Unit Cost', 'Total Value'];

        $rows = InventoryItem::query()
            ->active()
            ->orderByDesc('total_value')
            ->get()
            ->map(fn ($item) => [
                $item->item_code,
                $item->name,
                $item->quantity,
                $item->unit_cost,
                $item->total_value,
            ]);

        $total = $rows->sum(fn ($row) => (float) ($row[4] ?? 0));
        $rows->push(['', 'TOTAL', '', '', $total]);

        return [$headers, $rows, $title];
    }

    /**
     * @return array{0: list<string>, 1: Collection<int, list<string|float|int|null>>, 2: string}
     */
    protected function qrInventoryReport(string $title): array
    {
        $headers = ['Item Code', 'QR Code', 'Name', 'Status', 'Location'];

        $rows = InventoryItem::query()
            ->active()
            ->with('location')
            ->orderBy('qr_code')
            ->get()
            ->map(fn ($item) => [
                $item->item_code,
                $item->qr_code,
                $item->name,
                $item->status,
                $item->location?->name,
            ]);

        return [$headers, $rows, $title];
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, list<string|float|int|null>>  $rows
     */
    protected function exportPdf(string $title, array $headers, Collection $rows): Response
    {
        $pdf = Pdf::loadView('reports.pdf.table', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'generatedAt' => ph_datetime(now()),
        ]);

        $filename = str($title)->slug().'_'.now('Asia/Manila')->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, list<string|float|int|null>>  $rows
     */
    protected function exportCsv(string $title, array $headers, Collection $rows): StreamedResponse
    {
        $filename = str($title)->slug().'_'.now('Asia/Manila')->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  list<string>  $headers
     * @param  Collection<int, list<string|float|int|null>>  $rows
     */
    protected function exportExcel(string $title, array $headers, Collection $rows): BinaryFileResponse
    {
        $collection = $rows->map(fn ($row) => array_combine($headers, $row));

        $export = new class($collection, $headers) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(protected Collection $data, protected array $headingsList) {}

            public function collection(): Collection
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headingsList;
            }
        };

        $filename = str($title)->slug().'_'.now('Asia/Manila')->format('Y-m-d').'.xlsx';

        return Excel::download($export, $filename);
    }
}
