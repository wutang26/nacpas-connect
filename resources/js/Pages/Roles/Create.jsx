
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Roles.css';

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

export default function Create({ availablePermissions = [] }) {
    const { flash = {} } = usePage().props;

    const form = useForm({
        name: '',
        permissions: [],
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

        form.post('/roles', {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Role" />

            <div className="roles-page">
                <div className="roles-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/roles">Roles & Permissions</Link>
                    <span>/</span>
                    <span>Create Role</span>
                </div>

                <div className="roles-heading">
                    <div>
                        <h1>Create New Role</h1>
                        <p>
                            Create a role and assign the permissions it needs.
                        </p>
                    </div>

                    <Link href="/roles" className="roles-secondary-button">
                        ← Back to Roles
                    </Link>
                </div>

                {flash.error && (
                    <div className="roles-alert error">{flash.error}</div>
                )}

                <form onSubmit={submit}>
                    <div className="roles-panel roles-form-panel">
                        <div className="roles-panel-heading">
                            <div>
                                <h3>Role Information</h3>
                                <p>Enter a unique name for this role.</p>
                            </div>
                            <span className="roles-section-number">01</span>
                        </div>

                        <div className="roles-form-content">
                            <div className="roles-field">
                                <label htmlFor="role-name">Role Name</label>
                                <input
                                    id="role-name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    placeholder="e.g. network_operator"
                                    autoComplete="off"
                                    required
                                />
                                <small>
                                    Use lowercase letters, numbers and
                                    underscores. Example: network_operator.
                                </small>
                                {form.errors.name && (
                                    <span className="roles-field-error">
                                        {form.errors.name}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="roles-panel roles-form-panel">
                        <div className="roles-panel-heading">
                            <div>
                                <h3>Assign Permissions</h3>
                                <p>
                                    Select the actions this role is allowed
                                    to perform.
                                </p>
                            </div>

                            <span className="roles-selected-count">
                                {form.data.permissions.length} selected
                            </span>
                        </div>

                        <div className="roles-permission-content">
                            {Object.entries(grouped).map(([group, items]) => {
                                const allSelected = items.every((permission) =>
                                    form.data.permissions.includes(permission.name)
                                );

                                return (
                                    <div className="roles-permission-group" key={group}>
                                        <div className="roles-permission-group-heading">
                                            <h4>{group}</h4>

                                            <button
                                                type="button"
                                                onClick={() => toggleGroup(items)}
                                            >
                                                {allSelected
                                                    ? 'Deselect all'
                                                    : 'Select all'}
                                            </button>
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
                                                        }`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={checked}
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
                                    No permissions are available to assign.
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
                    </div>

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
                                ? 'Creating Role...'
                                : 'Create Role'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
