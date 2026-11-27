import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading } from './VoucherUI';
import './Vouchers.css';

const numberFormat = new Intl.NumberFormat('en-US');

function number(value) {
    return numberFormat.format(Number(value || 0));
}

function money(value, currency = 'TZS') {
    const amount = Number(value || 0);

    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency || 'TZS',
            maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${currency || 'TZS'} ${amount.toLocaleString()}`;
    }
}

function formatDate(value) {
    if (!value) return '—';

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return '—';

    return new Intl.DateTimeFormat('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Africa/Dar_es_Salaam',
    }).format(parsed);
}

function Status({ value }) {
    const status = String(value || 'unknown').toLowerCase();

    return (
        <span className={`v-status v-status-${status}`}>
            {status.replaceAll('_', ' ')}
        </span>
    );
}

function Metric({ title, value, description, icon }) {
    return (
        <div className="v-stat">
            <span className="v-stat-icon">{icon}</span>

            <div>
                <span>{title}</span>
                <strong>{number(value)}</strong>
                <small>{description}</small>
            </div>
        </div>
    );
}

function RevenueList({ title, description, items }) {
    return (
        <section className="v-card">
            <h3>{title}</h3>
            <p>{description}</p>

            {items?.length ? (
                <div className="vd-revenue-list">
                    {items.map((item) => (
                        <div
                            className="vd-revenue-row"
                            key={item.currency}
                        >
                            <span>{item.currency}</span>
                            <strong>
                                {money(item.amount, item.currency)}
                            </strong>
                        </div>
                    ))}
                </div>
            ) : (
                <p className="v-muted">No transactions recorded yet.</p>
            )}
        </section>
    );
}

export default function Dashboard({
    statistics = {},
    wholesaleRevenue = [],
    redemptionRetailValue = [],
    siteStatistics = [],
    recentPurchases = [],
    recentRedemptions = [],
}) {
    const quickLinks = [
        {
            label: 'Create Voucher Batch',
            description: 'Generate new voucher stock.',
            icon: '🎟️',
            href: '/vouchers/batches/create',
        },
        {
            label: 'Manage Batches',
            description: 'View and manage generated batches.',
            icon: '📦',
            href: '/vouchers/batches',
        },
        {
            label: 'Manage Agents',
            description: 'Register and manage voucher agents.',
            icon: '👥',
            href: '/vouchers/agents',
        },
        {
            label: 'Agent Purchases',
            description: 'Review and confirm purchase requests.',
            icon: '🧾',
            href: '/vouchers/purchases',
        },
        {
            label: 'Voucher Reports',
            description: 'Review voucher redemption records.',
            icon: '📊',
            href: '/vouchers/reports',
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Voucher Management Dashboard" />

            <main className="v-page vd-dashboard">
                <Breadcrumb
                    items={[
                        { label: 'Voucher Management' },
                        { label: 'Dashboard' },
                    ]}
                />

                <div className="vd-heading">
                    <div>
                        <span className="v-eyebrow">
                            NACPASI PLATFORM OPERATIONS
                        </span>

                        <h1>Voucher Management Dashboard</h1>

                        <p>
                            Platform-wide overview of voucher inventory,
                            agent operations, sales and customer redemptions.
                        </p>
                    </div>

                    <div className="vd-heading-icon">🎟️</div>
                </div>

                <div className="vd-notice">
                    <span>🛡️</span>
                    <div>
                        <strong>Super Administrator Overview</strong>
                        <p>
                            These figures cover all sites registered
                            in the NACPASI platform.
                        </p>
                    </div>
                </div>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Voucher Inventory</h2>
                            <p>Generated voucher stock and its current status.</p>
                        </div>
                    </div>

                    <div className="v-stats">
                        <Metric
                            title="Total Vouchers"
                            value={statistics.total_vouchers}
                            description="All generated vouchers"
                            icon="🎟️"
                        />
                        <Metric
                            title="Available"
                            value={statistics.available_vouchers}
                            description="Ready for agent purchase"
                            icon="🟢"
                        />
                        <Metric
                            title="Assigned"
                            value={statistics.assigned_vouchers}
                            description="Issued to agents"
                            icon="📤"
                        />
                        <Metric
                            title="Redeemed"
                            value={statistics.redeemed_vouchers}
                            description="Used by customers"
                            icon="✅"
                        />
                        <Metric
                            title="Void"
                            value={statistics.void_vouchers}
                            description="Invalidated vouchers"
                            icon="🚫"
                        />
                    </div>
                </section>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Agent Operations</h2>
                            <p>Agent registration and voucher purchase activity.</p>
                        </div>
                    </div>

                    <div className="v-stats">
                        <Metric
                            title="Total Agents"
                            value={statistics.total_agents}
                            description="Registered agents"
                            icon="👥"
                        />
                        <Metric
                            title="Active Agents"
                            value={statistics.active_agents}
                            description="Currently active"
                            icon="🟢"
                        />
                        <Metric
                            title="Suspended Agents"
                            value={statistics.suspended_agents}
                            description="Temporarily suspended"
                            icon="⛔"
                        />
                        <Metric
                            title="Pending Purchases"
                            value={statistics.pending_purchases}
                            description="Awaiting confirmation"
                            icon="⏳"
                        />
                        <Metric
                            title="Paid Purchases"
                            value={statistics.paid_purchases}
                            description="Confirmed purchases"
                            icon="💳"
                        />
                    </div>
                </section>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Voucher Business Summary</h2>
                            <p>
                                Financial totals are separated by currency.
                                Retail value is not the same as realised profit.
                            </p>
                        </div>
                    </div>

                    <div className="v-card-grid">
                        <RevenueList
                            title="Wholesale Revenue"
                            description="Total value of confirmed paid agent purchases."
                            items={wholesaleRevenue}
                        />

                        <RevenueList
                            title="Redeemed Retail Value"
                            description="Retail value of vouchers already redeemed by customers."
                            items={redemptionRetailValue}
                        />
                    </div>
                </section>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Site Performance</h2>
                            <p>
                                Voucher operations across all registered sites.
                            </p>
                        </div>
                    </div>

                    <div className="v-panel">
                        <div className="v-table-wrap">
                            <table className="v-table">
                                <thead>
                                    <tr>
                                        <th>Site</th>
                                        <th>Batches</th>
                                        <th>Vouchers</th>
                                        <th>Available</th>
                                        <th>Assigned</th>
                                        <th>Redeemed</th>
                                        <th>Active Agents</th>
                                        <th>Pending Purchases</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {siteStatistics.length ? (
                                        siteStatistics.map((site) => (
                                            <tr key={site.id}>
                                                <td>
                                                    <strong>{site.name}</strong>
                                                </td>
                                                <td>{number(site.total_batches)}</td>
                                                <td>{number(site.total_vouchers)}</td>
                                                <td>{number(site.available)}</td>
                                                <td>{number(site.assigned)}</td>
                                                <td>{number(site.redeemed)}</td>
                                                <td>{number(site.active_agents)}</td>
                                                <td>{number(site.pending_purchases)}</td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="8">
                                                <div className="v-empty">
                                                    No sites found.
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Recent Agent Purchases</h2>
                            <p>Latest voucher stock purchase requests.</p>
                        </div>

                        <Link
                            href="/vouchers/purchases"
                            className="v-button v-button-light"
                        >
                            View all purchases
                        </Link>
                    </div>

                    <div className="v-panel">
                        <div className="v-table-wrap">
                            <table className="v-table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Agent</th>
                                        <th>Site</th>
                                        <th>Batch</th>
                                        <th>Quantity</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {recentPurchases.length ? (
                                        recentPurchases.map((purchase) => (
                                            <tr key={purchase.id}>
                                                <td>
                                                    <strong>{purchase.reference}</strong>
                                                </td>
                                                <td>{purchase.agent?.user?.name || '—'}</td>
                                                <td>{purchase.site?.name || '—'}</td>
                                                <td>{purchase.batch?.batch_number || '—'}</td>
                                                <td>{number(purchase.quantity)}</td>
                                                <td>
                                                    {money(
                                                        purchase.total_amount,
                                                        purchase.currency
                                                    )}
                                                </td>
                                                <td><Status value={purchase.status} /></td>
                                                <td>{formatDate(purchase.created_at)}</td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="8">
                                                <div className="v-empty">
                                                    No agent purchases recorded.
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Recent Redemptions</h2>
                            <p>Latest vouchers used by WiFi customers.</p>
                        </div>

                        <Link
                            href="/vouchers/reports"
                            className="v-button v-button-light"
                        >
                            Open reports
                        </Link>
                    </div>

                    <div className="v-panel">
                        <div className="v-table-wrap">
                            <table className="v-table">
                                <thead>
                                    <tr>
                                        <th>Site</th>
                                        <th>Package</th>
                                        <th>Agent</th>
                                        <th>Customer</th>
                                        <th>Device MAC</th>
                                        <th>Redeemed At</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {recentRedemptions.length ? (
                                        recentRedemptions.map((redemption) => (
                                            <tr key={redemption.id}>
                                                <td>{redemption.site?.name || '—'}</td>
                                                <td>{redemption.package?.name || '—'}</td>
                                                <td>
                                                    {redemption.agent?.user?.name || 'Direct Sale'}
                                                </td>
                                                <td>{redemption.customer?.name || '—'}</td>
                                                <td>{redemption.client_mac || '—'}</td>
                                                <td>{formatDate(redemption.redeemed_at)}</td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="6">
                                                <div className="v-empty">
                                                    No voucher redemptions recorded.
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section className="vd-section">
                    <div className="vd-section-heading">
                        <div>
                            <h2>Quick Management Links</h2>
                            <p>Direct access to voucher administration modules.</p>
                        </div>
                    </div>

                    <div className="vd-quick-grid">
                        {quickLinks.map((item) => (
                            <Link
                                href={item.href}
                                className="vd-quick-card"
                                key={item.href}
                            >
                                <span>{item.icon}</span>
                                <div>
                                    <strong>{item.label}</strong>
                                    <p>{item.description}</p>
                                </div>
                                <span className="vd-quick-arrow">→</span>
                            </Link>
                        ))}
                    </div>
                </section>
            </main>
        </AuthenticatedLayout>
    );
}
