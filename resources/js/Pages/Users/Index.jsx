import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';
import './Users.css';

export default function Index({
    users,
    filters = {},
    roles = [],
    canManageUsers = false,
}) {
    const {
        flash = {},
        platformSettings = {},
    } = usePage().props;

    const [search, setSearch] = useState(filters.search || '');
    const [selectedRole, setSelectedRole] = useState(filters.role || '');

    const applyFilters = (event) => {
        event.preventDefault();

        router.get(
            '/users',
            {
                search,
                role: selectedRole,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedRole('');

        router.get('/users', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // const formatDate = (date) => {
    //     if (!date) return 'N/A';

    //     const parsedDate = new Date(date);

    //     if (Number.isNaN(parsedDate.getTime())) return 'N/A';

    //     const parts = new Intl.DateTimeFormat('en-GB', {
    //         timeZone: platformSettings.timezone || 'Africa/Dar_es_Salaam',
    //         day: '2-digit',
    //         month: '2-digit',
    //         year: 'numeric',
    //     }).formatToParts(parsedDate);

    //     const values = Object.fromEntries(
    //         parts.map((part) => [part.type, part.value])
    //     );

    //     const dateFormat = platformSettings.date_format || 'd/m/Y';

    //     return dateFormat.replace(/[d m Y]/g, (token) => {
    //         const replacements = {
    //             d: values.day,
    //             m: values.month,
    //             Y: values.year,
    //             ' ': ' ',
    //         };

    //         return replacements[token] ?? token;
    //     });
    // };
        const formatDate = (date) => {
        if (!date) return 'N/A';

        const parsedDate = new Date(date);

        if (Number.isNaN(parsedDate.getTime())) {
            return 'N/A';
        }

        const timezone = platformSettings.timezone || 'Africa/Dar_es_Salaam';

        const dateFormat = platformSettings.date_format || 'd/m/Y';

        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: timezone,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).formatToParts(parsedDate);

        const values = Object.fromEntries(
            parts.map((part) => [part.type, part.value])
        );

        const replacements = {
            d: values.day,
            m: values.month,
            Y: values.year,
        };

        return dateFormat.replace(/d|m|Y/g, (token) => {
            return replacements[token] ?? token;
        });
    };
    
    return (
        <AuthenticatedLayout>
            <Head title="User Management" />

            <div className="users-page">
                <div className="users-heading">
                    <div>
                        <h1>User Management</h1>
                        <p>
                            View and manage NACPASI platform users,
                            roles and permissions.
                        </p>
                    </div>
                </div>

                {flash.success && (
                    <div className="users-alert success">
                        ✓ {flash.success}
                    </div>
                )}

                <div className="users-statistics">
                    <div className="users-stat-card">
                        <div className="users-stat-icon">👥</div>
                        <div>
                            <span>Total Users</span>
                            <strong>{users.total}</strong>
                        </div>
                    </div>

                    <div className="users-stat-card">
                        <div className="users-stat-icon">📄</div>
                        <div>
                            <span>Current Page</span>
                            <strong>{users.current_page}</strong>
                        </div>
                    </div>

                    <div className="users-stat-card">
                        <div className="users-stat-icon">🔐</div>
                        <div>
                            <span>Available Roles</span>
                            <strong>{roles.length}</strong>
                        </div>
                    </div>
                </div>

                <div className="users-panel">
                    <div className="users-panel-heading">
                        <div>
                            <h3>Registered Users</h3>
                            <p>
                                Search users and view their account details.
                            </p>
                        </div>

                        <span className="users-count">
                            {users.total} users
                        </span>
                    </div>

                    <form className="users-filters" onSubmit={applyFilters}>
                        <div className="users-filter-field search-field">
                            <label htmlFor="user-search">Search users</label>
                            <input
                                id="user-search"
                                type="search"
                                placeholder="Search by name or email..."
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                            />
                        </div>

                        <div className="users-filter-field">
                            <label htmlFor="user-role-filter">Filter by role</label>
                            <select
                                id="user-role-filter"
                                value={selectedRole}
                                onChange={(event) => setSelectedRole(event.target.value)}
                            >
                                <option value="">All available roles</option>

                                {roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {role.name.replaceAll('_', ' ')}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="users-filter-actions">
                            <button type="submit" className="users-primary-button">
                                Search
                            </button>

                            <button
                                type="button"
                                className="users-secondary-button"
                                onClick={clearFilters}
                            >
                                Clear
                            </button>
                        </div>
                    </form>

                    {users.data.length === 0 ? (
                        <div className="users-empty">
                            <span>👥</span>
                            <h3>No users found</h3>
                            <p>
                                No users match your search criteria.
                            </p>
                        </div>
                    ) : (
                        <div className="users-table-scroll">
                            <table className="users-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Assigned Role</th>
                                        <th>Registered</th>
                                        <th>Email Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {users.data.map((user) => (
                                        <tr key={user.id}>
                                            <td>
                                                <div className="users-name-cell">
                                                    <div className="users-avatar">
                                                        {user.name?.charAt(0)?.toUpperCase()}
                                                    </div>

                                                    <div>
                                                        <strong>{user.name}</strong>
                                                        <small>#{user.id}</small>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>{user.email}</td>

                                            <td>
                                                <div className="users-role-list">
                                                    {user.roles?.length > 0
                                                        ? user.roles.map((role) => (
                                                            <span
                                                                className="users-role-badge"
                                                                key={role}
                                                            >
                                                                {role.replaceAll('_', ' ')}
                                                            </span>
                                                        ))
                                                        : <span className="users-muted">No role</span>
                                                    }
                                                </div>
                                            </td>

                                            <td>{formatDate(user.created_at)}</td>

                                            <td>
                                                <span className={`users-email-status ${
                                                    user.email_verified_at ? 'verified' : 'unverified'
                                                }`}>
                                                    {user.email_verified_at
                                                        ? 'Verified'
                                                        : 'Not verified'}
                                                </span>
                                            </td>

                                            <td>
                                                <Link
                                                    href={`/users/${user.id}`}
                                                    className="users-view-button"
                                                >
                                                    View User
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {users.links?.length > 3 && (
                        <div className="users-pagination">
                            <span>
                                Showing {users.from || 0}–{users.to || 0}
                                {' '}of {users.total} users
                            </span>

                            <div className="users-pagination-links">
                                {users.links.map((link, index) => (
                                    <button
                                        key={index}
                                        type="button"
                                        disabled={!link.url || link.active}
                                        className={link.active ? 'active' : ''}
                                        onClick={() => link.url && router.visit(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {!canManageUsers && (
                    <p className="users-readonly-note">
                        You have view-only access to user accounts.
                    </p>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
