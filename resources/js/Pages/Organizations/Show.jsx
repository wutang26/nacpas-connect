import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Show({ organization, canUpdate = false }) {
    const { flash = {} } = usePage().props;
    const sites = organization.sites || [];

    const details = [
        ['Organization Code', organization.code],
        ['Owner', organization.owner?.name],
        ['Owner Email', organization.owner?.email],
        ['Business Email', organization.business_email],
        ['Business Phone', organization.business_phone],
        ['Billing Email', organization.billing_email],
        ['Region', organization.region],
        ['District', organization.district],
        ['Address', organization.address],
        ['Timezone', organization.timezone],
        ['Created', organization.created_at ? new Date(organization.created_at).toLocaleDateString('en-GB') : '—'],
    ];

    return (
        <AuthenticatedLayout>
            <Head title={organization.name} />
            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>{organization.name}</h1>
                        <p>Organization profile, registered sites and account information.</p>
                    </div>
                    <div className="management-inline-actions">
                        <Link href="/organizations" className="management-secondary">← Organizations</Link>
                        {canUpdate && <Link href={`/organizations/${organization.id}/edit`} className="management-primary">Edit Organization</Link>}
                    </div>
                </div>

                {flash.success && <div className="management-alert success">✓ {flash.success}</div>}

                <div className="management-stats">
                    {[
                        ['📍', 'Sites', organization.sites_count],
                        ['👥', 'Members', organization.users_count],
                        ['🔄', 'Subscriptions', organization.subscriptions_count],
                        ['●', 'Status', organization.status],
                    ].map(([icon, label, value]) => (
                        <div className="management-stat" key={label}>
                            <div className="management-stat-icon">{icon}</div>
                            <div><span>{label}</span><strong style={{ textTransform: label === 'Status' ? 'capitalize' : 'none' }}>{value ?? 0}</strong></div>
                        </div>
                    ))}
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div><h3>Organization Information</h3><p>Registered business and account details.</p></div>
                        <span className={`management-status ${organization.status}`}>{organization.status}</span>
                    </div>
                    <div className="management-detail-grid">
                        {details.map(([label, value]) => (
                            <div className="management-detail" key={label}>
                                <span>{label}</span><strong>{value || '—'}</strong>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div><h3>Organization Sites</h3><p>WiFi locations registered under this organization.</p></div>
                        <span className="management-count">{sites.length} sites</span>
                    </div>
                    {sites.length === 0 ? (
                        <div className="management-empty">No sites have been registered for this organization.</div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead><tr><th>Site</th><th>Code</th><th>Region</th><th>Packages</th><th>Devices</th><th>QR Codes</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                    {sites.map((site) => (
                                        <tr key={site.id}>
                                            <td><Link href={`/sites/${site.id}`}>{site.name}</Link></td>
                                            <td>{site.code}</td>
                                            <td>{site.region || '—'}</td>
                                            <td>{site.packages_count ?? 0}</td>
                                            <td>{site.devices_count ?? 0}</td>
                                            <td>{site.qr_codes_count ?? 0}</td>
                                            <td><span className={`management-status ${site.status}`}>{site.status}</span></td>
                                            <td><Link href={`/sites/${site.id}`}>View</Link></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
