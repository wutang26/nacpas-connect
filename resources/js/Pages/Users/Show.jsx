import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Users.css';

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

function PermissionGroup({ title, permissions, selected = [], onToggle, readOnly = false }) {
    const grouped = groupPermissions(permissions);

    if (permissions.length === 0) {
        return (
            <div className="user-permission-empty">
                No permissions available in this section.
            </div>
        );
    }

    return (
        <div className="user-permission-groups">
            {Object.entries(grouped).map(([group, items]) => (
                <div className="user-permission-group" key={group}>
                    <h4>{group}</h4>

                    <div className="user-permission-options">
                        {items.map((permission) => {
                            const checked = selected.includes(permission.name);

                            return (
                                <label
                                    className={`user-permission-option ${
                                        checked ? 'checked' : ''
                                    } ${readOnly ? 'readonly' : ''}`}
                                    key={permission.id}
                                >
                                    <input
                                        type="checkbox"
                                        checked={checked}
                                        disabled={readOnly}
                                        onChange={() => onToggle?.(permission.name)}
                                    />

                                    <span className="user-permission-checkmark"></span>

                                    <span className="user-permission-label">
                                        {formatPermissionName(permission.name)}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Show({
    user,
    availableRoles = [],
    selectedRole = '',
    availablePermissions = [],
    directPermissions = [],
    rolePermissions = [],
    canManageUsers = false,
    isSuperAdmin = false,
}) {
    const { flash = {} } = usePage().props;

    const initialDirectPermissions = directPermissions.map(
        (permission) => permission.name
    );

    const form = useForm({
        role: selectedRole || '',
        direct_permissions: initialDirectPermissions,
    });

    const verifyForm = useForm({});

    const togglePermission = (permissionName) => {
        const current = form.data.direct_permissions;

        const updated = current.includes(permissionName)
            ? current.filter((name) => name !== permissionName)
            : [...current, permissionName];

        form.setData('direct_permissions', updated);
    };

    const submit = (event) => {
        event.preventDefault();

        form.put(`/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';

        return new Date(date).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const selectedRoleDetails = availableRoles.find(
        (role) => role.name === form.data.role
    );

    return (
        <AuthenticatedLayout>
            <Head title={`User - ${user.name}`} />

            <div className="users-page">
                <div className="users-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/users">User Management</Link>
                    <span>/</span>
                    <span>User Details</span>
                </div>

                <div className="users-heading">
                    <div>
                        <h1>User Details</h1>
                        <p>
                            View account information and manage assigned roles
                            and permissions.
                        </p>
                    </div>

                    <Link href="/users" className="users-secondary-button">
                        ← Back to Users
                    </Link>
                </div>

                {flash.success && (
                    <div className="users-alert success">
                        ✓ {flash.success}
                    </div>
                )}

                <div className="user-profile-card">
                    <div className="user-profile-avatar">
                        {user.name?.charAt(0)?.toUpperCase() || 'U'}
                    </div>

                    <div className="user-profile-information">
                        <h2>{user.name}</h2>
                        <p>{user.email}</p>

                        <div className="user-profile-meta">
                            <span>
                                User ID: #{user.id}
                            </span>

                            <span>
                                Registered: {formatDate(user.created_at)}
                            </span>

                            <span className={`users-email-status ${
                                user.email_verified_at ? 'verified' : 'unverified'
                            }`}>
                                {user.email_verified_at
                                    ? 'Email verified'
                                    : 'Email not verified'}
                            </span>
                        </div>

                                        {(isSuperAdmin || canManageUsers) && !user.email_verified_at && (
                    <div style={{ marginTop: '16px' }}>
                        <button
                            type="button"
                            className="users-primary-button"
                            disabled={verifyForm.processing}
                            onClick={() => {
                                if (window.confirm(
                                    `Unathibitisha kuwa ${user.email} ni email halali ya mtumiaji huyu?`
                                )) {
                                    verifyForm.post(`/users/${user.id}/verify-email`, {
                                        preserveScroll: true,
                                    });
                                }
                            }}
                        >
                            {verifyForm.processing
                                ? 'Verifying Email...'
                                : '✓ Verify Email'}
                        </button>
                    </div>
                )}
                    </div>
                </div>




                {!canManageUsers && (
                    <div className="users-alert error">
                        You have view-only access. You cannot change this user's
                        role or permissions.
                    </div>
                )}

                <form onSubmit={submit}>
                    <div className="user-management-panel">
                        <div className="user-management-heading">
                            <div>
                                <h3>Role Assignment</h3>
                                <p>
                                    Select one primary role for this user.
                                    The selected role determines inherited permissions.
                                </p>
                            </div>

                            <span className="user-section-number">01</span>
                        </div>

                        {availableRoles.length === 0 ? (
                            <div className="user-permission-empty">
                                No assignable roles are available.
                            </div>
                        ) : (
                            <div className="user-role-options">
                                {availableRoles.map((role) => (
                                    <label
                                        className={`user-role-option ${
                                            form.data.role === role.name ? 'selected' : ''
                                        }`}
                                        key={role.id}
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            value={role.name}
                                            checked={form.data.role === role.name}
                                            disabled={!canManageUsers || form.processing}
                                            onChange={(event) =>
                                                form.setData('role', event.target.value)
                                            }
                                        />

                                        <span className="user-role-radio"></span>

                                        <span className="user-role-information">
                                            <strong>
                                                {role.name.replaceAll('_', ' ')}
                                            </strong>

                                            <small>
                                                {role.name === 'user'
                                                    ? 'Default public user account'
                                                    : 'Platform access role'}
                                            </small>
                                        </span>
                                    </label>
                                ))}
                            </div>
                        )}

                        {form.errors.role && (
                            <p className="user-form-error">{form.errors.role}</p>
                        )}
                    </div>

                    <div className="user-management-panel">
                        <div className="user-management-heading">
                            <div>
                                <h3>Role Permissions</h3>
                                <p>
                                    These permissions are inherited automatically
                                    from the selected role. They are displayed
                                    separately and cannot be edited here.
                                </p>
                            </div>

                            <span className="user-section-number">02</span>
                        </div>

                        <div className="user-selected-role">
                            <span>Selected role</span>
                            <strong>
                                {selectedRoleDetails
                                    ? selectedRoleDetails.name.replaceAll('_', ' ')
                                    : form.data.role.replaceAll('_', ' ') || 'No role selected'}
                            </strong>
                        </div>

                        <PermissionGroup
                            permissions={rolePermissions}
                            selected={rolePermissions.map((permission) => permission.name)}
                            readOnly
                        />

                        <div className="user-permission-summary">
                            {rolePermissions.length} inherited permissions
                        </div>
                    </div>

                    <div className="user-management-panel">
                        <div className="user-management-heading">
                            <div>
                                <h3>Direct Permissions</h3>
                                <p>
                                    Select additional permissions to grant directly
                                    to this user, independently of their role.
                                </p>
                            </div>

                            <span className="user-section-number">03</span>
                        </div>

                        {availablePermissions.length === 0 ? (
                            <div className="user-permission-empty">
                                No direct permissions are available for assignment.
                            </div>
                        ) : (
                            <PermissionGroup
                                permissions={availablePermissions}
                                selected={form.data.direct_permissions}
                                onToggle={togglePermission}
                                readOnly={!canManageUsers || form.processing}
                            />
                        )}

                        {form.errors.direct_permissions && (
                            <p className="user-form-error">
                                {form.errors.direct_permissions}
                            </p>
                        )}

                        {Object.entries(form.errors)
                            .filter(([key]) => key.startsWith('direct_permissions.'))
                            .map(([key, message]) => (
                                <p className="user-form-error" key={key}>
                                    {message}
                                </p>
                            ))}

                        <div className="user-permission-summary">
                            {form.data.direct_permissions.length} direct permissions selected
                        </div>
                    </div>

                    {canManageUsers && (
                        <div className="user-save-panel">
                            <div>
                                <strong>Save User Access Settings</strong>
                                <p>
                                    Changes will replace the user's current role
                                    and direct permissions.
                                </p>
                            </div>

                            <button
                                type="submit"
                                className="users-primary-button user-save-button"
                                disabled={form.processing || !form.data.role}
                            >
                                {form.processing
                                    ? 'Saving Changes...'
                                    : 'Save Changes'}
                            </button>
                        </div>
                    )}
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
