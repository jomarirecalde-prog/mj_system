<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('inventory_items')
            ->where('part_number', 'like', 'BACS-%')
            ->orderBy('id')
            ->get(['id', 'part_number']);

        foreach ($rows as $row) {
            if (! is_string($row->part_number) || ! preg_match('/^BACS-(\d+)$/', $row->part_number, $matches)) {
                continue;
            }

            $candidate = sprintf('PN-%06d', (int) $matches[1]);

            while (DB::table('inventory_items')->where('part_number', $candidate)->exists()) {
                $next = ((int) substr($candidate, 3)) + 1;
                $candidate = sprintf('PN-%06d', $next);
            }

            DB::table('inventory_items')->where('id', $row->id)->update([
                'part_number' => $candidate,
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('inventory_items')
            ->where('part_number', 'like', 'PN-%')
            ->orderBy('id')
            ->get(['id', 'part_number']);

        foreach ($rows as $row) {
            if (! is_string($row->part_number) || ! preg_match('/^PN-(\d+)$/', $row->part_number, $matches)) {
                continue;
            }

            $candidate = sprintf('BACS-%06d', (int) $matches[1]);

            while (DB::table('inventory_items')->where('part_number', $candidate)->exists()) {
                $next = ((int) substr($candidate, 5)) + 1;
                $candidate = sprintf('BACS-%06d', $next);
            }

            DB::table('inventory_items')->where('id', $row->id)->update([
                'part_number' => $candidate,
            ]);
        }
    }
};
