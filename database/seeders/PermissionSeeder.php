<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'platform.dashboard.view',
            'platform.organizations.view',
            'platform.organizations.create',
            'platform.organizations.update',
            'platform.organizations.delete',
            'platform.users.view',
            'platform.users.manage',
            'platform.saas_plans.view',
            'platform.saas_plans.manage',
            'platform.subscriptions.view',
            'platform.subscriptions.manage',
            'platform.payments.view',
            'platform.payments.manage',
            'platform.reports.view',
            'platform.settings.manage',

            'platform.qr_codes.view',
            'platform.qr_codes.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}