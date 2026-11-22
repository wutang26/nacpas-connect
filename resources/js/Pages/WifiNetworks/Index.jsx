
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './WifiNetworks.css';

function DeleteNetworkButton({ network }) {
    const { delete: destroy, processing } = useForm();

    const handleDelete = () => {
        if (!window.confirm(
            `Una uhakika unataka kufuta WiFi Network "${network.name}"?`
        )) {
            return;
        }

        destroy(`/wifi-networks/${network.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <button
            type="button"
            className="wifi-action-button delete"
            onClick={handleDelete}
            disabled={processing || network.qr_codes_count > 0}
            title={
                network.qr_codes_count > 0
                    ? 'Futa QR Codes zinazohusiana kwanza.'
                    : 'Futa WiFi Network'
            }
        >
            {processing ? 'Inafuta...' : 'Futa'}
        </button>
    );
}

export default function Index({
    sites = [],
    selectedSiteId = null,
    networks = [],
}) {
    const { flash = {}, errors = {} } = usePage().props;

    const activeCount = networks.filter((network) => network.is_active).length;
    const inactiveCount = networks.length - activeCount;

    const handleSiteChange = (event) => {
        router.get(
            '/wifi-networks',
            { site_id: event.target.value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="WiFi Access Management" />

            <div className="wifi-page">
                <div className="wifi-heading">
                    <div>
                        <h1>WiFi Access Management</h1>
                        <p>
                            Sajili na simamia WiFi networks na SSID za hotspot sites zako.
                        </p>
                    </div>

                    {sites.length > 0 && (
                        <Link
                            href={`/wifi-networks/create?site_id=${selectedSiteId}`}
                            className="wifi-primary-button"
                        >
                            <span>＋</span> Sajili WiFi Network
                        </Link>
                    )}
                </div>

                {flash.success && (
                    <div className="wifi-alert success">
                        ✓ {flash.success}
                    </div>
                )}

                {errors.network && (
                    <div className="wifi-alert error">
                        ! {errors.network}
                    </div>
                )}

                <div className="wifi-toolbar">
                    <div className="wifi-site-filter">
                        <label htmlFor="wifi-site">Chagua Site</label>

                        <select
                            id="wifi-site"
                            value={selectedSiteId ?? ''}
                            onChange={handleSiteChange}
                            disabled={sites.length === 0}
                        >
                            {sites.map((site) => (
                                <option key={site.id} value={site.id}>
                                    {site.name}
                                    {site.code ? ` (${site.code})` : ''}
                                </option>
                            ))}
                        </select>
                    </div>

                    <span className="wifi-toolbar-note">
                        WiFi Network Configuration
                    </span>
                </div>

                <div className="wifi-statistics">
                    <div className="wifi-stat-card">
                        <div className="wifi-stat-icon">📶</div>
                        <div>
                            <span>Jumla ya Networks</span>
                            <strong>{networks.length}</strong>
                        </div>
                    </div>

                    <div className="wifi-stat-card">
                        <div className="wifi-stat-icon active">✓</div>
                        <div>
                            <span>Active Networks</span>
                            <strong>{activeCount}</strong>
                        </div>
                    </div>

                    <div className="wifi-stat-card">
                        <div className="wifi-stat-icon inactive">⏸</div>
                        <div>
                            <span>Inactive Networks</span>
                            <strong>{inactiveCount}</strong>
                        </div>
                    </div>
                </div>

                <div className="wifi-table-panel">
                    <div className="wifi-table-heading">
                        <div>
                            <h3>WiFi Networks</h3>
                            <p>
                                SSID zilizohifadhiwa kwenye Site uliyochagua.
                            </p>
                        </div>

                        <span className="wifi-count">
                            {networks.length} networks
                        </span>
                    </div>

                    {sites.length === 0 ? (
                        <div className="wifi-empty">
                            <span>📍</span>
                            <h3>Hakuna Site inayopatikana</h3>
                            <p>
                                Huna ruhusa ya kusimamia WiFi Network kwenye Site yoyote.
                            </p>
                        </div>
                    ) : networks.length === 0 ? (
                        <div className="wifi-empty">
                            <span>📡</span>
                            <h3>Hakuna WiFi Network iliyosajiliwa</h3>
                            <p>
                                Sajili SSID yako ya kwanza kwa Site hii.
                            </p>
                            <Link
                                href={`/wifi-networks/create?site_id=${selectedSiteId}`}
                                className="wifi-primary-button"
                            >
                                Sajili WiFi Network
                            </Link>
                        </div>
                    ) : (
                        <div className="wifi-table-scroll">
                            <table className="wifi-table">
                                <thead>
                                    <tr>
                                        <th>Jina</th>
                                        <th>SSID</th>
                                        <th>Security</th>
                                        <th>Password</th>
                                        <th>QR Codes</th>
                                        <th>Status</th>
                                        <th>Vitendo</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {networks.map((network) => (
                                        <tr key={network.id}>
                                            <td>
                                                <div className="wifi-name-cell">
                                                    <strong>{network.name}</strong>
                                                    <small>ID: {network.id}</small>
                                                </div>
                                            </td>

                                            <td>
                                                <span className="wifi-ssid">
                                                    {network.ssid}
                                                </span>
                                            </td>

                                            <td>
                                                <span className={`wifi-security ${network.security_type}`}>
                                                    {network.security_type === 'wpa2'
                                                        ? 'WPA2'
                                                        : 'Open'}
                                                </span>
                                            </td>

                                            <td>
                                                {network.security_type === 'open'
                                                    ? 'Not required'
                                                    : network.password_configured
                                                        ? 'Configured'
                                                        : 'Not configured'}
                                            </td>

                                            <td>{network.qr_codes_count}</td>

                                            <td>
                                                <span className={`wifi-status ${network.is_active ? 'enabled' : 'disabled'}`}>
                                                    {network.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>

                                            <td>
                                                <div className="wifi-actions">
                                                    <Link
                                                        href={`/wifi-networks/${network.id}/edit`}
                                                        className="wifi-action-button edit"
                                                    >
                                                        Hariri
                                                    </Link>

                                                    <DeleteNetworkButton network={network} />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                <div className="wifi-information">
                    <strong>Maelezo muhimu</strong>
                    <p>
                        Kusajili SSID hapa hakubadilishi configuration ya Omada Controller.
                        Uunganishaji wa moja kwa moja na Omada utaongezwa baada ya kuthibitisha
                        API integration.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
