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
            $item->item_code,
            $item->qr_code,
            $item->name,
            $item->category?->name,
            $item->location?->name,
            $item->department?->name,
            $item->quantity,
            $item->unit,
            $item->unit_cost,
            $item->total_value,
            $item->condition,
            $item->status,
            $item->serial_number,
            $item->brand,
            $item->model,
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
            'Item Code',
            'QR Code',
            'Name',
            'Category',
            'Location',
            'Department',
            'Quantity',
            'Unit',
            'Unit Cost',
            'Total Value',
            'Condition',
            'Status',
            'Serial Number',
            'Brand',
            'Model',
            'Reorder Level',
            'Date Acquired',
        ];
    }
}
