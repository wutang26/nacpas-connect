import { Head, Link, useForm } from '@inertiajs/react';
import '../Management/Management.css';

const dateValue = (value) => {
    if (!value) return '';

    // Convert server datetime to datetime-local format.
    return String(value).replace(' ', 'T').slice(0, 16);
};

export default function SubscriptionForm({
    subscription = null,
    organizations = [],
    plans = [],
}) {
    const editing = Boolean(subscription);

    const { data, setData, post, put, processing, errors } = useForm({
        organization_id: subscription?.organization_id
            ? String(subscription.organization_id)
            : '',
        saas_plan_id: subscription?.saas_plan_id
            ? String(subscription.saas_plan_id)
            : '',
        billing_cycle: subscription?.billing_cycle || 'monthly',
        status: subscription?.status || 'trialing',
        current_period_start: dateValue(subscription?.current_period_start),
        current_period_end: dateValue(subscription?.current_period_end),
        trial_ends_at: dateValue(subscription?.trial_ends_at),
        grace_ends_at: dateValue(subscription?.grace_ends_at),
        cancel_at_period_end: subscription?.cancel_at_period_end ?? false,
    });

    const selectedPlan = plans.find(
        (plan) => String(plan.id) === String(data.saas_plan_id)
    );

    const selectedPrice = selectedPlan
        ? Number(
            data.billing_cycle === 'annual'
                ? selectedPlan.price_annual
                : selectedPlan.price_monthly
        )
        : 0;

    const money = (amount, currency = 'TZS') =>
        `${currency} ${Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })}`;

    const submit = (event) => {
        event.preventDefault();

        if (editing) {
            put(`/subscriptions/${subscription.id}`);
        } else {
            post('/subscriptions');
        }
    };

    const fieldError = (name) =>
        errors[name] ? <small>{errors[name]}</small> : null;

    return (
        <>
            <Head title={editing ? 'Edit SaaS Subscription' : 'Create SaaS Subscription'} />

            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>
                            {editing
                                ? 'Edit SaaS Subscription'
                                : 'Create SaaS Subscription'}
                        </h1>

                        <p>
                            {editing
                                ? 'Update organization billing, plan and subscription period.'
                                : 'Assign a NACPASI SaaS plan to a customer organization.'}
                        </p>
                    </div>

                    <Link href="/subscriptions" className="management-secondary">
                        ← Back to Subscriptions
                    </Link>
                </div>

                <div className="management-panel">
                    <form className="management-form" onSubmit={submit}>
                        <div className="management-form-grid">

                            <div className="management-form-section">
                                Organization & Plan
                            </div>

                            <div className="management-field">
                                <label htmlFor="organization_id">
                                    Organization
                                </label>

                                <select
                                    id="organization_id"
                                    value={data.organization_id}
                                    onChange={(event) =>
                                        setData('organization_id', event.target.value)
                                    }
                                >
                                    <option value="">Select organization</option>

                                    {organizations.map((organization) => (
                                        <option
                                            key={organization.id}
                                            value={organization.id}
                                        >
                                            {organization.name} ({organization.code})
                                            {organization.status !== 'active'
                                                ? ` — ${organization.status}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>

                                {fieldError('organization_id')}
                            </div>

                            <div className="management-field">
                                <label htmlFor="saas_plan_id">
                                    SaaS Plan
                                </label>

                                <select
                                    id="saas_plan_id"
                                    value={data.saas_plan_id}
                                    onChange={(event) =>
                                        setData('saas_plan_id', event.target.value)
                                    }
                                >
                                    <option value="">Select SaaS plan</option>

                                    {plans.map((plan) => (
                                        <option key={plan.id} value={plan.id}>
                                            {plan.name} ({plan.code})
                                            {plan.is_active === false
                                                ? ' — Inactive'
                                                : ''}
                                        </option>
                                    ))}
                                </select>

                                {fieldError('saas_plan_id')}
                            </div>

                            <div className="management-form-section">
                                Billing Configuration
                            </div>

                            <div className="management-field">
                                <label htmlFor="billing_cycle">
                                    Billing Cycle
                                </label>

                                <select
                                    id="billing_cycle"
                                    value={data.billing_cycle}
                                    onChange={(event) =>
                                        setData('billing_cycle', event.target.value)
                                    }
                                >
                                    <option value="monthly">Monthly</option>
                                    <option value="annual">Annually</option>
                                </select>

                                {fieldError('billing_cycle')}
                            </div>

                            <div className="management-field">
                                <label>Subscription Price</label>

                                <div className="subscription-price-preview">
                                    <strong>
                                        {money(
                                            selectedPrice,
                                            selectedPlan?.currency || 'TZS'
                                        )}
                                    </strong>

                                    <small>
                                        {data.billing_cycle === 'annual'
                                            ? 'Annual billing amount'
                                            : 'Monthly billing amount'}
                                    </small>
                                </div>
                            </div>

                            <div className="management-form-section">
                                Subscription Status
                            </div>

                            <div className="management-field">
                                <label htmlFor="status">Status</label>

                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(event) =>
                                        setData('status', event.target.value)
                                    }
                                >
                                    <option value="trialing">Trialing</option>
                                    <option value="active">Active</option>
                                    <option value="past_due">Past Due</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="expired">Expired</option>
                                </select>

                                {fieldError('status')}
                            </div>

                            <div className="management-field">
                                <label htmlFor="cancel_at_period_end">
                                    Cancellation
                                </label>

                                <select
                                    id="cancel_at_period_end"
                                    value={data.cancel_at_period_end ? '1' : '0'}
                                    onChange={(event) =>
                                        setData(
                                            'cancel_at_period_end',
                                            event.target.value === '1'
                                        )
                                    }
                                >
                                    <option value="0">Continue subscription</option>
                                    <option value="1">Cancel at period end</option>
                                </select>

                                {fieldError('cancel_at_period_end')}
                            </div>

                            <div className="management-form-section">
                                Subscription Period
                            </div>

                            <div className="management-field">
                                <label htmlFor="current_period_start">
                                    Period Start
                                </label>

                                <input
                                    id="current_period_start"
                                    type="datetime-local"
                                    value={data.current_period_start}
                                    onChange={(event) =>
                                        setData('current_period_start', event.target.value)
                                    }
                                />

                                {fieldError('current_period_start')}
                            </div>

                            <div className="management-field">
                                <label htmlFor="current_period_end">
                                    Period End
                                </label>

                                <input
                                    id="current_period_end"
                                    type="datetime-local"
                                    value={data.current_period_end}
                                    onChange={(event) =>
                                        setData('current_period_end', event.target.value)
                                    }
                                />

                                {fieldError('current_period_end')}
                            </div>

                            <div className="management-field">
                                <label htmlFor="trial_ends_at">
                                    Trial Ends At
                                </label>

                                <input
                                    id="trial_ends_at"
                                    type="datetime-local"
                                    value={data.trial_ends_at}
                                    onChange={(event) =>
                                        setData('trial_ends_at', event.target.value)
                                    }
                                />

                                {fieldError('trial_ends_at')}
                            </div>

                            <div className="management-field">
                                <label htmlFor="grace_ends_at">
                                    Grace Period Ends At
                                </label>

                                <input
                                    id="grace_ends_at"
                                    type="datetime-local"
                                    value={data.grace_ends_at}
                                    onChange={(event) =>
                                        setData('grace_ends_at', event.target.value)
                                    }
                                />

                                {fieldError('grace_ends_at')}
                            </div>
                        </div>

                        <div className="management-form-actions">
                            <Link
                                href="/subscriptions"
                                className="management-secondary"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                className="management-submit"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Saving...'
                                    : editing
                                        ? 'Save Changes'
                                        : 'Create Subscription'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}