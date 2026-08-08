<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('selling_price', 14, 2)->default(0)->after('unit_cost');
        });

        // Seed selling price from unit cost for existing rows.
        DB::table('inventory_items')->update([
            'selling_price' => DB::raw('unit_cost'),
        ]);

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->date('sale_date');
            $table->string('customer_name')->nullable();
            $table->string('payment_method')->default('cash'); // cash, gcash, card, bank_transfer, other
            $table->string('status')->default('completed'); // completed, voided
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('discount', 16, 2)->default(0);
            $table->decimal('tax', 16, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->decimal('amount_tendered', 16, 2)->nullable();
            $table->decimal('change_due', 16, 2)->nullable();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['status', 'sale_date']);
            $table->index('cashier_id');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('selling_price');
        });
    }
};
