
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Roles.css';

function formatRoleName(name = '') {
    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatPermissionName(name) {
    return name
        .split('.')
        .map((part) => part.replaceAll('_', ' '))
        .join(' / ');
}

function groupPermissions(permissions = []) {
    return permissions.reduce((groups, permission) => {
        const parts = permission.name.split('.');
        const group = parts.length > 1
            ? parts.slice(0, -1).join(' / ')
            : 'General';

        if (!groups[group]) {
            groups[group] = [];
        }

        groups[group].push(permission);
        return groups;
    }, {});
}

export default function Edit({
    role,
    availablePermissions = [],
    canManageRoles = false,
}) {
    const { flash = {} } = usePage().props;

    const form = useForm({
        permissions: role.permissions || [],
    });

    const grouped = groupPermissions(availablePermissions);

    const togglePermission = (permissionName) => {
        const current = form.data.permissions;

        form.setData(
            'permissions',
            current.includes(permissionName)
                ? current.filter((name) => name !== permissionName)
                : [...current, permissionName]
        );
    };

    const toggleGroup = (items) => {
        const names = items.map((permission) => permission.name);
        const allSelected = names.every((name) =>
            form.data.permissions.includes(name)
        );

        form.setData(
            'permissions',
            allSelected
                ? form.data.permissions.filter((name) => !names.includes(name))
                : [...new Set([...form.data.permissions, ...names])]
        );
    };

    const submit = (event) => {
        event.preventDefault();

        form.put(`/roles/${role.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Role - ${formatRoleName(role.name)}`} />

            <div className="roles-page">
                <div className="roles-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/roles">Roles & Permissions</Link>
                    <span>/</span>
                    <span>Edit Role</span>
                </div>

                <div className="roles-heading">
                    <div>
                        <h1>Role Configuration</h1>
                        <p>
                            Review and manage permissions assigned to this role.
                        </p>
                    </div>

                    <Link href="/roles" className="roles-secondary-button">
                        ← Back to Roles
                    </Link>
                </div>

                {flash.success && (
                    <div className="roles-alert success">
                        ✓ {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="roles-alert error">{flash.error}</div>
                )}

                <div className="roles-panel roles-form-panel">
                    <div className="roles-panel-heading">
                        <div>
                            <h3>{formatRoleName(role.name)}</h3>
                            <p>
                                Role ID: #{role.id}
                                {role.is_system ? ' · System Role' : ' · Custom Role'}
                            </p>
                        </div>

                        <span className="roles-selected-count">
                            {form.data.permissions.length} permissions
                        </span>
                    </div>

                    {!canManageRoles && (
                        <div className="roles-alert info roles-inline-alert">
                            You have view-only access. You cannot change these
                            permissions.
                        </div>
                    )}

                    <form onSubmit={submit}>
                        <div className="roles-permission-content">
                            {Object.entries(grouped).map(([group, items]) => {
                                const allSelected = items.every((permission) =>
                                    form.data.permissions.includes(permission.name)
                                );

                                return (
                                    <div className="roles-permission-group" key={group}>
                                        <div className="roles-permission-group-heading">
                                            <h4>{group}</h4>

                                            {canManageRoles && (
                                                <button
                                                    type="button"
                                                    onClick={() => toggleGroup(items)}
                                                >
                                                    {allSelected
                                                        ? 'Deselect all'
                                                        : 'Select all'}
                                                </button>
                                            )}
                                        </div>

                                        <div className="roles-permission-options">
                                            {items.map((permission) => {
                                                const checked =
                                                    form.data.permissions.includes(
                                                        permission.name
                                                    );

                                                return (
                                                    <label
                                                        key={permission.id}
                                                        className={`roles-permission-option ${
                                                            checked ? 'checked' : ''
                                                        } ${!canManageRoles ? 'readonly' : ''}`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={checked}
                                                            disabled={!canManageRoles || form.processing}
                                                            onChange={() =>
                                                                togglePermission(
                                                                    permission.name
                                                                )
                                                            }
                                                        />

                                                        <span className="roles-checkbox"></span>

                                                        <span>
                                                            {formatPermissionName(
                                                                permission.name
                                                            )}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            })}

                            {availablePermissions.length === 0 && (
                                <div className="roles-empty">
                                    No permissions are available for this role.
                                </div>
                            )}

                            {form.errors.permissions && (
                                <p className="roles-field-error">
                                    {form.errors.permissions}
                                </p>
                            )}

                            {form.errors['permissions.0'] && (
                                <p className="roles-field-error">
                                    {form.errors['permissions.0']}
                                </p>
                            )}
                        </div>

                        {canManageRoles && (
                            <div className="roles-form-actions">
                                <Link
                                    href="/roles"
                                    className="roles-secondary-button"
                                >
                                    Cancel
                                </Link>

                                <button
                                    type="submit"
                                    className="roles-primary-button"
                                    disabled={form.processing}
                                >
                                    {form.processing
                                        ? 'Saving Changes...'
                                        : 'Save Permissions'}
                                </button>
                            </div>
                        )}
                    </form>
                </div>

                <div className="roles-security-note">
                    <strong>Important</strong>
                    <p>
                        Assign only the permissions required for this role.
                        Changes affect all users assigned to this role.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
