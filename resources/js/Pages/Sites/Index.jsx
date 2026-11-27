import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Index({
    sites,
    organizations = [],
    filters = {},
    statistics = {},
    canCreate = false,
    canUpdate = false,
}) {
    const { flash = {} } = usePage().props;
    const [search, setSearch] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || '');
    const [organizationId, setOrganizationId] = useState(filters.organization_id || '');

    const applyFilters = (event) => {
        event.preventDefault();
        router.get('/sites', { q: search, status, organization_id: organizationId }, {
            preserveState: true, preserveScroll: true, replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setOrganizationId('');
        router.get('/sites', {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Sites" />
            <div className="management-page">
                <div className="management-heading">
                    <div><h1>Sites</h1><p>Manage physical WiFi locations belonging to customer organizations.</p></div>
                    {canCreate && <Link href="/sites/create" className="management-primary">＋ Register Site</Link>}
                </div>

                {flash.success && <div className="management-alert success">✓ {flash.success}</div>}

                <div className="management-stats">
                    {[
                        ['📍', 'Total Sites', statistics.total],
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
                        <div><h3>Registered Sites</h3><p>Search sites by name, code, region or organization.</p></div>
                        <span className="management-count">{sites.total} sites</span>
                    </div>

                    <form className="management-filters" onSubmit={applyFilters}>
                        <div className="management-field grow">
                            <label htmlFor="site-search">Search</label>
                            <input id="site-search" type="search" value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Site name, code, region..." />
                        </div>
                        <div className="management-field" style={{ flex: '0 1 190px' }}>
                            <label htmlFor="site-org">Organization</label>
                            <select id="site-org" value={organizationId} onChange={(e) => setOrganizationId(e.target.value)}>
                                <option value="">All organizations</option>
                                {organizations.map((org) => <option key={org.id} value={org.id}>{org.name}</option>)}
                            </select>
                        </div>
                        <div className="management-field" style={{ flex: '0 1 150px' }}>
                            <label htmlFor="site-status">Status</label>
                            <select id="site-status" value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <button type="submit" className="management-primary">Search</button>
                        <button type="button" className="management-secondary" onClick={clearFilters}>Clear</button>
                    </form>

                    {sites.data.length === 0 ? (
                        <div className="management-empty">No sites found.</div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead><tr><th>Site</th><th>Organization</th><th>Region</th><th>Packages</th><th>Devices</th><th>Users</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                    {sites.data.map((site) => (
                                        <tr key={site.id}>
                                            <td><Link href={`/sites/${site.id}`}>{site.name}</Link><div style={{ color: '#89958d', marginTop: 5 }}>{site.code}</div></td>
                                            <td>{site.organization?.name || '—'}</td>
                                            <td>{site.region || '—'}</td>
                                            <td>{site.packages_count ?? 0}</td>
                                            <td>{site.devices_count ?? 0}</td>
                                            <td>{site.users_count ?? 0}</td>
                                            <td><span className={`management-status ${site.status}`}>{site.status}</span></td>
                                            <td><div className="management-inline-actions"><Link href={`/sites/${site.id}`}>View</Link>{canUpdate && <Link href={`/sites/${site.id}/edit`}>Edit</Link>}</div></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <div className="management-pagination">
                        <span>Showing {sites.from || 0}–{sites.to || 0} of {sites.total}</span>
                        <div className="management-pagination-links">
                            {sites.links.map((link, index) => link.url ? (
                                <Link key={index} href={link.url} className={link.active ? 'current' : ''}
                                    dangerouslySetInnerHTML={{ __html: link.label }} />
                            ) : (
                                <span key={index} className="disabled" dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
