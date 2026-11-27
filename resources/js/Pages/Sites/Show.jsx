import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Show({ site, canUpdate = false }) {
    const { flash = {} } = usePage().props;
    const details = [
        ['Site Code', site.code],
        ['Organization', site.organization?.name],
        ['Created By', site.creator?.name],
        ['Creator Email', site.creator?.email],
        ['Region', site.region],
        ['District', site.district],
        ['Address', site.address],
        ['Latitude', site.latitude],
        ['Longitude', site.longitude],
        ['Timezone', site.timezone],
        ['Description', site.description],
    ];

    return (
        <AuthenticatedLayout>
            <Head title={site.name} />
            <div className="management-page">
                <div className="management-heading">
                    <div><h1>{site.name}</h1><p>Site profile, location and WiFi service configuration.</p></div>
                    <div className="management-inline-actions">
                        <Link href="/sites" className="management-secondary">← Sites</Link>
                        {canUpdate && <Link href={`/sites/${site.id}/edit`} className="management-primary">Edit Site</Link>}
                    </div>
                </div>

                {flash.success && <div className="management-alert success">✓ {flash.success}</div>}

                <div className="management-stats">
                    {[
                        ['📦', 'WiFi Packages', site.packages_count],
                        ['🖥️', 'Devices', site.devices_count],
                        ['👥', 'Users', site.users_count],
                        ['▦', 'QR Codes', site.qr_codes_count],
                    ].map(([icon, label, value]) => (
                        <div className="management-stat" key={label}>
                            <div className="management-stat-icon">{icon}</div>
                            <div><span>{label}</span><strong>{value ?? 0}</strong></div>
                        </div>
                    ))}
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div><h3>Site Information</h3><p>Registered location and ownership details.</p></div>
                        <span className={`management-status ${site.status}`}>{site.status}</span>
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
                        <div><h3>WiFi Packages</h3><p>Packages configured for this site.</p></div>
                        <span className="management-count">{(site.packages || []).length} packages</span>
                    </div>
                    {(site.packages || []).length === 0 ? (
                        <div className="management-empty">No WiFi packages have been configured for this site.</div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead><tr><th>Package</th><th>Price</th><th>Duration</th><th>Status</th></tr></thead>
                                <tbody>
                                    {site.packages.map((item) => (
                                        <tr key={item.id}>
                                            <td>{item.name}</td>
                                            <td>{item.currency || 'TZS'} {Number(item.price || 0).toLocaleString('en-TZ')}</td>
                                            <td>{item.duration_minutes ? `${item.duration_minutes} minutes` : '—'}</td>
                                            <td><span className={`management-status ${item.is_active ? 'active' : 'inactive'}`}>{item.is_active ? 'Active' : 'Inactive'}</span></td>
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
