import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Index({
    organizations,
    filters = {},
    statistics = {},
    canCreate = false,
    canUpdate = false,
}) {
    const { flash = {} } = usePage().props;
    const [search, setSearch] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || '');

    const applyFilters = (event) => {
        event.preventDefault();
        router.get('/organizations', { q: search, status }, {
            preserveState: true, preserveScroll: true, replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        router.get('/organizations', {}, {
            preserveState: true, preserveScroll: true, replace: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Organizations" />
            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>Organizations</h1>
                        <p>Register and manage NACPASI customer organizations.</p>
                    </div>
                    {canCreate && (
                        <Link href="/organizations/create" className="management-primary">
                            ＋ Create Organization
                        </Link>
                    )}
                </div>

                {flash.success && <div className="management-alert success">✓ {flash.success}</div>}

                <div className="management-stats">
                    {[
                        ['🏢', 'Total Organizations', statistics.total],
                        ['✓', 'Active', statistics.active],
                        ['⏸', 'Inactive', statistics.inactive],
                        ['!', 'Suspended', statistics.suspended],
                    ].map(([icon, label, value]) => (
                        <div className="management-stat" key={label}>
                            <div className="management-stat-icon">{icon}</div>
                            <div><span>{label}</span><strong>{value ?? 0}</strong></div>
                        </div>
                    ))}
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Registered Organizations</h3>
                            <p>Search organizations and review their sites and subscriptions.</p>
                        </div>
                        <span className="management-count">{organizations.total} organizations</span>
                    </div>

                    <form className="management-filters" onSubmit={applyFilters}>
                        <div className="management-field grow">
                            <label htmlFor="org-search">Search</label>
                            <input id="org-search" type="search" value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Name, code, email or phone..." />
                        </div>
                        <div className="management-field" style={{ flex: '0 1 190px' }}>
                            <label htmlFor="org-status">Status</label>
                            <select id="org-status" value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <button className="management-primary" type="submit">Search</button>
                        <button className="management-secondary" type="button" onClick={clearFilters}>Clear</button>
                    </form>

                    {organizations.data.length === 0 ? (
                        <div className="management-empty">No organizations found.</div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>Owner</th>
                                        <th>Sites</th>
                                        <th>Members</th>
                                        <th>Subscriptions</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {organizations.data.map((org) => (
                                        <tr key={org.id}>
                                            <td>
                                                <Link href={`/organizations/${org.id}`}>{org.name}</Link>
                                                <div style={{ color: '#89958d', marginTop: 5 }}>{org.code}</div>
                                            </td>
                                            <td>{org.owner?.name || '—'}</td>
                                            <td>{org.sites_count ?? 0}</td>
                                            <td>{org.users_count ?? 0}</td>
                                            <td>{org.subscriptions_count ?? 0}</td>
                                            <td><span className={`management-status ${org.status}`}>{org.status}</span></td>
                                            <td>
                                                <div className="management-inline-actions">
                                                    <Link href={`/organizations/${org.id}`}>View</Link>
                                                    {canUpdate && <Link href={`/organizations/${org.id}/edit`}>Edit</Link>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="management-pagination">
                        <span>Showing {organizations.from || 0}–{organizations.to || 0} of {organizations.total}</span>
                        <div className="management-pagination-links">
                            {organizations.links.map((link, index) => (
                                link.url ? (
                                    <Link key={index} href={link.url}
                                        className={link.active ? 'current' : ''}
                                        dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span key={index} className="disabled"
                                        dangerouslySetInnerHTML={{ __html: link.label }} />
                                )
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
