
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Show({
    plan,
    subscriptions,
    canManage = false,
}) {
    const money = (amount, currency = 'TZS') =>
        `${currency} ${Number(amount || 0).toLocaleString('en-US')}`;

    const formatDate = (value) => {
        if (!value) return '—';

        return new Date(value).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const limit = (value) => {
        const number = Number(value ?? 0);

        return number >= 999999
            ? 'Unlimited'
            : number.toLocaleString('en-US');
    };

    const features = Array.isArray(plan.features)
        ? plan.features
        : [];

    const subscriptionList = subscriptions?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title={`${plan.name} - SaaS Plan`} />

            <div className="management-page">

                {/* HEADER */}
                <div className="management-heading">
                    <div>
                        <h1>{plan.name}</h1>
                        <p>
                            SaaS plan details, pricing, limits and
                            organization subscriptions.
                        </p>
                    </div>

                    <div style={{
                        display: 'flex',
                        gap: 10,
                        flexWrap: 'wrap',
                    }}>
                        <Link
                            href="/saas-plans"
                            className="management-secondary"
                        >
                            ← Back to Plans
                        </Link>

                        {canManage && (
                            <Link
                                href={`/saas-plans/${plan.id}/edit`}
                                className="management-primary"
                            >
                                ✎ Edit Plan
                            </Link>
                        )}
                    </div>
                </div>

                {/* PLAN SUMMARY */}
                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Plan Information</h3>
                            <p>General subscription plan information.</p>
                        </div>

                        <span className={`management-status ${
                            plan.is_active ? 'active' : 'inactive'
                        }`}>
                            {plan.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>

                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(190px, 1fr))',
                        gap: 20,
                        padding: 20,
                    }}>
                        <div>
                            <small>Plan Name</small>
                            <h3>{plan.name}</h3>
                        </div>

                        <div>
                            <small>Plan Code</small>
                            <h3>{plan.code}</h3>
                        </div>

                        <div>
                            <small>Currency</small>
                            <h3>{plan.currency}</h3>
                        </div>

                        <div>
                            <small>Created</small>
                            <h3>{formatDate(plan.created_at)}</h3>
                        </div>
                    </div>

                    {plan.description && (
                        <div style={{ padding: '0 20px 20px' }}>
                            <small>Description</small>
                            <p>{plan.description}</p>
                        </div>
                    )}
                </div>

                {/* PRICING */}
                <div className="management-stats">
                    <div className="management-stat">
                        <div className="management-stat-icon">💳</div>
                        <div>
                            <span>Monthly Price</span>
                            <strong>
                                {money(plan.price_monthly, plan.currency)}
                            </strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">📅</div>
                        <div>
                            <span>Annual Price</span>
                            <strong>
                                {money(plan.price_annual, plan.currency)}
                            </strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">🏢</div>
                        <div>
                            <span>Maximum Sites</span>
                            <strong>{limit(plan.max_sites)}</strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">👥</div>
                        <div>
                            <span>Maximum Users</span>
                            <strong>{limit(plan.max_users)}</strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">📡</div>
                        <div>
                            <span>Maximum Devices</span>
                            <strong>{limit(plan.max_devices)}</strong>
                        </div>
                    </div>

                    <div className="management-stat">
                        <div className="management-stat-icon">🔄</div>
                        <div>
                            <span>Organizations Using Plan</span>
                            <strong>
                                {plan.organization_subscriptions_count ?? 0}
                            </strong>
                        </div>
                    </div>
                </div>

                {/* FEATURES */}
                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Plan Features</h3>
                            <p>Features included in this subscription plan.</p>
                        </div>
                    </div>

                    <div style={{ padding: 20 }}>
                        {features.length > 0 ? (
                            <div style={{
                                display: 'grid',
                                gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
                                gap: 12,
                            }}>
                                {features.map((feature, index) => (
                                    <div
                                        key={index}
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 10,
                                            padding: 12,
                                            border: '1px solid #e5ebe7',
                                            borderRadius: 8,
                                        }}
                                    >
                                        <span style={{ color: '#218653' }}>
                                            ✓
                                        </span>
                                        <span>{feature}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="management-empty">
                                No features have been configured for this plan.
                            </div>
                        )}
                    </div>
                </div>

                {/* ORGANIZATION SUBSCRIPTIONS */}
                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Organization Subscriptions</h3>
                            <p>
                                Organizations currently or previously
                                subscribed to this plan.
                            </p>
                        </div>

                        <span className="management-count">
                            {subscriptions?.total ?? 0} subscriptions
                        </span>
                    </div>

                    {subscriptionList.length === 0 ? (
                        <div className="management-empty">
                            No organizations have subscribed to this plan yet.
                        </div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>Billing Cycle</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Period Start</th>
                                        <th>Period End</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {subscriptionList.map((subscription) => (
                                        <tr key={subscription.id}>
                                            <td>
                                                <strong>
                                                    {subscription.organization?.name
                                                        || 'Unknown Organization'}
                                                </strong>

                                                <div style={{
                                                    color: '#89958d',
                                                    fontSize: 11,
                                                    marginTop: 4,
                                                }}>
                                                    {subscription.organization?.code || '—'}
                                                </div>
                                            </td>

                                            <td>
                                                {subscription.billing_cycle}
                                            </td>

                                            <td style={{ whiteSpace: 'nowrap' }}>
                                                {money(
                                                    subscription.amount,
                                                    subscription.currency
                                                )}
                                            </td>

                                            <td>
                                                <span className={`management-status ${
                                                    subscription.status === 'active'
                                                        ? 'active'
                                                        : 'inactive'
                                                }`}>
                                                    {subscription.status}
                                                </span>
                                            </td>

                                            <td>
                                                {formatDate(subscription.current_period_start)}
                                            </td>

                                            <td>
                                                {formatDate(subscription.current_period_end)}
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
                                Showing {subscriptions.from}–
                                {subscriptions.to} of {subscriptions.total}
                            </span>

                            <div className="management-pagination-links">
                                {subscriptions.links.map((link, index) => (
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
