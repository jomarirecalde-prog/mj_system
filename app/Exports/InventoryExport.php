<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, \App\Models\InventoryItem>  $items
     */
    public function __construct(protected Collection $items) {}

    public function collection(): Collection
    {
        return $this->items->map(fn ($item) => [
            $item->part_number,
            $item->name,
            $item->category?->name,
            $item->brand,
            $item->model,
            $item->quantity,
            $item->status,
            $item->item_code,
            $item->qr_code,
            $item->location?->name,
            $item->department?->name,
            $item->unit,
            $item->unit_cost,
            $item->total_value,
            $item->condition,
            $item->serial_number,
            $item->reorder_level,
            $item->date_acquired?->format('Y-m-d'),
        ]);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Part Number',
            'Item',
            'Category',
            'Brand',
            'Model',
            'Quantity',
            'Status',
            'Item Code',
            'QR Code',
            'Location',
            'Department',
            'Unit',
            'Unit Cost',
            'Total Value',
            'Condition',
            'Serial Number',
            'Reorder Level',
            'Date Acquired',
        ];
    }
}
