
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import '../Management/Management.css';

export default function Index({
    plans,
    filters = {},
    statistics = {},
    canManage = false,
}) {
    const { flash = {} } = usePage().props;

    const [search, setSearch] = useState(filters.q || '');
    const [status, setStatus] = useState(filters.status || '');

    const applyFilters = (event) => {
        event.preventDefault();

        router.get('/saas-plans', {
            q: search,
            status,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');

        router.get('/saas-plans', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const money = (amount, currency = 'TZS') => {
        const value = Number(amount || 0);

        return `${currency} ${value.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        })}`;
    };

    const limit = (value) => {
        const number = Number(value ?? 0);

        return number >= 999999
            ? 'Unlimited'
            : number.toLocaleString('en-US');
    };

    const planList = plans?.data || [];
    const paginationLinks = plans?.links || [];

    return (
        <AuthenticatedLayout>
            <Head title="SaaS Plans" />

            <div className="management-page">

                {/* PAGE HEADER */}
                <div className="management-heading">
                    <div>
                        <h1>SaaS Plans</h1>
                        <p>
                            Create, view and manage NACPASI subscription
                            plans, pricing and platform limits.
                        </p>
                    </div>

                    {canManage && (
                        <Link
                            href="/saas-plans/create"
                            className="management-primary"
                        >
                            ＋ Create SaaS Plan
                        </Link>
                    )}
                </div>

                {/* FLASH MESSAGE */}
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

                {/* STATISTICS */}
                <div className="management-stats">
                    {[
                        ['📦', 'Total Plans', statistics.total],
                        ['✓', 'Active Plans', statistics.active],
                        ['⏸', 'Inactive Plans', statistics.inactive],
                        ['🔄', 'Plans in Use', statistics.subscribed],
                    ].map(([icon, label, value]) => (
                        <div className="management-stat" key={label}>
                            <div className="management-stat-icon">
                                {icon}
                            </div>

                            <div>
                                <span>{label}</span>
                                <strong>{value ?? 0}</strong>
                            </div>
                        </div>
                    ))}
                </div>

                {/* PLANS PANEL */}
                <div className="management-panel">

                    <div className="management-panel-heading">
                        <div>
                            <h3>Subscription Plans</h3>
                            <p>
                                Review plan pricing, limits, features
                                and organization usage.
                            </p>
                        </div>

                        <span className="management-count">
                            {plans?.total ?? 0} plans
                        </span>
                    </div>

                    {/* FILTERS */}
                    <form
                        className="management-filters"
                        onSubmit={applyFilters}
                    >
                        <div className="management-field grow">
                            <label htmlFor="plan-search">
                                Search Plans
                            </label>

                            <input
                                id="plan-search"
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Plan name, code or description..."
                            />
                        </div>

                        <div
                            className="management-field"
                            style={{ flex: '0 1 190px' }}
                        >
                            <label htmlFor="plan-status">
                                Status
                            </label>

                            <select
                                id="plan-status"
                                value={status}
                                onChange={(event) =>
                                    setStatus(event.target.value)
                                }
                            >
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <button
                            className="management-primary"
                            type="submit"
                        >
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

                    {/* TABLE */}
                    {planList.length === 0 ? (
                        <div className="management-empty">
                            <div style={{ fontSize: 32, marginBottom: 10 }}>
                                📦
                            </div>

                            <strong>No SaaS Plans Found</strong>

                            <p>
                                There are no plans matching your search.
                            </p>

                            {canManage && (
                                <Link
                                    href="/saas-plans/create"
                                    className="management-primary"
                                    style={{
                                        display: 'inline-flex',
                                        marginTop: 12,
                                    }}
                                >
                                    ＋ Create First Plan
                                </Link>
                            )}
                        </div>
                    ) : (
                        <div className="management-table-wrap">
                            <table className="management-table">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Monthly</th>
                                        <th>Annual</th>
                                        <th>Sites</th>
                                        <th>Users</th>
                                        <th>Devices</th>
                                        <th>Subscriptions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {planList.map((plan) => (
                                        <tr key={plan.id}>

                                            {/* PLAN */}
                                            <td>
                                                <div style={{
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: 4,
                                                    minWidth: 110,
                                                }}>
                                                    <strong>
                                                        {plan.name}
                                                    </strong>

                                                    <small style={{
                                                        color: '#89958d',
                                                        fontSize: 11,
                                                    }}>
                                                        {plan.code}
                                                    </small>
                                                </div>
                                            </td>

                                            {/* PRICING */}
                                            <td style={{
                                                whiteSpace: 'nowrap',
                                                fontWeight: 600,
                                            }}>
                                                {money(
                                                    plan.price_monthly,
                                                    plan.currency
                                                )}
                                            </td>

                                            <td style={{
                                                whiteSpace: 'nowrap',
                                                fontWeight: 600,
                                            }}>
                                                {money(
                                                    plan.price_annual,
                                                    plan.currency
                                                )}
                                            </td>

                                            {/* LIMITS */}
                                            <td>{limit(plan.max_sites)}</td>
                                            <td>{limit(plan.max_users)}</td>
                                            <td>{limit(plan.max_devices)}</td>

                                            {/* USAGE */}
                                            <td>
                                                {plan.organization_subscriptions_count ?? 0}
                                            </td>

                                            {/* STATUS */}
                                            <td>
                                                <span
                                                    className={`management-status ${
                                                        plan.is_active
                                                            ? 'active'
                                                            : 'inactive'
                                                    }`}
                                                >
                                                    {plan.is_active
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </span>
                                            </td>

                                            {/* ACTIONS */}
                                            <td>
                                                <div
                                                    className="management-inline-actions"
                                                    style={{
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        gap: 10,
                                                        whiteSpace: 'nowrap',
                                                    }}
                                                >
                                                    <Link
                                                        href={`/saas-plans/${plan.id}`}
                                                        title="View plan details"
                                                    >
                                                        View
                                                    </Link>

                                                    {canManage && (
                                                        <Link
                                                            href={`/saas-plans/${plan.id}/edit`}
                                                            title="Edit plan"
                                                        >
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

                    {/* PAGINATION */}
                    {plans?.last_page > 1 && (
                        <div className="management-pagination">
                            <span>
                                Showing {plans.from || 0}–
                                {plans.to || 0} of {plans.total || 0}
                            </span>

                            <div className="management-pagination-links">
                                {paginationLinks.map((link, index) => (
                                    link.url ? (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={
                                                link.active ? 'current' : ''
                                            }
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                            preserveScroll
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
