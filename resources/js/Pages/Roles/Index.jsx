
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Roles.css';

function formatRoleName(name = '') {
    return name
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export default function Index({ roles, canManageRoles = false }) {
    const { flash = {} } = usePage().props;

    const deleteRole = (role) => {
        if (role.is_system || role.is_protected || !canManageRoles) {
            return;
        }

        const confirmed = window.confirm(
            `Una uhakika unataka kufuta role "${formatRoleName(role.name)}"?`
        );

        if (confirmed) {
            router.delete(`/roles/${role.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Roles & Permissions" />

            <div className="roles-page">
                <div className="roles-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <span>Roles & Permissions</span>
                </div>

                <div className="roles-heading">
                    <div>
                        <h1>Roles & Permissions</h1>
                        <p>
                            Manage platform roles and control what each role
                            is allowed to access.
                        </p>
                    </div>

                    {canManageRoles && (
                        <Link
                            href="/roles/create"
                            className="roles-primary-button"
                        >
                            <span>＋</span>
                            Create New Role
                        </Link>
                    )}
                </div>

                {flash.success && (
                    <div className="roles-alert success">
                        ✓ {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="roles-alert error">
                        {flash.error}
                    </div>
                )}

                <div className="roles-statistics">
                    <div className="roles-stat-card">
                        <div className="roles-stat-icon">🛡️</div>
                        <div>
                            <span>Total Roles</span>
                            <strong>{roles.total || 0}</strong>
                        </div>
                    </div>

                    <div className="roles-stat-card">
                        <div className="roles-stat-icon">🔐</div>
                        <div>
                            <span>System Roles</span>
                            <strong>
                                {roles.data.filter((role) => role.is_system).length}
                            </strong>
                        </div>
                    </div>

                    <div className="roles-stat-card">
                        <div className="roles-stat-icon">👥</div>
                        <div>
                            <span>Assigned Users</span>
                            <strong>
                                {roles.data.reduce(
                                    (total, role) => total + role.users_count,
                                    0
                                )}
                            </strong>
                        </div>
                    </div>
                </div>

                {!canManageRoles && (
                    <div className="roles-alert info">
                        You have view-only access to Roles & Permissions.
                    </div>
                )}

                <div className="roles-panel">
                    <div className="roles-panel-heading">
                        <div>
                            <h3>Platform Roles</h3>
                            <p>
                                Review roles, assigned users and permission
                                configurations.
                            </p>
                        </div>

                        <span className="roles-count">
                            {roles.total || 0} roles
                        </span>
                    </div>

                    {roles.data.length === 0 ? (
                        <div className="roles-empty">
                            <span>🛡️</span>
                            <h3>No roles found</h3>
                            <p>No roles are currently available.</p>
                        </div>
                    ) : (
                        <div className="roles-table-scroll">
                            <table className="roles-table">
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th>Type</th>
                                        <th>Permissions</th>
                                        <th>Assigned Users</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {roles.data.map((role) => (
                                        <tr key={role.id}>
                                            <td>
                                                <div className="roles-name-cell">
                                                    <div className="roles-avatar">
                                                        {role.name.charAt(0).toUpperCase()}
                                                    </div>

                                                    <div>
                                                        <strong>
                                                            {formatRoleName(role.name)}
                                                        </strong>
                                                        <small>Role ID: #{role.id}</small>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <span className={`roles-type ${
                                                    role.is_system ? 'system' : 'custom'
                                                }`}>
                                                    {role.is_system
                                                        ? 'System Role'
                                                        : 'Custom Role'}
                                                </span>
                                            </td>

                                            <td>
                                                <span className="roles-number">
                                                    {role.permissions_count}
                                                </span>
                                            </td>

                                            <td>
                                                <span className="roles-number">
                                                    {role.users_count}
                                                </span>
                                            </td>

                                            <td>
                                                <div className="roles-actions">
                                                    <Link
                                                        href={`/roles/${role.id}/edit`}
                                                        className="roles-view-button"
                                                    >
                                                        {canManageRoles
                                                            ? 'Manage'
                                                            : 'View'}
                                                    </Link>

                                                    {canManageRoles &&
                                                        !role.is_system &&
                                                        !role.is_protected && (
                                                            <button
                                                                type="button"
                                                                className="roles-delete-button"
                                                                disabled={role.users_count > 0}
                                                                title={
                                                                    role.users_count > 0
                                                                        ? 'Remove users from this role before deleting it.'
                                                                        : 'Delete role'
                                                                }
                                                                onClick={() => deleteRole(role)}
                                                            >
                                                                Delete
                                                            </button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {roles.links?.length > 3 && (
                        <div className="roles-pagination">
                            <span>
                                Showing {roles.from || 0}–{roles.to || 0}
                                {' '}of {roles.total} roles
                            </span>

                            <div className="roles-pagination-links">
                                {roles.links.map((link, index) => (
                                    <button
                                        key={index}
                                        type="button"
                                        disabled={!link.url || link.active}
                                        className={link.active ? 'active' : ''}
                                        onClick={() =>
                                            link.url && router.visit(link.url)
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                <div className="roles-security-note">
                    <strong>Security notice</strong>
                    <p>
                        The Super Admin role is protected and cannot be edited
                        or deleted through this page. System roles are also
                        protected from deletion.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
