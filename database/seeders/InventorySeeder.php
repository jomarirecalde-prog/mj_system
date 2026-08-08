<?php

namespace Database\Seeders;

use App\Models\BorrowingRecord;
use App\Models\Department;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Support\InventoryTransactionType;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@inventory.local')->first();
        $staff = User::query()->where('email', 'staff@inventory.local')->first();
        $actorId = $admin?->id ?? $staff?->id;

        $electronics = InventoryCategory::query()->where('code', 'ELEC')->first();
        $furniture = InventoryCategory::query()->where('code', 'FURN')->first();
        $tools = InventoryCategory::query()->where('code', 'TOOL')->first();
        $supplies = InventoryCategory::query()->where('code', 'SUPP')->first();

        $warehouse = InventoryLocation::query()->where('code', 'WH-MAIN')->first();
        $itRoom = InventoryLocation::query()->where('code', 'IT-STR')->first();
        $adminOffice = InventoryLocation::query()->where('code', 'ADM-OFF')->first();

        $itDept = Department::query()->where('code', 'IT')->first();
        $opsDept = Department::query()->where('code', 'OPS')->first();

        $techSupplier = Supplier::query()->where('name', 'TechSupply Co.')->first();
        $officeSupplier = Supplier::query()->where('name', 'OfficeMart PH')->first();

        $items = [
            [
                'item_code' => 'ITM-2026-0001',
                'qr_code' => 'INV-2026-000001',
                'name' => 'Dell Latitude Laptop',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $electronics?->id,
                'location_id' => $itRoom?->id,
                'department_id' => $itDept?->id,
                'supplier_id' => $techSupplier?->id,
                'brand' => 'Dell',
                'model' => 'Latitude 5540',
                'serial_number' => 'SN-DELL-5540-001',
                'quantity' => 5,
                'unit_cost' => 45000,
                'reorder_level' => 2,
                'minimum_stock' => 2,
                'condition' => 'Good',
                'status' => 'Available',
            ],
            [
                'item_code' => 'ITM-2026-0002',
                'qr_code' => 'INV-2026-000002',
                'name' => 'HP LaserJet Printer',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $electronics?->id,
                'location_id' => $adminOffice?->id,
                'department_id' => $opsDept?->id,
                'supplier_id' => $techSupplier?->id,
                'brand' => 'HP',
                'model' => 'LaserJet Pro',
                'serial_number' => 'SN-HP-LJ-002',
                'quantity' => 2,
                'unit_cost' => 18500,
                'reorder_level' => 1,
                'condition' => 'Good',
                'status' => 'Borrowed',
            ],
            [
                'item_code' => 'ITM-2026-0003',
                'qr_code' => 'INV-2026-000003',
                'name' => 'Ergonomic Office Chair',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $furniture?->id,
                'location_id' => $warehouse?->id,
                'department_id' => $opsDept?->id,
                'supplier_id' => $officeSupplier?->id,
                'quantity' => 12,
                'unit_cost' => 6500,
                'reorder_level' => 3,
                'condition' => 'Good',
                'status' => 'Available',
            ],
            [
                'item_code' => 'ITM-2026-0004',
                'qr_code' => 'INV-2026-000004',
                'name' => 'Cordless Drill Set',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $tools?->id,
                'location_id' => $warehouse?->id,
                'quantity' => 3,
                'unit_cost' => 4200,
                'reorder_level' => 2,
                'condition' => 'Fair',
                'status' => 'Under Maintenance',
            ],
            [
                'item_code' => 'ITM-2026-0005',
                'qr_code' => 'INV-2026-000005',
                'name' => 'A4 Bond Paper Ream',
                'inventory_type' => InventoryItem::TYPE_CONSUMABLE,
                'category_id' => $supplies?->id,
                'location_id' => $warehouse?->id,
                'quantity' => 50,
                'unit_cost' => 280,
                'reorder_level' => 75,
                'minimum_stock' => 20,
                'condition' => 'New',
                'status' => 'Available',
            ],
            [
                'item_code' => 'ITM-2026-0006',
                'qr_code' => 'INV-2026-000006',
                'name' => 'Network Switch 24-Port',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $electronics?->id,
                'location_id' => $itRoom?->id,
                'department_id' => $itDept?->id,
                'supplier_id' => $techSupplier?->id,
                'serial_number' => 'SN-NET-24-006',
                'quantity' => 0,
                'unit_cost' => 12000,
                'reorder_level' => 1,
                'condition' => 'Good',
                'status' => 'Out of Stock',
            ],
            [
                'item_code' => 'ITM-2026-0007',
                'qr_code' => 'INV-2026-000007',
                'name' => 'Whiteboard 4x6 ft',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $furniture?->id,
                'location_id' => $adminOffice?->id,
                'quantity' => 6,
                'unit_cost' => 3500,
                'reorder_level' => 2,
                'condition' => 'Good',
                'status' => 'Available',
            ],
            [
                'item_code' => 'ITM-2026-0008',
                'qr_code' => 'INV-2026-000008',
                'name' => 'Barcode Scanner',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $electronics?->id,
                'location_id' => $itRoom?->id,
                'department_id' => $itDept?->id,
                'serial_number' => 'SN-BC-008',
                'quantity' => 1,
                'unit_cost' => 8900,
                'reorder_level' => 2,
                'condition' => 'Damaged',
                'status' => 'Available',
            ],
            [
                'item_code' => 'ITM-2026-0009',
                'qr_code' => 'INV-2026-000009',
                'name' => 'Ballpen (Box of 50)',
                'inventory_type' => InventoryItem::TYPE_CONSUMABLE,
                'category_id' => $supplies?->id,
                'location_id' => $warehouse?->id,
                'supplier_id' => $officeSupplier?->id,
                'quantity' => 100,
                'unit_cost' => 150,
                'reorder_level' => 40,
                'minimum_stock' => 20,
                'condition' => 'New',
                'status' => 'Available',
            ],
            [
                'item_code' => 'ITM-2026-0010',
                'qr_code' => 'INV-2026-000010',
                'name' => 'Archived Projector',
                'inventory_type' => InventoryItem::TYPE_ASSET,
                'category_id' => $electronics?->id,
                'location_id' => $warehouse?->id,
                'quantity' => 1,
                'unit_cost' => 22000,
                'reorder_level' => 0,
                'condition' => 'Fair',
                'status' => 'Archived',
                'is_archived' => true,
                'archived_at' => now('Asia/Manila'),
            ],
        ];

        foreach ($items as $itemData) {
            $qty = (float) ($itemData['quantity'] ?? 0);
            $itemData['created_by'] = $actorId;
            $itemData['updated_by'] = $actorId;
            $itemData['unit'] = $itemData['unit'] ?? 'pcs';
            $itemData['inventory_type'] = $itemData['inventory_type'] ?? InventoryItem::TYPE_CONSUMABLE;
            $itemData['date_acquired'] = $itemData['date_acquired'] ?? now('Asia/Manila')->subMonths(2)->toDateString();

            // Persist quantity, then ensure an opening ledger row exists.
            $item = InventoryItem::query()->updateOrCreate(
                ['item_code' => $itemData['item_code']],
                $itemData,
            );

            $item->recalculateTotalValue();
            $item->save();

            $hasOpening = InventoryTransaction::query()
                ->where('inventory_item_id', $item->id)
                ->whereIn('type', [
                    InventoryTransactionType::INITIAL_STOCK,
                    InventoryTransactionType::PURCHASE,
                    InventoryTransactionType::STOCK_IN,
                ])
                ->exists();

            if (! $hasOpening && $qty > 0 && $actorId) {
                InventoryTransaction::query()->create([
                    'inventory_item_id' => $item->id,
                    'type' => InventoryTransactionType::INITIAL_STOCK,
                    'quantity' => $qty,
                    'quantity_before' => 0,
                    'quantity_after' => $qty,
                    'transaction_date' => $item->date_acquired?->toDateString() ?? now('Asia/Manila')->toDateString(),
                    'reference_number' => $item->item_code,
                    'performed_by' => $actorId,
                    'remarks' => 'Seeded initial stock',
                ]);
            }
        }

        $borrowedItem = InventoryItem::query()->where('qr_code', 'INV-2026-000002')->first();

        if ($borrowedItem !== null && ! BorrowingRecord::query()->where('inventory_item_id', $borrowedItem->id)->exists()) {
            BorrowingRecord::query()->create([
                'inventory_item_id' => $borrowedItem->id,
                'borrower_name' => 'Carlos Mendoza',
                'borrower_id_number' => 'ID-7788',
                'department_id' => $opsDept?->id,
                'date_borrowed' => now('Asia/Manila')->subDays(3)->toDateString(),
                'expected_return_date' => now('Asia/Manila')->addDays(4)->toDateString(),
                'purpose' => 'Branch presentation',
                'condition_before' => 'Good',
                'approved_by' => $staff?->id,
                'processed_by' => $staff?->id,
                'status' => 'borrowed',
            ]);
        }
    }
}
