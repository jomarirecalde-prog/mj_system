<?php

use App\Support\PartNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('part_number', 100)->nullable()->after('item_code');
        });

        $this->backfillPartNumbers();

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('part_number', 100)->nullable(false)->change();
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unique('part_number');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['part_number']);
            $table->dropColumn('part_number');
        });
    }

    protected function backfillPartNumbers(): void
    {
        $used = [];
        $maxSequence = 0;
        $pattern = '/^'.preg_quote(PartNumber::PREFIX, '/').'(\d+)$/';

        $rows = DB::table('inventory_items')
            ->orderBy('id')
            ->get(['id', 'part_number']);

        foreach ($rows as $row) {
            $current = PartNumber::normalize($row->part_number);

            if ($current !== '' && PartNumber::isValid($current) && ! isset($used[$current])) {
                $used[$current] = $row->id;

                if (preg_match($pattern, $current, $matches)) {
                    $maxSequence = max($maxSequence, (int) $matches[1]);
                }

                if ($current !== (string) $row->part_number) {
                    DB::table('inventory_items')->where('id', $row->id)->update([
                        'part_number' => $current,
                    ]);
                }

                continue;
            }

            do {
                $maxSequence++;
                $candidate = PartNumber::formatSequence($maxSequence);
            } while (isset($used[$candidate]));

            $used[$candidate] = $row->id;

            DB::table('inventory_items')->where('id', $row->id)->update([
                'part_number' => $candidate,
            ]);
        }
    }
};
