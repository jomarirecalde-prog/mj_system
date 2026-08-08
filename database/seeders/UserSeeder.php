<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'full_name' => 'System Administrator',
                'email' => 'admin@inventory.local',
                'employee_id' => 'EMP-001',
                'department' => 'Information Technology',
                'role' => 'admin',
                'status' => 'active',
                'password' => 'password',
            ],
            [
                'name' => 'Inventory Staff',
                'full_name' => 'Inventory Staff User',
                'email' => 'staff@inventory.local',
                'employee_id' => 'EMP-002',
                'department' => 'Operations',
                'role' => 'staff',
                'status' => 'active',
                'password' => 'password',
            ],
            [
                'name' => 'Portal Employee',
                'full_name' => 'Sample Employee User',
                'email' => 'employee@inventory.local',
                'employee_id' => 'EMP-2026-000010',
                'department' => 'Human Resources',
                'position' => 'Office Staff',
                'role' => 'employee',
                'status' => 'active',
                'password' => 'password',
            ],
        ];

        foreach ($users as $userData) {
            $password = $userData['password'];
            unset($userData['password']);

            User::query()->updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => Hash::make($password)]),
            );
        }
    }
}
