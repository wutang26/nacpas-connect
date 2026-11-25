
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Permissions.css';

function formatName(value = '') {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatPermission(name = '') {
    return name
        .split('.')
        .map((part) => formatName(part))
        .join(' / ');
}

export default function Index({
    permissions = [],
    canManagePermissions = false,
}) {
    const [search, setSearch] = useState('');
    const [selectedModule, setSelectedModule] = useState('all');

    const modules = useMemo(() => {
        return [...new Set(permissions.map((permission) => permission.module))]
            .sort();
    }, [permissions]);

    const filteredPermissions = useMemo(() => {
        const term = search.trim().toLowerCase();

        return permissions.filter((permission) => {
            const matchesSearch =
                !term ||
                permission.name.toLowerCase().includes(term) ||
                formatPermission(permission.name).toLowerCase().includes(term);

            const matchesModule =
                selectedModule === 'all' ||
                permission.module === selectedModule;

            return matchesSearch && matchesModule;
        });
    }, [permissions, search, selectedModule]);

    const groupedPermissions = useMemo(() => {
        return filteredPermissions.reduce((groups, permission) => {
            if (!groups[permission.module]) {
                groups[permission.module] = [];
            }

            groups[permission.module].push(permission);

            return groups;
        }, {});
    }, [filteredPermissions]);

    const assignedCount = permissions.filter(
        (permission) => permission.roles_count > 0
    ).length;

    return (
        <AuthenticatedLayout>
            <Head title="Permissions Management" />

            <div className="permissions-page">
                <div className="permissions-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <span>Settings</span>
                    <span>/</span>
                    <span>Permissions</span>
                </div>

                <div className="permissions-heading">
                    <div>
                        <h1>Permissions Management</h1>
                        <p>
                            View platform permissions, modules and role
                            assignments.
                        </p>
                    </div>

                    <Link
                        href="/roles"
                        className="permissions-primary-button"
                    >
                        Manage Roles
                    </Link>
                </div>

                <div className="permissions-stats">
                    <div className="permissions-stat-card">
                        <span>Total Permissions</span>
                        <strong>{permissions.length}</strong>
                        <small>Registered platform permissions</small>
                    </div>

                    <div className="permissions-stat-card">
                        <span>Modules</span>
                        <strong>{modules.length}</strong>
                        <small>Permission categories</small>
                    </div>

                    <div className="permissions-stat-card">
                        <span>Assigned Permissions</span>
                        <strong>{assignedCount}</strong>
                        <small>Used by at least one role</small>
                    </div>

                    <div className="permissions-stat-card">
                        <span>Unassigned Permissions</span>
                        <strong>{permissions.length - assignedCount}</strong>
                        <small>Not assigned to any role</small>
                    </div>
                </div>

                <div className="permissions-toolbar">
                    <div className="permissions-search">
                        <span>⌕</span>
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search permissions..."
                        />
                    </div>

                    <select
                        value={selectedModule}
                        onChange={(event) =>
                            setSelectedModule(event.target.value)
                        }
                    >
                        <option value="all">All Modules</option>
                        {modules.map((module) => (
                            <option key={module} value={module}>
                                {formatName(module)}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="permissions-results-heading">
                    <div>
                        <h2>Platform Permissions</h2>
                        <p>
                            Showing {filteredPermissions.length} of{' '}
                            {permissions.length} permissions
                        </p>
                    </div>

                    <span className="permissions-readonly-badge">
                        Permission Catalog
                    </span>
                </div>

                {Object.entries(groupedPermissions).map(([module, items]) => (
                    <section
                        className="permissions-module-card"
                        key={module}
                    >
                        <div className="permissions-module-heading">
                            <div>
                                <h3>{formatName(module)}</h3>
                                <p>
                                    {items.length}{' '}
                                    {items.length === 1
                                        ? 'permission'
                                        : 'permissions'}
                                </p>
                            </div>
                        </div>

                        <div className="permissions-table-wrapper">
                            <table className="permissions-table">
                                <thead>
                                    <tr>
                                        <th>Permission</th>
                                        <th>Action</th>
                                        <th>Assigned Roles</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {items.map((permission) => (
                                        <tr key={permission.id}>
                                            <td>
                                                <strong>
                                                    {formatPermission(
                                                        permission.name
                                                    )}
                                                </strong>
                                                <small>
                                                    {permission.name}
                                                </small>
                                            </td>

                                            <td>
                                                <span className="permissions-action">
                                                    {formatName(
                                                        permission.action
                                                    )}
                                                </span>
                                            </td>

                                            <td>
                                                {permission.roles_count}
                                            </td>

                                            <td>
                                                {permission.roles_count > 0 ? (
                                                    <span className="permissions-status assigned">
                                                        Assigned
                                                    </span>
                                                ) : (
                                                    <span className="permissions-status unused">
                                                        Unassigned
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                ))}

                {filteredPermissions.length === 0 && (
                    <div className="permissions-empty">
                        <h3>No permissions found</h3>
                        <p>
                            Try another search term or select a different
                            module.
                        </p>
                    </div>
                )}

                <div className="permissions-security-note">
                    <strong>Security information</strong>
                    <p>
                        Platform permissions are defined by the application.
                        Assignments are managed through Roles & Permissions.
                        Avoid deleting or renaming permissions that are used
                        by application routes and controllers.
                    </p>
                    <Link href="/roles">
                        Open Roles & Permissions →
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
