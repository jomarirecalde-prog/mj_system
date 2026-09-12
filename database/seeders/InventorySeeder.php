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
use App\Support\PartNumber;
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
        $hrDept = Department::query()->where('code', 'HR')->first();
        $finDept = Department::query()->where('code', 'FIN')->first();

        $techSupplier = Supplier::query()->where('name', 'TechSupply Co.')->first();
        $officeSupplier = Supplier::query()->where('name', 'OfficeMart PH')->first();
        $toolsSupplier = Supplier::query()->where('name', 'Global Tools Inc.')->first();

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

        $items = array_merge($items, $this->additionalSampleItems([
            'electronics' => $electronics?->id,
            'furniture' => $furniture?->id,
            'tools' => $tools?->id,
            'supplies' => $supplies?->id,
            'warehouse' => $warehouse?->id,
            'itRoom' => $itRoom?->id,
            'adminOffice' => $adminOffice?->id,
            'itDept' => $itDept?->id,
            'opsDept' => $opsDept?->id,
            'hrDept' => $hrDept?->id,
            'finDept' => $finDept?->id,
            'tech' => $techSupplier?->id,
            'office' => $officeSupplier?->id,
            'toolsSupplier' => $toolsSupplier?->id,
        ]));

        foreach ($items as $itemData) {
            $qty = (float) ($itemData['quantity'] ?? 0);
            $itemData['created_by'] = $actorId;
            $itemData['updated_by'] = $actorId;
            $itemData['unit'] = $itemData['unit'] ?? 'pcs';
            $itemData['inventory_type'] = $itemData['inventory_type'] ?? InventoryItem::TYPE_CONSUMABLE;
            $itemData['date_acquired'] = $itemData['date_acquired'] ?? now('Asia/Manila')->subMonths(2 + ($qty % 10))->toDateString();
            $itemData['selling_price'] = $itemData['selling_price'] ?? round(((float) ($itemData['unit_cost'] ?? 0)) * 1.15, 2);

            $existing = InventoryItem::query()->where('item_code', $itemData['item_code'])->first();
            $itemData['part_number'] = $itemData['part_number']
                ?? $existing?->part_number
                ?? PartNumber::generate();

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

    /**
     * Extra sample SKUs so the catalog totals 100 items (ITM-2026-0011 through 0100).
     *
     * @param  array<string, int|null>  $ids
     * @return list<array<string, mixed>>
     */
    protected function additionalSampleItems(array $ids): array
    {
        $cat = [
            'electronics' => $ids['electronics'] ?? null,
            'furniture' => $ids['furniture'] ?? null,
            'tools' => $ids['tools'] ?? null,
            'supplies' => $ids['supplies'] ?? null,
        ];
        $loc = [
            'warehouse' => $ids['warehouse'] ?? null,
            'itRoom' => $ids['itRoom'] ?? null,
            'adminOffice' => $ids['adminOffice'] ?? null,
        ];
        $dept = [
            'it' => $ids['itDept'] ?? null,
            'ops' => $ids['opsDept'] ?? null,
            'hr' => $ids['hrDept'] ?? null,
            'fin' => $ids['finDept'] ?? null,
        ];
        $sup = [
            'tech' => $ids['tech'] ?? null,
            'office' => $ids['office'] ?? null,
            'tools' => $ids['toolsSupplier'] ?? null,
        ];

        $catalog = [
            ['HP EliteBook Laptop', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'HP', 'EliteBook 840 G10', 4, 52000, 'pcs', 1, 'Good', 'Available'],
            ['Lenovo ThinkPad E14', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Lenovo', 'ThinkPad E14 Gen 5', 6, 48500, 'pcs', 2, 'Good', 'Available'],
            ['Acer Aspire 5 Laptop', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Acer', 'Aspire 5 A515', 3, 32900, 'pcs', 1, 'Good', 'Available'],
            ['Asus VivoBook 15', 'asset', 'electronics', 'itRoom', 'ops', 'tech', 'Asus', 'VivoBook 15 X1504', 2, 27500, 'pcs', 1, 'Fair', 'Available'],
            ['Apple MacBook Air M3', 'asset', 'electronics', 'adminOffice', 'it', 'tech', 'Apple', 'MacBook Air 13 M3', 2, 72000, 'pcs', 1, 'New', 'Available'],
            ['Dell OptiPlex Desktop', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Dell', 'OptiPlex 7010', 8, 38000, 'pcs', 2, 'Good', 'Available'],
            ['HP ProDesk 400 G9', 'asset', 'electronics', 'adminOffice', 'fin', 'tech', 'HP', 'ProDesk 400 G9', 5, 34500, 'pcs', 1, 'Good', 'Available'],
            ['Lenovo ThinkCentre M70q', 'asset', 'electronics', 'itRoom', 'hr', 'tech', 'Lenovo', 'ThinkCentre M70q', 4, 29800, 'pcs', 1, 'Good', 'Available'],
            ['Apple iMac 24-inch', 'asset', 'electronics', 'adminOffice', 'it', 'tech', 'Apple', 'iMac 24 M3', 1, 89000, 'pcs', 1, 'New', 'Available'],
            ['Dell 24-inch Monitor', 'asset', 'electronics', 'warehouse', 'it', 'tech', 'Dell', 'P2422H', 15, 9800, 'pcs', 3, 'Good', 'Available'],
            ['LG 27-inch IPS Monitor', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'LG', '27UP850', 6, 18500, 'pcs', 2, 'Good', 'Available'],
            ['Samsung 32-inch Curved Monitor', 'asset', 'electronics', 'adminOffice', 'ops', 'tech', 'Samsung', 'C32R500', 3, 14200, 'pcs', 1, 'Good', 'Available'],
            ['HP 22-inch Monitor', 'asset', 'electronics', 'warehouse', 'hr', 'tech', 'HP', 'P22v G5', 10, 6200, 'pcs', 3, 'Good', 'Available'],
            ['ViewSonic 24-inch Monitor', 'asset', 'electronics', 'warehouse', 'fin', 'tech', 'ViewSonic', 'VA2406-H', 7, 7100, 'pcs', 2, 'Good', 'Available'],
            ['Epson EcoTank L3250', 'asset', 'electronics', 'adminOffice', 'ops', 'tech', 'Epson', 'L3250', 4, 11500, 'pcs', 1, 'Good', 'Available'],
            ['Canon PIXMA G3010', 'asset', 'electronics', 'adminOffice', 'hr', 'tech', 'Canon', 'PIXMA G3010', 2, 9800, 'pcs', 1, 'Good', 'Available'],
            ['Brother MFC Laser Printer', 'asset', 'electronics', 'adminOffice', 'fin', 'tech', 'Brother', 'MFC-L2715DW', 3, 16800, 'pcs', 1, 'Good', 'Available'],
            ['Fujitsu ScanSnap Scanner', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Fujitsu', 'iX1600', 2, 24500, 'pcs', 1, 'Good', 'Available'],
            ['Epson WorkForce Scanner', 'asset', 'electronics', 'adminOffice', 'ops', 'tech', 'Epson', 'DS-870', 1, 18900, 'pcs', 1, 'Fair', 'Under Maintenance'],
            ['Cisco 8-Port Gigabit Switch', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Cisco', 'SG110D-08', 3, 6200, 'pcs', 1, 'Good', 'Available'],
            ['TP-Link Archer AX55 Router', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'TP-Link', 'Archer AX55', 4, 4500, 'pcs', 1, 'Good', 'Available'],
            ['Ubiquiti UniFi Access Point', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Ubiquiti', 'U6-Lite', 8, 7800, 'pcs', 2, 'Good', 'Available'],
            ['APC Back-UPS 1500VA', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'APC', 'BR1500MS', 5, 12500, 'pcs', 2, 'Good', 'Available'],
            ['CyberPower UPS 900VA', 'asset', 'electronics', 'warehouse', 'ops', 'tech', 'CyberPower', 'CP900EPFCLCD', 2, 5400, 'pcs', 1, 'Damaged', 'Available'],
            ['Epson EB-E01 Projector', 'asset', 'electronics', 'adminOffice', 'hr', 'tech', 'Epson', 'EB-E01', 2, 22000, 'pcs', 1, 'Good', 'Available'],
            ['BenQ MW560 Projector', 'asset', 'electronics', 'warehouse', 'ops', 'tech', 'BenQ', 'MW560', 1, 26500, 'pcs', 1, 'Good', 'Available'],
            ['Logitech C920 Webcam', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Logitech', 'C920 HD Pro', 12, 4200, 'pcs', 3, 'Good', 'Available'],
            ['Logitech Meetup Camera', 'asset', 'electronics', 'adminOffice', 'hr', 'tech', 'Logitech', 'Meetup', 2, 38500, 'pcs', 1, 'New', 'Available'],
            ['Jabra Evolve 40 Headset', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Jabra', 'Evolve 40', 20, 6500, 'pcs', 5, 'Good', 'Available'],
            ['HyperX Cloud II Headset', 'asset', 'electronics', 'itRoom', 'ops', 'tech', 'HyperX', 'Cloud II', 6, 3900, 'pcs', 2, 'Good', 'Available'],
            ['Apple iPhone 15', 'asset', 'electronics', 'adminOffice', 'it', 'tech', 'Apple', 'iPhone 15 128GB', 3, 52990, 'pcs', 1, 'New', 'Available'],
            ['Samsung Galaxy A55', 'asset', 'electronics', 'warehouse', 'ops', 'tech', 'Samsung', 'Galaxy A55 5G', 8, 18990, 'pcs', 2, 'Good', 'Available'],
            ['Apple iPad 10th Gen', 'asset', 'electronics', 'adminOffice', 'hr', 'tech', 'Apple', 'iPad 10.9', 4, 28990, 'pcs', 1, 'Good', 'Available'],
            ['Samsung Galaxy Tab A9', 'asset', 'electronics', 'warehouse', 'fin', 'tech', 'Samsung', 'Tab A9+', 5, 12990, 'pcs', 1, 'Good', 'Available'],
            ['Logitech MX Keys Keyboard', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Logitech', 'MX Keys', 10, 5200, 'pcs', 3, 'Good', 'Available'],
            ['Logitech MX Master 3S Mouse', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Logitech', 'MX Master 3S', 12, 4800, 'pcs', 3, 'Good', 'Available'],
            ['Anker USB-C Docking Station', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Anker', 'PowerExpand 8-in-1', 7, 3900, 'pcs', 2, 'Good', 'Available'],
            ['Synology NAS DS223', 'asset', 'electronics', 'itRoom', 'it', 'tech', 'Synology', 'DS223', 1, 18500, 'pcs', 1, 'Good', 'Available'],
            ['Hikvision Dome CCTV Camera', 'asset', 'electronics', 'warehouse', 'ops', 'tech', 'Hikvision', 'DS-2CD2143G2', 10, 4200, 'pcs', 2, 'Good', 'Available'],
            ['Grandstream IP Phone', 'asset', 'electronics', 'adminOffice', 'hr', 'tech', 'Grandstream', 'GRP2612', 16, 3500, 'pcs', 4, 'Good', 'Available'],
            ['Executive Office Desk', 'asset', 'furniture', 'adminOffice', 'ops', 'office', 'Uratex', 'Exec-180', 4, 12500, 'pcs', 1, 'Good', 'Available'],
            ['Staff Office Desk', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Our Home', 'Staff-120', 18, 4500, 'pcs', 4, 'Good', 'Available'],
            ['Conference Table 8-Seater', 'asset', 'furniture', 'adminOffice', 'hr', 'office', 'Mandaue Foam', 'Conf-240', 2, 18500, 'pcs', 1, 'Good', 'Available'],
            ['Folding Training Table', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Our Home', 'Fold-150', 10, 2800, 'pcs', 2, 'Good', 'Available'],
            ['Steel Filing Cabinet 4-Drawer', 'asset', 'furniture', 'adminOffice', 'fin', 'office', 'Steelcase', 'FC-4D', 8, 7200, 'pcs', 2, 'Good', 'Available'],
            ['Mobile Pedestal Drawer', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Uratex', 'Ped-3', 14, 3200, 'pcs', 3, 'Good', 'Available'],
            ['Open Shelf Bookcase', 'asset', 'furniture', 'adminOffice', 'hr', 'office', 'Our Home', 'Shelf-5T', 6, 4100, 'pcs', 2, 'Good', 'Available'],
            ['Reception Sofa Set', 'asset', 'furniture', 'adminOffice', 'hr', 'office', 'Mandaue Foam', 'Recv-3pc', 1, 24500, 'set', 1, 'Good', 'Available'],
            ['Visitor Side Chair', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Uratex', 'Visit-01', 24, 1800, 'pcs', 6, 'Good', 'Available'],
            ['Standing Desk Converter', 'asset', 'furniture', 'itRoom', 'it', 'office', 'FlexiSpot', 'Loctek-36', 3, 8900, 'pcs', 1, 'New', 'Available'],
            ['Whiteboard 3x4 ft', 'asset', 'furniture', 'adminOffice', 'hr', 'office', 'Ace', 'WB-3x4', 5, 2200, 'pcs', 2, 'Good', 'Available'],
            ['Cork Bulletin Board', 'asset', 'furniture', 'adminOffice', 'ops', 'office', 'Ace', 'Cork-2x3', 8, 950, 'pcs', 2, 'Good', 'Available'],
            ['Coat Rack Stand', 'asset', 'furniture', 'adminOffice', 'hr', 'office', 'Our Home', 'Coat-5H', 4, 1500, 'pcs', 1, 'Good', 'Available'],
            ['Storage Cabinet with Lock', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Steelcase', 'SC-Lock', 6, 9800, 'pcs', 2, 'Good', 'Available'],
            ['Partition Cubicle Panel', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Our Home', 'Cub-160', 20, 3500, 'pcs', 4, 'Fair', 'Available'],
            ['Broken Lounge Chair', 'asset', 'furniture', 'warehouse', 'ops', 'office', 'Mandaue Foam', 'Lounge-01', 1, 6500, 'pcs', 0, 'Damaged', 'Archived'],
            ['Impact Wrench Set', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Dewalt', 'DCF899', 2, 12500, 'set', 1, 'Good', 'Available'],
            ['Screwdriver Set 32-pc', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Stanley', 'STHT0-74353', 6, 850, 'set', 2, 'Good', 'Available'],
            ['Digital Multimeter', 'asset', 'tools', 'itRoom', 'it', 'tools', 'Fluke', '101', 3, 4200, 'pcs', 1, 'Good', 'Available'],
            ['Cable Tester Kit', 'asset', 'tools', 'itRoom', 'it', 'tools', 'NOYAFA', 'NF-8601', 4, 2800, 'set', 1, 'Good', 'Available'],
            ['Aluminum Ladder 6-ft', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Louis', 'AL-6', 3, 2100, 'pcs', 1, 'Good', 'Available'],
            ['Heavy Duty Stapler', 'asset', 'tools', 'adminOffice', 'ops', 'office', 'Max', 'HD-12N', 8, 650, 'pcs', 2, 'Good', 'Available'],
            ['Heat Gun', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Bosch', 'GHG 180', 2, 2400, 'pcs', 1, 'Good', 'Available'],
            ['Soldering Station', 'asset', 'tools', 'itRoom', 'it', 'tools', 'Hakko', 'FX-888D', 2, 6800, 'pcs', 1, 'Good', 'Available'],
            ['Socket Wrench Set', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Stanley', 'STMT71654', 3, 1950, 'set', 1, 'Fair', 'Available'],
            ['Safety Tool Box', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Stanley', 'STST18613', 5, 1400, 'pcs', 2, 'Good', 'Available'],
            ['Electric Screwdriver', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Bosch', 'GO 3', 4, 3200, 'pcs', 1, 'Good', 'Available'],
            ['Measuring Tape 8m', 'asset', 'tools', 'warehouse', 'ops', 'tools', 'Stanley', 'PowerLock 8m', 12, 280, 'pcs', 4, 'Good', 'Available'],
            ['A3 Bond Paper Ream', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'HardCopy', 'A3-80gsm', 25, 420, 'ream', 40, 'New', 'Available'],
            ['Legal Size Bond Paper', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'HardCopy', 'Legal-80gsm', 18, 310, 'ream', 30, 'New', 'Available'],
            ['Sticky Notes Pad Pack', 'consumable', 'supplies', 'warehouse', 'ops', 'office', '3M', 'Post-it 654', 40, 95, 'pack', 20, 'New', 'Available'],
            ['Highlighter Set', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Stabilo', 'Boss 6-color', 30, 180, 'set', 15, 'New', 'Available'],
            ['Permanent Marker Box', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Pilot', 'SCA-400', 22, 240, 'box', 12, 'New', 'Available'],
            ['Correction Tape Box', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Tombow', 'Mono 5mm', 16, 320, 'box', 10, 'New', 'Available'],
            ['Manila Folder Pack of 100', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'BestBuy', 'MF-100', 12, 280, 'pack', 8, 'New', 'Available'],
            ['Expanding Envelope Pack', 'consumable', 'supplies', 'warehouse', 'fin', 'office', 'BestBuy', 'EE-Legal', 9, 350, 'pack', 6, 'New', 'Available'],
            ['Binder Clip Assorted', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Joy', 'BC-Mix', 35, 75, 'box', 15, 'New', 'Available'],
            ['Staple Wire Box', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Max', 'No.10', 48, 45, 'box', 20, 'New', 'Available'],
            ['HP 35A Toner Cartridge', 'consumable', 'supplies', 'adminOffice', 'ops', 'tech', 'HP', 'CB435A', 6, 2100, 'pcs', 8, 'New', 'Available'],
            ['Epson 003 Ink Bottle Set', 'consumable', 'supplies', 'adminOffice', 'ops', 'tech', 'Epson', '003-CMYK', 10, 1450, 'set', 6, 'New', 'Available'],
            ['HDMI Cable 2m', 'consumable', 'supplies', 'itRoom', 'it', 'tech', 'Ugreen', 'HDMI-2M', 28, 250, 'pcs', 10, 'New', 'Available'],
            ['RJ45 Ethernet Cable 5m', 'consumable', 'supplies', 'itRoom', 'it', 'tech', 'Ugreen', 'Cat6-5M', 40, 180, 'pcs', 15, 'New', 'Available'],
            ['USB-C Cable 1m', 'consumable', 'supplies', 'itRoom', 'it', 'tech', 'Anker', 'USB-C 1M', 22, 350, 'pcs', 10, 'New', 'Available'],
            ['AA Alkaline Batteries Pack', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Energizer', 'AA-8pk', 18, 220, 'pack', 12, 'New', 'Available'],
            ['AAA Batteries Pack', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Energizer', 'AAA-8pk', 14, 210, 'pack', 12, 'New', 'Available'],
            ['Disinfectant Spray', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Lysol', '500ml', 20, 185, 'bottle', 15, 'New', 'Available'],
            ['Trash Bag Large Roll', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Joy', 'XL-30pcs', 11, 95, 'roll', 20, 'New', 'Available'],
            ['Hand Soap Refill', 'consumable', 'supplies', 'warehouse', 'hr', 'office', 'Safeguard', '1L', 8, 120, 'bottle', 10, 'New', 'Available'],
            ['Thermal Receipt Paper Roll', 'consumable', 'supplies', 'adminOffice', 'fin', 'office', 'Generic', '80x80', 3, 45, 'roll', 25, 'New', 'Available'],
            ['Packing Tape 2-inch', 'consumable', 'supplies', 'warehouse', 'ops', 'office', 'Scotch', '48mm', 0, 65, 'roll', 12, 'New', 'Out of Stock'],
        ];

        $items = [];
        foreach ($catalog as $index => $row) {
            [
                $name, $type, $categoryKey, $locationKey, $departmentKey, $supplierKey,
                $brand, $model, $qty, $cost, $unit, $reorder, $condition, $status,
            ] = $row;

            $n = $index + 11;
            $isAsset = $type === InventoryItem::TYPE_ASSET;
            $isArchived = $status === 'Archived';

            $items[] = [
                'item_code' => sprintf('ITM-2026-%04d', $n),
                'qr_code' => sprintf('INV-2026-%06d', $n),
                'name' => $name,
                'description' => 'Sample '.$type.' stock for '.$name.'.',
                'inventory_type' => $type,
                'category_id' => $cat[$categoryKey] ?? null,
                'location_id' => $loc[$locationKey] ?? null,
                'department_id' => $dept[$departmentKey] ?? null,
                'supplier_id' => $sup[$supplierKey] ?? null,
                'brand' => $brand,
                'model' => $model,
                'serial_number' => $isAsset ? sprintf('SN-%s-%04d', strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $brand) ?: 'ITEM', 0, 4)), $n) : null,
                'quantity' => $qty,
                'unit' => $unit,
                'unit_cost' => $cost,
                'reorder_level' => $reorder,
                'minimum_stock' => max(1, (int) floor($reorder / 2)),
                'condition' => $condition,
                'status' => $status,
                'warranty_expiration' => $isAsset ? now('Asia/Manila')->addYear()->toDateString() : null,
                'is_archived' => $isArchived,
                'archived_at' => $isArchived ? now('Asia/Manila')->subMonth() : null,
            ];
        }

        return $items;
    }
}
