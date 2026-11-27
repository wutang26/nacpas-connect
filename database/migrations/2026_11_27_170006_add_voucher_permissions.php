<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'voucher.management.view',
        'voucher.management.manage',
        'voucher.agents.view',
        'voucher.agents.manage',
        'voucher.reports.view',
        'voucher.agent.dashboard',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create a dedicated agent role without changing existing roles.
        DB::table('roles')->insertOrIgnore([
            'name' => 'voucher_agent',
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $superAdmin = DB::table('roles')
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first();

        if ($superAdmin) {
            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $this->permissions)
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $superAdmin->id,
                ]);
            }
        }

        $agentRole = DB::table('roles')
            ->where('name', 'voucher_agent')
            ->where('guard_name', 'web')
            ->first();

        if ($agentRole) {
            $agentPermission = DB::table('permissions')
                ->where('name', 'voucher.agent.dashboard')
                ->where('guard_name', 'web')
                ->first();

            if ($agentPermission) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $agentPermission->id,
                    'role_id' => $agentRole->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive.
        // Do not remove permissions or roles that may have been assigned.
    }
};
