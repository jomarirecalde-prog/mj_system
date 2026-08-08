<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\InventoryCategory;
use App\Models\InventoryLocation;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'code' => 'ELEC', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Furniture', 'code' => 'FURN', 'description' => 'Office furniture'],
            ['name' => 'Tools', 'code' => 'TOOL', 'description' => 'Hand and power tools'],
            ['name' => 'Supplies', 'code' => 'SUPP', 'description' => 'Consumable office supplies'],
        ];

        foreach ($categories as $category) {
            InventoryCategory::query()->updateOrCreate(['code' => $category['code']], $category + ['is_active' => true]);
        }

        $locations = [
            ['name' => 'Main Warehouse', 'code' => 'WH-MAIN', 'building' => 'Building A', 'office' => 'Storage', 'floor' => '1'],
            ['name' => 'IT Stock Room', 'code' => 'IT-STR', 'building' => 'Building B', 'office' => 'IT', 'floor' => '2'],
            ['name' => 'Admin Office', 'code' => 'ADM-OFF', 'building' => 'Building A', 'office' => 'Admin', 'floor' => '3'],
        ];

        foreach ($locations as $location) {
            InventoryLocation::query()->updateOrCreate(['code' => $location['code']], $location + ['is_active' => true]);
        }

        $departments = [
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'IT department'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'HR department'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Finance department'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operations department'],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(['code' => $department['code']], $department + ['is_active' => true]);
        }

        $suppliers = [
            ['name' => 'TechSupply Co.', 'contact_person' => 'Maria Santos', 'email' => 'sales@techsupply.local', 'phone' => '+63 912 000 0001'],
            ['name' => 'OfficeMart PH', 'contact_person' => 'Juan Dela Cruz', 'email' => 'orders@officemart.local', 'phone' => '+63 917 000 0002'],
            ['name' => 'Global Tools Inc.', 'contact_person' => 'Ana Reyes', 'email' => 'info@globaltools.local', 'phone' => '+63 918 000 0003'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(['name' => $supplier['name']], $supplier + ['is_active' => true]);
        }
    }
}
