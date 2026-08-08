<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromCollection, WithHeadings
{
    /**
     * @param  list<string>  $headers
     * @param  Collection<int, list<string|int|float|null>>  $rows
     */
    public function __construct(
        protected array $headers,
        protected Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => collect($row)->values());
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headers;
    }
}
