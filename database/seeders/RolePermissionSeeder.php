<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full system access'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Inventory operations'],
            ['name' => 'Viewer', 'slug' => 'viewer', 'description' => 'Read-only access'],
        ];

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(['slug' => $roleData['slug']], $roleData);
        }

        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'module' => 'inventory'],
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'module' => 'inventory'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'users'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'module' => 'settings'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Manage Master Data', 'slug' => 'master.manage', 'module' => 'master'],
            ['name' => 'Backup Restore', 'slug' => 'backup.manage', 'module' => 'backup'],
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'module' => 'audit'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::query()->updateOrCreate(['slug' => $permissionData['slug']], $permissionData);
        }

        $admin = Role::query()->where('slug', 'admin')->first();
        $staff = Role::query()->where('slug', 'staff')->first();
        $viewer = Role::query()->where('slug', 'viewer')->first();

        if ($admin !== null) {
            $admin->permissions()->sync(Permission::query()->pluck('id'));
        }

        if ($staff !== null) {
            $staff->permissions()->sync(
                Permission::query()->whereIn('slug', [
                    'dashboard.view',
                    'inventory.view',
                    'inventory.manage',
                    'reports.view',
                ])->pluck('id')
            );
        }

        if ($viewer !== null) {
            $viewer->permissions()->sync(
                Permission::query()->whereIn('slug', [
                    'dashboard.view',
                    'inventory.view',
                    'reports.view',
                ])->pluck('id')
            );
        }
    }
}
