
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './CaptivePortalSessions.css';

const contextLabels = {
    captured: 'Captured',
    attached: 'Attached',
    consumed: 'Consumed',
    expired: 'Expired',
};

const authorizationLabels = {
    not_started: 'Not Started',
    queued: 'Queued',
    authorizing: 'Authorizing',
    authorized: 'Authorized',
    failed: 'Failed',
};

function StatusBadge({ status, type = 'context' }) {
    const value = status || 'unknown';

    const label = type === 'authorization'
        ? authorizationLabels[value] || value
        : contextLabels[value] || value;

    return (
        <span className={`cps-status cps-status-${value}`}>
            <span className="cps-status-dot" />
            {label}
        </span>
    );
}

function formatDate(value) {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '—';

    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function SummaryCard({ icon, label, value, description, color }) {
    return (
        <div className="cps-summary-card">
            <div className={`cps-summary-icon cps-icon-${color}`}>
                {icon}
            </div>

            <div className="cps-summary-content">
                <span>{label}</span>
                <strong>{Number(value || 0).toLocaleString()}</strong>
                <small>{description}</small>
            </div>
        </div>
    );
}

export default function Index({
    contexts = {},
    statistics = {},
    sites = [],
    filters = {},
}) {
    const [search, setSearch] = useState(filters.q || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [contextStatus, setContextStatus] = useState(
        filters.context_status || ''
    );
    const [authorizationStatus, setAuthorizationStatus] = useState(
        filters.authorization_status || ''
    );

    const stats = {
        total: 0,
        captured: 0,
        attached: 0,
        consumed: 0,
        expired: 0,
        authorized: 0,
        pending: 0,
        failed: 0,
        ...statistics,
    };

    const records = Array.isArray(contexts.data) ? contexts.data : [];
    const paginationLinks = Array.isArray(contexts.links)
        ? contexts.links
        : [];

    const applyFilters = (event) => {
        event.preventDefault();

        router.get('/captive-portal-sessions', {
            q: search,
            site_id: siteId,
            context_status: contextStatus,
            authorization_status: authorizationStatus,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setSiteId('');
        setContextStatus('');
        setAuthorizationStatus('');

        router.get('/captive-portal-sessions', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const visitPage = (url) => {
        if (!url) return;

        router.get(url, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Captive Portal Sessions" />

            <div className="cps-page">

                {/* BREADCRUMB */}
                <div className="cps-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/network-connections">
                        Network Management
                    </Link>
                    <span>/</span>
                    <strong>Captive Portal Sessions</strong>
                </div>

                {/* PAGE HEADING */}
                <div className="cps-heading">
                    <div>
                        <span className="cps-eyebrow">
                            NETWORK MANAGEMENT
                        </span>

                        <h1>Captive Portal Sessions</h1>

                        <p>
                            Monitor devices connecting through your Omada
                            captive portal and track their authorization status.
                        </p>
                    </div>

                    <div className="cps-heading-icon">📡</div>
                </div>

                {/* INFORMATION NOTICE */}
                <div className="cps-notice">
                    <span>ⓘ</span>

                    <p>
                        This dashboard displays captured client and access
                        point information from the Omada portal. Authorization
                        is tracked separately from context capture.
                    </p>
                </div>

                {/* SUMMARY CARDS */}
                <div className="cps-summary">
                    <SummaryCard
                        icon="📡"
                        label="Total Contexts"
                        value={stats.total}
                        description="Captured portal records"
                        color="green"
                    />

                    <SummaryCard
                        icon="✓"
                        label="Authorized"
                        value={stats.authorized}
                        description="Successfully authorized"
                        color="blue"
                    />

                    <SummaryCard
                        icon="◷"
                        label="Pending"
                        value={stats.pending}
                        description="Awaiting authorization"
                        color="orange"
                    />

                    <SummaryCard
                        icon="!"
                        label="Failed"
                        value={stats.failed}
                        description="Authorization failures"
                        color="red"
                    />

                    <SummaryCard
                        icon="⌛"
                        label="Expired"
                        value={stats.expired}
                        description="Expired portal contexts"
                        color="grey"
                    />
                </div>

                {/* CONTEXT LIFECYCLE */}
                <div className="cps-status-overview">
                    <div>
                        <span className="cps-overview-label">
                            CONTEXT LIFECYCLE
                        </span>

                        <div className="cps-overview-items">
                            <span>
                                Captured <strong>{stats.captured}</strong>
                            </span>

                            <span>
                                Attached <strong>{stats.attached}</strong>
                            </span>

                            <span>
                                Consumed <strong>{stats.consumed}</strong>
                            </span>

                            <span>
                                Expired <strong>{stats.expired}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                {/* RECORDS PANEL */}
                <div className="cps-panel">

                    <div className="cps-panel-heading">
                        <div>
                            <h2>Portal Connection Records</h2>

                            <p>
                                Client identity, access point and authorization
                                information.
                            </p>
                        </div>

                        <span className="cps-record-count">
                            {contexts.total || 0} records
                        </span>
                    </div>

                    {/* FILTERS */}
                    <form
                        className="cps-filters"
                        onSubmit={applyFilters}
                    >
                        <div className="cps-filter-field cps-search-field">
                            <label>Search device</label>

                            <input
                                type="text"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="MAC address, IP address or SSID..."
                            />
                        </div>

                        <div className="cps-filter-field">
                            <label>Site</label>

                            <select
                                value={siteId}
                                onChange={(event) =>
                                    setSiteId(event.target.value)
                                }
                            >
                                <option value="">All Sites</option>

                                {sites.map((site) => (
                                    <option key={site.id} value={site.id}>
                                        {site.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="cps-filter-field">
                            <label>Context Status</label>

                            <select
                                value={contextStatus}
                                onChange={(event) =>
                                    setContextStatus(event.target.value)
                                }
                            >
                                <option value="">All Contexts</option>
                                <option value="captured">Captured</option>
                                <option value="attached">Attached</option>
                                <option value="consumed">Consumed</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        <div className="cps-filter-field">
                            <label>Authorization</label>

                            <select
                                value={authorizationStatus}
                                onChange={(event) =>
                                    setAuthorizationStatus(event.target.value)
                                }
                            >
                                <option value="">All Statuses</option>
                                <option value="not_started">Not Started</option>
                                <option value="queued">Queued</option>
                                <option value="authorizing">Authorizing</option>
                                <option value="authorized">Authorized</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <div className="cps-filter-actions">
                            <button
                                type="submit"
                                className="cps-filter-button"
                            >
                                Search
                            </button>

                            <button
                                type="button"
                                className="cps-clear-button"
                                onClick={clearFilters}
                            >
                                Clear
                            </button>
                        </div>
                    </form>

                    {/* CONNECTION TABLE */}
                    <div className="cps-table-wrapper">
                        <table className="cps-table">
                            <thead>
                                <tr>
                                    <th>CLIENT DEVICE</th>
                                    <th>ACCESS POINT</th>
                                    <th>NETWORK</th>
                                    <th>ORGANIZATION / SITE</th>
                                    <th>CONTEXT</th>
                                    <th>AUTHORIZATION</th>
                                    <th>CAPTURED AT</th>
                                    <th>EXPIRES AT</th>
                                </tr>
                            </thead>

                            <tbody>
                                {records.length > 0 ? (
                                    records.map((context) => (
                                        <tr key={context.id}>
                                            <td>
                                                <div className="cps-device-cell">
                                                    <strong>
                                                        {context.client_mac || 'Unknown MAC'}
                                                    </strong>

                                                    <span>
                                                        IP: {context.client_ip || '—'}
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <div className="cps-device-cell">
                                                    <strong>
                                                        {context.ap_mac || '—'}
                                                    </strong>

                                                    <span>
                                                        Radio: {context.radio_id || '—'}
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <div className="cps-device-cell">
                                                    <strong>
                                                        {context.ssid_name || 'Unknown SSID'}
                                                    </strong>

                                                    <span>Omada network</span>
                                                </div>
                                            </td>

                                            <td>
                                                <div className="cps-device-cell">
                                                    <strong>
                                                        {context.organization || '—'}
                                                    </strong>

                                                    <span>
                                                        {context.site || '—'}
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <StatusBadge
                                                    status={context.context_status}
                                                />
                                            </td>

                                            <td>
                                                <StatusBadge
                                                    status={context.authorization_status}
                                                    type="authorization"
                                                />

                                                <small className="cps-attempts">
                                                    Attempts: {context.authorization_attempts ?? 0}
                                                </small>
                                            </td>

                                            <td className="cps-date-cell">
                                                {formatDate(context.created_at)}
                                            </td>

                                            <td className="cps-date-cell">
                                                {formatDate(context.expires_at)}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8">
                                            <div className="cps-empty">
                                                <span>📡</span>

                                                <strong>
                                                    No portal sessions found
                                                </strong>

                                                <p>
                                                    Captured Omada portal
                                                    connections will appear here.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* TABLE FOOTER */}
                    <div className="cps-table-footer">
                        <span>
                            Showing {contexts.from || 0}–{contexts.to || 0}
                            {' '}of {contexts.total || 0} records
                        </span>

                        {contexts.last_page > 1 && (
                            <div className="cps-pagination">
                                {paginationLinks.map((link, index) => (
                                    <button
                                        key={`${index}-${link.label}`}
                                        type="button"
                                        disabled={!link.url || link.active}
                                        className={link.active ? 'active' : ''}
                                        onClick={() => visitPage(link.url)}
                                    >
                                        {link.label
                                            .replace(/&laquo;/g, '«')
                                            .replace(/&raquo;/g, '»')
                                        }
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
