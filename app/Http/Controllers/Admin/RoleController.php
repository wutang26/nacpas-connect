<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private array $systemRoles = [
        'super_admin',
        'admin',
        'organization_manager',
        'site_manager',
        'support_agent',
        'accountant',
        'user',
    ];

    private function isSuperAdmin(Request $request): bool
    {
        return $request->user()->hasRole('super_admin');
    }

    private function authorizeRoleView(Request $request): void
    {
        abort_unless(
            $this->isSuperAdmin($request)
            || $request->user()->can('platform.roles.view')
            || $request->user()->can('platform.roles.manage'),
            403,
            'Huna ruhusa ya kuona Roles.'
        );
    }

    private function authorizeRoleManagement(Request $request): void
    {
        abort_unless(
            $this->isSuperAdmin($request)
            || $request->user()->can('platform.roles.manage'),
            403,
            'Huna ruhusa ya kusimamia Roles.'
        );
    }

    private function availablePermissions(Request $request)
    {
        $query = Permission::query()
            ->where('guard_name', 'web');

        if (!$this->isSuperAdmin($request)) {
            $allowed = $request->user()
                ->getAllPermissions()
                ->pluck('name')
                ->all();

            $query->whereIn('name', $allowed);
        }

        return $query->orderBy('name')
            ->get(['id', 'name']);
    }

    private function protectRole(Request $request, Role $role): void
    {
        abort_if(
            $role->name === 'super_admin',
            403,
            'Role ya Super Admin haiwezi kubadilishwa hapa.'
        );

        if (
            $role->name === 'admin'
            && !$this->isSuperAdmin($request)
        ) {
            abort(403, 'Super Admin pekee ndiye anayeruhusiwa kusimamia Admin role.');
        }
    }

    public function index(Request $request): Response
    {
        $this->authorizeRoleView($request);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_system' => in_array($role->name, $this->systemRoles, true),
                'is_protected' => $role->name === 'super_admin',
            ]);

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'canManageRoles' => $this->isSuperAdmin($request)
                || $request->user()->can('platform.roles.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeRoleManagement($request);

        return Inertia::render('Roles/Create', [
            'availablePermissions' => $this->availablePermissions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoleManagement($request);

        $allowedPermissions = $this->availablePermissions($request)
            ->pluck('name')
            ->all();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
                Rule::notIn(['super_admin']),
            ],
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in($allowedPermissions),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($validated['permissions']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role mpya imeundwa kikamilifu.');
    }

    public function edit(Request $request, Role $role): Response
    {
        $this->authorizeRoleView($request);
        $this->protectRole($request, $role);

        abort_unless($role->guard_name === 'web', 404);

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions()
                    ->pluck('name')
                    ->values(),
                'is_system' => in_array($role->name, $this->systemRoles, true),
            ],
            'availablePermissions' => $this->availablePermissions($request),
            'canManageRoles' => $this->isSuperAdmin($request)
                || $request->user()->can('platform.roles.manage'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRoleManagement($request);
        $this->protectRole($request, $role);

        abort_unless($role->guard_name === 'web', 404);

        $allowedPermissions = $this->availablePermissions($request)
            ->pluck('name')
            ->all();

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in($allowedPermissions),
            ],
        ]);

        $role->syncPermissions($validated['permissions']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.edit', $role)
            ->with('success', 'Role permissions zimehifadhiwa kikamilifu.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeRoleManagement($request);
        $this->protectRole($request, $role);

        abort_unless($role->guard_name === 'web', 404);

        abort_if(
            in_array($role->name, $this->systemRoles, true),
            403,
            'System roles haziwezi kufutwa.'
        );

        abort_if(
            $role->users()->exists(),
            422,
            'Role hii bado imetumika na watumiaji. Ondoa assignments kwanza.'
        );

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role imefutwa kikamilifu.');
    }
}
