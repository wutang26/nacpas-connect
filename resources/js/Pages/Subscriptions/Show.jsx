import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

const statusLabel = {
    trialing: 'Trialing',
    active: 'Active',
    past_due: 'Past Due',
    cancelled: 'Cancelled',
    expired: 'Expired',
    pending: 'Pending',
    successful: 'Successful',
    failed: 'Failed',
    refunded: 'Refunded',
};

const statusClass = (status) => {
    const allowed = [
        'trialing',
        'active',
        'past_due',
        'cancelled',
        'expired',
        'pending',
        'successful',
        'failed',
        'refunded',
    ];

    return allowed.includes(status) ? status : 'inactive';
};

const formatDate = (value, includeTime = false) => {
    if (!value) return '—';

    return new Date(value).toLocaleString('en-GB', includeTime
        ? {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }
        : {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }
    );
};

const money = (amount, currency = 'TZS') =>
    `${currency} ${Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;

export default function Show({
    subscription,
    payments,
    canManage = false,
}) {
    const { flash = {} } = usePage().props;

    const organization = subscription.organization || {};
    const plan = subscription.plan || {};
    const paymentList = payments?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title={`Subscription #${subscription.id}`} />

            <div className="management-page">

                <div className="management-heading">
                    <div>
                        <h1>SaaS Subscription Details</h1>
                        <p>
                            Subscription #{subscription.id} ·{' '}
                            {organization.name || 'Unknown Organization'}
                        </p>
                    </div>

                    <div className="management-heading-actions">
                        <Link
                            href="/subscriptions"
                            className="management-secondary"
                        >
                            ← Back to Subscriptions
                        </Link>

                        {canManage && (
                            <Link
                                href={`/subscriptions/${subscription.id}/edit`}
                                className="management-primary"
                            >
                                ✎ Edit Subscription
                            </Link>
                        )}
                    </div>
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

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Subscription Overview</h3>
                            <p>
                                Current billing and subscription information.
                            </p>
                        </div>

                        <span className={`management-status ${statusClass(subscription.status)}`}>
                            {statusLabel[subscription.status] || subscription.status}
                        </span>
                    </div>

                    <div className="management-detail-grid">
                        <div className="management-detail">
                            <span>Organization</span>
                            <strong>{organization.name || '—'}</strong>
                        </div>

                        <div className="management-detail">
                            <span>Organization Code</span>
                            <strong>{organization.code || '—'}</strong>
                        </div>

                        <div className="management-detail">
                            <span>Organization Status</span>
                            <strong>{organization.status || '—'}</strong>
                        </div>

                        <div className="management-detail">
                            <span>SaaS Plan</span>
                            <strong>{plan.name || '—'}</strong>
                        </div>

                        <div className="management-detail">
                            <span>Plan Code</span>
                            <strong>{plan.code || '—'}</strong>
                        </div>

                        <div className="management-detail">
                            <span>Billing Cycle</span>
                            <strong style={{ textTransform: 'capitalize' }}>
                                {subscription.billing_cycle || '—'}
                            </strong>
                        </div>

                        <div className="management-detail">
                            <span>Subscription Amount</span>
                            <strong>
                                {money(subscription.amount, subscription.currency)}
                            </strong>
                        </div>

                        <div className="management-detail">
                            <span>Currency</span>
                            <strong>{subscription.currency || 'TZS'}</strong>
                        </div>

                        <div className="management-detail">
                            <span>Created At</span>
                            <strong>{formatDate(subscription.created_at, true)}</strong>
                        </div>
                    </div>
                </div>

                <div className="management-stats">
                    <div className="management-stat">
                        <div className="management-stat-icon">📅</div>
                        <div>
                            <span>Period Start</span>
                            <strong className="management-stat-date">
                                {formatDate(subscription.current_period_start)}
                            </strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">⏳</div>
                        <div>
                            <span>Period End</span>
                            <strong className="management-stat-date">
                                {formatDate(subscription.current_period_end)}
                            </strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">◷</div>
                        <div>
                            <span>Trial Ends</span>
                            <strong className="management-stat-date">
                                {formatDate(subscription.trial_ends_at)}
                            </strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">🛡</div>
                        <div>
                            <span>Grace Period Ends</span>
                            <strong className="management-stat-date">
                                {formatDate(subscription.grace_ends_at)}
                            </strong>
                        </div>
                    </div>
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Cancellation Settings</h3>
                            <p>
                                Cancellation behavior configured for this subscription.
                            </p>
                        </div>
                    </div>

                    <div className="management-detail-grid">
                        <div className="management-detail">
                            <span>Cancel at Period End</span>
                            <strong>
                                {subscription.cancel_at_period_end
                                    ? 'Yes'
                                    : 'No'}
                            </strong>
                        </div>

                        <div className="management-detail">
                            <span>Cancelled At</span>
                            <strong>
                                {formatDate(subscription.cancelled_at, true)}
                            </strong>
                        </div>
                    </div>
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Payment History</h3>
                            <p>
                                Recorded payment transactions for this subscription.
                            </p>
                        </div>

                        <span className="management-count">
                            {payments?.total ?? 0} payments
                        </span>
                    </div>

                    {paymentList.length === 0 ? (
                        <div className="management-empty">
                            <div style={{ fontSize: 30, marginBottom: 10 }}>
                                💳
                            </div>

                            <strong>No Payment Records</strong>

                            <p>
                                No payment transactions have been recorded
                                for this subscription yet.
                            </p>
                        </div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Provider</th>
                                        <th>Phone Number</th>
                                        <th>Amount</th>
                                        <th>Paid At</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {paymentList.map((payment) => (
                                        <tr key={payment.id}>
                                            <td>
                                                <strong>
                                                    {payment.internal_reference || '—'}
                                                </strong>

                                                <div className="management-muted">
                                                    {payment.provider_reference || 'No provider reference'}
                                                </div>
                                            </td>

                                            <td style={{ textTransform: 'capitalize' }}>
                                                {payment.provider || '—'}
                                            </td>

                                            <td>{payment.phone_number || '—'}</td>

                                            <td style={{
                                                whiteSpace: 'nowrap',
                                                fontWeight: 700,
                                            }}>
                                                {money(payment.amount, payment.currency)}
                                            </td>

                                            <td>
                                                {formatDate(payment.paid_at, true)}
                                            </td>

                                            <td>
                                                <span className={`management-status ${statusClass(payment.status)}`}>
                                                    {statusLabel[payment.status] || payment.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {payments?.last_page > 1 && (
                        <div className="management-pagination">
                            <span>
                                Showing {payments.from || 0}–
                                {payments.to || 0} of {payments.total || 0}
                            </span>

                            <div className="management-pagination-links">
                                {(payments.links || []).map((link, index) => (
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