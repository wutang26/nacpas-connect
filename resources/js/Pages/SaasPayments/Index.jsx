import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Index({
    payments,
    filters = {},
    statistics = {},
    providers = [],
}) {
    const [search, setSearch] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || '');
    const [provider, setProvider] = useState(filters.provider || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const paymentList = payments?.data || [];
    const paginationLinks = payments?.links || [];

    const money = (amount, currency = 'TZS') => {
        return `${currency} ${Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })}`;
    };

    const applyFilters = (event) => {
        event.preventDefault();

        router.get('/saas-payments', {
            q: search,
            status,
            provider,
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setProvider('');
        setDateFrom('');
        setDateTo('');

        router.get('/saas-payments', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const formatDate = (value) => {
        if (!value) return '—';

        return new Date(value).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const statusLabel = (value) => {
        return String(value || 'unknown')
            .replaceAll('_', ' ');
    };

    return (
        <AuthenticatedLayout>
            <Head title="SaaS Payments" />

            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>SaaS Payments</h1>
                        <p>
                            Monitor payments made by organizations for
                            NACPASI platform subscriptions.
                        </p>
                    </div>
                </div>

                <div className="management-stats">
                    {[
                        ['💳', 'Total Transactions', statistics.total],
                        ['✓', 'Successful', statistics.successful],
                        ['⏳', 'Pending', statistics.pending],
                        ['⚠', 'Failed / Cancelled', statistics.failed],
                    ].map(([icon, label, value]) => (
                        <div className="management-stat" key={label}>
                            <div className="management-stat-icon">{icon}</div>
                            <div>
                                <span>{label}</span>
                                <strong>{Number(value || 0).toLocaleString('en-US')}</strong>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Successful SaaS Revenue</h3>
                            <p>
                                Total value of payments verified as successful.
                            </p>
                        </div>

                        <strong style={{
                            color: '#218653',
                            fontSize: 20,
                        }}>
                            {money(statistics.revenue)}
                        </strong>
                    </div>

                    <div className="management-panel-heading">
                        <div>
                            <h3>Payment Transactions</h3>
                            <p>
                                Search and review organization payment records.
                            </p>
                        </div>

                        <span className="management-count">
                            {payments?.total ?? 0} transactions
                        </span>
                    </div>

                    <form
                        className="management-filters"
                        onSubmit={applyFilters}
                    >
                        <div className="management-field grow">
                            <label htmlFor="payment-search">Search</label>
                            <input
                                id="payment-search"
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Reference, organization, phone..."
                            />
                        </div>

                        <div className="management-field" style={{ flex: '0 1 150px' }}>
                            <label htmlFor="payment-status">Status</label>
                            <select
                                id="payment-status"
                                value={status}
                                onChange={(event) => setStatus(event.target.value)}
                            >
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="successful">Successful</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>

                        <div className="management-field" style={{ flex: '0 1 150px' }}>
                            <label htmlFor="payment-provider">Provider</label>
                            <select
                                id="payment-provider"
                                value={provider}
                                onChange={(event) => setProvider(event.target.value)}
                            >
                                <option value="">All providers</option>
                                {providers.map((item) => (
                                    <option key={item} value={item}>
                                        {item}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="management-field" style={{ flex: '0 1 150px' }}>
                            <label htmlFor="payment-date-from">Date From</label>
                            <input
                                id="payment-date-from"
                                type="date"
                                value={dateFrom}
                                onChange={(event) => setDateFrom(event.target.value)}
                            />
                        </div>

                        <div className="management-field" style={{ flex: '0 1 150px' }}>
                            <label htmlFor="payment-date-to">Date To</label>
                            <input
                                id="payment-date-to"
                                type="date"
                                value={dateTo}
                                onChange={(event) => setDateTo(event.target.value)}
                            />
                        </div>

                        <button className="management-primary" type="submit">
                            Search
                        </button>

                        <button
                            className="management-secondary"
                            type="button"
                            onClick={clearFilters}
                        >
                            Clear
                        </button>
                    </form>

                    {paymentList.length === 0 ? (
                        <div className="management-empty">
                            <div style={{ fontSize: 32, marginBottom: 10 }}>
                                💳
                            </div>

                            <strong>No SaaS Payments Found</strong>

                            <p>
                                No payment transactions match your current filters.
                            </p>
                        </div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead>
                                    <tr>
                                        <th>Transaction</th>
                                        <th>Organization</th>
                                        <th>Plan</th>
                                        <th>Provider</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {paymentList.map((payment) => {
                                        const subscription =
                                            payment.organization_subscription;

                                        return (
                                            <tr key={payment.id}>
                                                <td>
                                                    <strong>{payment.internal_reference}</strong>
                                                    <div className="management-muted">
                                                        {payment.provider_reference || 'No provider reference'}
                                                    </div>
                                                </td>

                                                <td>
                                                    <strong>
                                                        {subscription?.organization?.name || 'Unknown organization'}
                                                    </strong>
                                                    <div className="management-muted">
                                                        {payment.phone_number || 'No phone number'}
                                                    </div>
                                                </td>

                                                <td>
                                                    {subscription?.plan?.name || 'Unknown plan'}
                                                    <div className="management-muted">
                                                        {subscription?.billing_cycle || '—'}
                                                    </div>
                                                </td>

                                                <td style={{ textTransform: 'capitalize' }}>
                                                    {payment.provider || '—'}
                                                </td>

                                                <td style={{
                                                    whiteSpace: 'nowrap',
                                                    fontWeight: 700,
                                                }}>
                                                    {money(payment.amount, payment.currency)}
                                                </td>

                                                <td>
                                                    <span className={`management-status ${payment.status}`}>
                                                        {statusLabel(payment.status)}
                                                    </span>
                                                </td>

                                                <td style={{ whiteSpace: 'nowrap' }}>
                                                    {formatDate(payment.paid_at || payment.created_at)}
                                                </td>

                                                <td>
                                                    <Link
                                                        href={`/saas-payments/${payment.id}`}
                                                        className="management-link"
                                                    >
                                                        View Details →
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {paginationLinks.length > 3 && (
                        <div className="management-pagination">
                            <span>
                                Showing {payments.from || 0}–{payments.to || 0}
                                {' '}of {payments.total || 0} transactions
                            </span>

                            <div className="management-pagination-links">
                                {paginationLinks.map((link, index) => {
                                    const label = link.label
                                        .replace('&laquo;', '«')
                                        .replace('&raquo;', '»')
                                        .replace(/<[^>]*>/g, '');

                                    if (!link.url) {
                                        return (
                                            <span
                                                key={index}
                                                className="disabled"
                                                dangerouslySetInnerHTML={{ __html: label }}
                                            />
                                        );
                                    }

                                    return (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={link.active ? 'current' : ''}
                                            preserveScroll
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
