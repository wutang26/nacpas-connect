<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super_admin')
            || $user->can('platform.permissions.view')
            || $user->can('platform.permissions.manage'),
            403,
            'Huna ruhusa ya kuona Permissions.'
        );

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->withCount('roles')
            ->orderBy('name')
            ->get()
            ->map(function (Permission $permission) {
                $parts = explode('.', $permission->name);

                $module = count($parts) >= 2
                    ? $parts[1]
                    : 'general';

                $action = count($parts) >= 3
                    ? implode('.', array_slice($parts, 2))
                    : 'access';

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $module,
                    'action' => $action,
                    'roles_count' => $permission->roles_count,
                ];
            })
            ->values();

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'canManagePermissions' => $user->hasRole('super_admin')
                || $user->can('platform.permissions.manage'),
        ]);
    }
}
