<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    private array $restrictedPermissions = [
        'platform.roles.manage',
        'platform.permissions.manage',
        'platform.settings.manage',
    ];

    private function isSuperAdmin(Request $request): bool
    {
        return $request->user()->hasRole('super_admin');
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()->can('platform.users.view')
            || $request->user()->can('platform.users.manage'),
            403,
            'Huna ruhusa ya kuona taarifa za watumiaji.'
        );
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless(
            $request->user()->can('platform.users.manage'),
            403,
            'Huna ruhusa ya kusimamia watumiaji.'
        );
    }

    private function authorizeTarget(Request $request, User $user): void
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            abort(403, 'Huwezi kubadilisha role au permissions za akaunti yako mwenyewe.');
        }

        if (
            !$this->isSuperAdmin($request)
            && $user->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Huna ruhusa ya kusimamia akaunti hii.');
        }
    }

    private function availableRoles(Request $request)
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name');

        if (!$this->isSuperAdmin($request)) {
            $query->whereNotIn('name', [
                'super_admin',
                'admin',
            ]);
        }

        return $query->get(['id', 'name']);
    }

    private function availablePermissions(Request $request)
    {
        $query = Permission::query()
            ->where('guard_name', 'web');

        if (!$this->isSuperAdmin($request)) {
            $actorPermissionNames = $request->user()
                ->getAllPermissions()
                ->pluck('name')
                ->reject(fn ($name) => in_array(
                    $name,
                    $this->restrictedPermissions,
                    true
                ))
                ->values()
                ->all();

            $query->whereIn('name', $actorPermissionNames);
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $search = trim((string) $request->query('search', ''));
        $roleFilter = trim((string) $request->query('role', ''));

        $users = User::query()
            ->with('roles:id,name')
            ->when(!$this->isSuperAdmin($request), function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->whereIn('name', [
                        'super_admin',
                        'admin',
                    ]);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter !== '', function ($query) use ($roleFilter) {
                $query->whereHas('roles', function ($roleQuery) use ($roleFilter) {
                    $roleQuery->where('name', $roleFilter);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
            ],
            'roles' => $this->availableRoles($request),
            'canManageUsers' => $request->user()
                ->can('platform.users.manage'),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorizeView($request);

        if (
            !$this->isSuperAdmin($request)
            && $user->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Huna ruhusa ya kuona akaunti hii.');
        }

        $user->load('roles.permissions');

        $rolePermissions = $user->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
            ]);

        $directPermissions = $user->getDirectPermissions()
            ->sortBy('name')
            ->values()
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
            ]);

        return Inertia::render('Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'roles' => $user->getRoleNames()->values(),
            ],
            'availableRoles' => $this->availableRoles($request),
            'selectedRole' => $user->getRoleNames()->first(),
            'availablePermissions' => $this->availablePermissions($request),
            'directPermissions' => $directPermissions,
            'rolePermissions' => $rolePermissions,
            'canManageUsers' => $request->user()
                ->can('platform.users.manage')
                && !$request->user()->is($user),
            'isSuperAdmin' => $this->isSuperAdmin($request),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagement($request);
        $this->authorizeTarget($request, $user);

        $availableRoleNames = $this->availableRoles($request)
            ->pluck('name')
            ->all();

        $grantablePermissionNames = $this->availablePermissions($request)
            ->pluck('name')
            ->all();

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in($availableRoleNames),
            ],
            'direct_permissions' => [
                'present',
                'array',
            ],
            'direct_permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::in($grantablePermissionNames),
            ],
        ]);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $validated['direct_permissions'])
            ->get();

        $user->syncRoles([$validated['role']]);
        $user->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Role na direct permissions zimehifadhiwa kikamilifu.');
    }
}
