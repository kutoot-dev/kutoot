<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage campaign-plan access',
            'manage coupon-plan access',
            'manage campaigns',
            'manage coupons',
            'view transactions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        // Super Admin gets all via Gate::before — no explicit permission assignment needed

        $merchantAdmin = Role::firstOrCreate(['name' => 'Merchant Admin', 'guard_name' => 'web']);
        $merchantAdmin->syncPermissions([
            'manage campaigns',
            'manage coupons',
            'view transactions',
        ]);

        Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
    }
}
