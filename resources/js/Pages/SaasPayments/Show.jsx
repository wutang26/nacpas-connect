import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Show({ payment }) {
    const subscription = payment.organization_subscription;
    const organization = subscription?.organization;
    const plan = subscription?.plan;

    const money = (amount, currency = 'TZS') => {
        return `${currency} ${Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })}`;
    };

    const date = (value) => {
        if (!value) return '—';

        return new Date(value).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const Detail = ({ label, children }) => (
        <div className="management-detail">
            <span>{label}</span>
            <strong>{children || '—'}</strong>
        </div>
    );

    return (
        <AuthenticatedLayout>
            <Head title={`Payment ${payment.internal_reference}`} />

            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>Payment Details</h1>
                        <p>
                            Review SaaS transaction information and its
                            associated organization subscription.
                        </p>
                    </div>

                    <div className="management-heading-actions">
                        <Link
                            href="/saas-payments"
                            className="management-secondary"
                        >
                            ← Back to Payments
                        </Link>
                    </div>
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Transaction Overview</h3>
                            <p>{payment.internal_reference}</p>
                        </div>

                        <span className={`management-status ${payment.status}`}>
                            {String(payment.status || 'unknown').replaceAll('_', ' ')}
                        </span>
                    </div>

                    <div className="management-detail-grid">
                        <Detail label="Amount">
                            {money(payment.amount, payment.currency)}
                        </Detail>

                        <Detail label="Payment Provider">
                            {payment.provider}
                        </Detail>

                        <Detail label="Internal Reference">
                            {payment.internal_reference}
                        </Detail>

                        <Detail label="Provider Reference">
                            {payment.provider_reference}
                        </Detail>

                        <Detail label="Customer Phone">
                            {payment.phone_number}
                        </Detail>

                        <Detail label="Payment Status">
                            {payment.status}
                        </Detail>

                        <Detail label="Created At">
                            {date(payment.created_at)}
                        </Detail>

                        <Detail label="Paid At">
                            {date(payment.paid_at)}
                        </Detail>

                        <Detail label="Last Updated">
                            {date(payment.updated_at)}
                        </Detail>
                    </div>
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Organization Information</h3>
                            <p>Organization associated with this payment.</p>
                        </div>
                    </div>

                    <div className="management-detail-grid">
                        <Detail label="Organization">
                            {organization?.name}
                        </Detail>

                        <Detail label="Organization ID">
                            {organization?.id}
                        </Detail>

                        <Detail label="Subscription ID">
                            {subscription?.id}
                        </Detail>
                    </div>
                </div>

                <div className="management-panel">
                    <div className="management-panel-heading">
                        <div>
                            <h3>Subscription Information</h3>
                            <p>Plan and billing period associated with this transaction.</p>
                        </div>
                    </div>

                    <div className="management-detail-grid">
                        <Detail label="SaaS Plan">
                            {plan?.name}
                        </Detail>

                        <Detail label="Plan Code">
                            {plan?.code}
                        </Detail>

                        <Detail label="Billing Cycle">
                            {subscription?.billing_cycle}
                        </Detail>

                        <Detail label="Subscription Status">
                            {subscription?.status}
                        </Detail>

                        <Detail label="Subscription Amount">
                            {money(subscription?.amount, subscription?.currency)}
                        </Detail>

                        <Detail label="Subscription Currency">
                            {subscription?.currency}
                        </Detail>

                        <Detail label="Period Start">
                            {date(subscription?.current_period_start)}
                        </Detail>

                        <Detail label="Period End">
                            {date(subscription?.current_period_end)}
                        </Detail>
                    </div>
                </div>

                {payment.metadata && (
                    <div className="management-panel">
                        <div className="management-panel-heading">
                            <div>
                                <h3>Transaction Metadata</h3>
                                <p>Additional information stored with this payment.</p>
                            </div>
                        </div>

                        <div style={{ padding: 20 }}>
                            <pre style={{
                                margin: 0,
                                padding: 16,
                                overflowX: 'auto',
                                borderRadius: 7,
                                background: '#f7faf8',
                                border: '1px solid #e9eeeb',
                                color: '#43564a',
                                fontSize: 11,
                                lineHeight: 1.7,
                                whiteSpace: 'pre-wrap',
                                overflowWrap: 'anywhere',
                            }}>
                                {JSON.stringify(payment.metadata, null, 2)}
                            </pre>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
