
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Index.css';

export default function Index({ sites = [], statistics = {} }) {
    const { flash } = usePage().props;

    const [generatingSite, setGeneratingSite] = useState(null);
    const [copiedSite, setCopiedSite] = useState(null);

    const generateQr = (site) => {
        if (generatingSite !== null) return;

        setGeneratingSite(site.id);

        router.post(
            `/qr-codes/sites/${site.id}/generate`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setGeneratingSite(null),
            }
        );
    };

    const copyUrl = async (site) => {
        try {
            await navigator.clipboard.writeText(site.portal_url);
            setCopiedSite(site.id);

            setTimeout(() => {
                setCopiedSite(null);
            }, 2000);
        } catch (error) {
            window.prompt('Copy portal URL:', site.portal_url);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="QR Code Management" />

            <div className="qr-page">

                <div className="qr-page-header">
                    <div>
                        <span className="qr-eyebrow">
                            NETWORK MANAGEMENT
                        </span>

                        <h1>QR Code Management</h1>

                        <p>
                            Generate and manage customer portal QR codes
                            for each NACPASI Site.
                        </p>
                    </div>
                </div>

                {flash?.success && (
                    <div className="qr-alert qr-alert-success">
                        {flash.success}
                    </div>
                )}

                <div className="qr-statistics">

                    <div className="qr-stat-card">
                        <span>Total Sites</span>
                        <strong>{statistics.total_sites ?? 0}</strong>
                        <small>Registered Sites</small>
                    </div>

                    <div className="qr-stat-card">
                        <span>Generated QR Codes</span>
                        <strong>{statistics.generated ?? 0}</strong>
                        <small>Active portal QR codes</small>
                    </div>

                    <div className="qr-stat-card">
                        <span>Pending Generation</span>
                        <strong>{statistics.not_generated ?? 0}</strong>
                        <small>Sites without QR codes</small>
                    </div>

                </div>

                <div className="qr-section-heading">
                    <div>
                        <h2>Site Portal QR Codes</h2>
                        <p>
                            Each Site has its own customer portal address.
                        </p>
                    </div>

                    <span className="qr-total-badge">
                        {sites.length} Sites
                    </span>
                </div>

                {sites.length === 0 ? (
                    <div className="qr-empty">
                        <div className="qr-empty-icon">⌕</div>
                        <h3>No Sites Found</h3>
                        <p>
                            Create a Site before generating its portal QR code.
                        </p>
                    </div>
                ) : (
                    <div className="qr-site-grid">

                        {sites.map((site) => (
                            <div className="qr-site-card" key={site.id}>

                                <div className="qr-site-top">
                                    <div className="qr-site-icon">
                                        📍
                                    </div>

                                    <div className="qr-site-info">
                                        <h3>{site.name}</h3>
                                        <span>
                                            {site.code || site.slug}
                                        </span>
                                    </div>

                                    <span
                                        className={`qr-status ${
                                            site.qr_code
                                                ? 'qr-status-active'
                                                : 'qr-status-pending'
                                        }`}
                                    >
                                        {site.qr_code ? 'Generated' : 'Pending'}
                                    </span>
                                </div>

                                <div className="qr-preview">

                                    {site.qr_code ? (
                                        <img
                                            src={site.qr_code.image_url}
                                            alt={`${site.name} Portal QR Code`}
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="qr-placeholder">
                                            <span>▦</span>
                                            <small>QR Code Not Generated</small>
                                        </div>
                                    )}

                                </div>

                                <div className="qr-site-details">
                                    <label>Customer Portal URL</label>

                                    <div className="qr-url-field">
                                        <span>{site.portal_url}</span>

                                        <button
                                            type="button"
                                            onClick={() => copyUrl(site)}
                                            title="Copy portal URL"
                                        >
                                            {copiedSite === site.id
                                                ? 'Copied'
                                                : 'Copy'}
                                        </button>
                                    </div>
                                </div>

                                {site.qr_code && (
                                    <div className="qr-code-meta">
                                        <span>QR Type</span>
                                        <strong>Portal</strong>
                                    </div>
                                )}

                                <div className="qr-card-actions">

                                    {site.qr_code ? (
                                        <>
                                            <a
                                                href={site.qr_code.download_url}
                                                className="qr-download-button"
                                            >
                                                ↓ Download SVG
                                            </a>

                                            <button
                                                type="button"
                                                className="qr-regenerate-button"
                                                onClick={() => generateQr(site)}
                                                disabled={generatingSite !== null}
                                            >
                                                {generatingSite === site.id
                                                    ? 'Generating...'
                                                    : 'Refresh QR'}
                                            </button>
                                        </>
                                    ) : (
                                        <button
                                            type="button"
                                            className="qr-generate-button"
                                            onClick={() => generateQr(site)}
                                            disabled={generatingSite !== null}
                                        >
                                            {generatingSite === site.id
                                                ? 'Generating...'
                                                : '+ Generate Portal QR'}
                                        </button>
                                    )}

                                </div>

                            </div>
                        ))}

                    </div>
                )}

                <div className="qr-information">
                    <div className="qr-information-icon">i</div>

                    <div>
                        <h3>How Portal QR Codes Work</h3>

                        <p>
                            Customers scan the QR code using their phone
                            camera. They are directed to the captive portal
                            for that specific Site, where they can view
                            available WiFi packages and proceed with checkout.
                        </p>

                        <p>
                            Package names and prices are loaded dynamically
                            from NACPASI. You do not need to generate a new
                            QR code whenever package prices change.
                        </p>
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}
