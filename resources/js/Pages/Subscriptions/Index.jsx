import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

const statusLabel = {
    trialing: 'Trialing',
    active: 'Active',
    past_due: 'Past Due',
    cancelled: 'Cancelled',
    expired: 'Expired',
};

const statusClass = (status) => {
    const allowed = ['trialing', 'active', 'past_due', 'cancelled', 'expired'];
    return allowed.includes(status) ? status : 'inactive';
};

export default function Index({
    subscriptions,
    filters = {},
    statistics = {},
    canManage = false,
}) {
    const { flash = {} } = usePage().props;

    const [search, setSearch] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || '');
    const [billingCycle, setBillingCycle] = useState(filters.billing_cycle || '');

    const applyFilters = (event) => {
        event.preventDefault();

        router.get('/subscriptions', {
            q: search,
            status,
            billing_cycle: billingCycle,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setBillingCycle('');

        router.get('/subscriptions', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const money = (amount, currency = 'TZS') =>
        `${currency} ${Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })}`;

    const formatDate = (value) => {
        if (!value) return '—';

        return new Date(value).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const list = subscriptions?.data || [];
    const paginationLinks = subscriptions?.links || [];

    return (
        <AuthenticatedLayout>
            <Head title="SaaS Subscriptions" />

            <div className="management-page">

                <div className="management-heading">
                    <div>
                        <h1>SaaS Subscriptions</h1>
                        <p>
                            Manage NACPASI customer organizations,
                            subscription plans and billing periods.
                        </p>
                    </div>

                    {canManage && (
                        <Link
                            href="/subscriptions/create"
                            className="management-primary"
                        >
                            ＋ Create Subscription
                        </Link>
                    )}
                </div>

                {flash.success && (
                    <div className="management-alert success">
                        ✓ {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="management-alert error">
                        {flash.error}
                    </div>
                )}

                <div className="management-stats">
                    {[
                        ['🔄', 'Total Subscriptions', statistics.total],
                        ['✓', 'Active', statistics.active],
                        ['◷', 'Trialing', statistics.trialing],
                        ['⚠', 'Needs Attention', statistics.attention],
                    ].map(([icon, label, value]) => (
                        <div className="management-stat" key={label}>
                            <div className="management-stat-icon">{icon}</div>
                            <div>
                                <span>{label}</span>
                                <strong>{value ?? 0}</strong>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Organization Subscriptions</h3>
                            <p>
                                View subscription status, plan, billing
                                amount and renewal period.
                            </p>
                        </div>

                        <span className="management-count">
                            {subscriptions?.total ?? 0} subscriptions
                        </span>
                    </div>

                    <form
                        className="management-filters"
                        onSubmit={applyFilters}
                    >
                        <div className="management-field grow">
                            <label htmlFor="subscription-search">
                                Search
                            </label>

                            <input
                                id="subscription-search"
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Organization, code or plan..."
                            />
                        </div>

                        <div
                            className="management-field"
                            style={{ flex: '0 1 175px' }}
                        >
                            <label htmlFor="subscription-status">Status</label>

                            <select
                                id="subscription-status"
                                value={status}
                                onChange={(event) => setStatus(event.target.value)}
                            >
                                <option value="">All statuses</option>
                                <option value="trialing">Trialing</option>
                                <option value="active">Active</option>
                                <option value="past_due">Past Due</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        <div
                            className="management-field"
                            style={{ flex: '0 1 160px' }}
                        >
                            <label htmlFor="subscription-cycle">
                                Billing Cycle
                            </label>

                            <select
                                id="subscription-cycle"
                                value={billingCycle}
                                onChange={(event) => setBillingCycle(event.target.value)}
                            >
                                <option value="">All cycles</option>
                                <option value="monthly">Monthly</option>
                                <option value="annual">Annual</option>
                            </select>
                        </div>

                        <button type="submit" className="management-primary">
                            Search
                        </button>

                        <button
                            type="button"
                            className="management-secondary"
                            onClick={clearFilters}
                        >
                            Clear
                        </button>
                    </form>

                    {list.length === 0 ? (
                        <div className="management-empty">
                            <div style={{ fontSize: 32, marginBottom: 10 }}>
                                🔄
                            </div>

                            <strong>No SaaS Subscriptions Found</strong>

                            <p>
                                There are no subscriptions matching your search.
                            </p>

                            {canManage && (
                                <Link
                                    href="/subscriptions/create"
                                    className="management-primary"
                                    style={{
                                        display: 'inline-flex',
                                        marginTop: 12,
                                    }}
                                >
                                    ＋ Create First Subscription
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>SaaS Plan</th>
                                        <th>Billing</th>
                                        <th>Amount</th>
                                        <th>Period End</th>
                                        <th>Payments</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {list.map((subscription) => (
                                        <tr key={subscription.id}>
                                            <td>
                                                <strong>
                                                    {subscription.organization?.name
                                                        || 'Unknown Organization'}
                                                </strong>

                                                <div className="management-muted">
                                                    {subscription.organization?.code || '—'}
                                                </div>
                                            </td>

                                            <td>
                                                <strong>
                                                    {subscription.plan?.name || 'Unknown Plan'}
                                                </strong>

                                                <div className="management-muted">
                                                    {subscription.plan?.code || '—'}
                                                </div>
                                            </td>

                                            <td style={{ textTransform: 'capitalize' }}>
                                                {subscription.billing_cycle}
                                            </td>

                                            <td style={{
                                                whiteSpace: 'nowrap',
                                                fontWeight: 700,
                                            }}>
                                                {money(
                                                    subscription.amount,
                                                    subscription.currency
                                                )}
                                            </td>

                                            <td>
                                                {formatDate(subscription.current_period_end)}
                                            </td>

                                            <td>
                                                {subscription.payments_count ?? 0}
                                            </td>

                                            <td>
                                                <span className={`management-status ${statusClass(subscription.status)}`}>
                                                    {statusLabel[subscription.status] || subscription.status}
                                                </span>
                                            </td>

                                            <td>
                                                <div className="management-inline-actions">
                                                    <Link href={`/subscriptions/${subscription.id}`}>
                                                        View
                                                    </Link>

                                                    {canManage && (
                                                        <Link href={`/subscriptions/${subscription.id}/edit`}>
                                                            Edit
                                                        </Link>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {subscriptions?.last_page > 1 && (
                        <div className="management-pagination">
                            <span>
                                Showing {subscriptions.from || 0}–
                                {subscriptions.to || 0} of {subscriptions.total || 0}
                            </span>

                            <div className="management-pagination-links">
                                {paginationLinks.map((link, index) => (
                                    link.url ? (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={link.active ? 'current' : ''}
                                            preserveScroll
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ) : (
                                        <span
                                            key={index}
                                            className="disabled"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    )
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}