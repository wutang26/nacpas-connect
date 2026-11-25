
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Packages.css';

function formatNumber(value, locale = 'en-TZ', options = {}) {
    const number = Number(value ?? 0);

    if (!Number.isFinite(number)) {
        return '0';
    }

    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
        ...options,
    }).format(number);
}

function getNumberLocale(country) {
    const locales = {
        Tanzania: 'en-TZ',
        Kenya: 'en-KE',
        Uganda: 'en-UG',
        Rwanda: 'en-RW',
        Burundi: 'fr-BI',
        'United States': 'en-US',
    };

    return locales[country] || 'en-TZ';
}

function formatMoney(price, currency, locale = 'en-TZ') {
    const amount = formatNumber(price, locale);

    return `${currency} ${amount}`;
}

function formatDuration(minutes) {
    const value = Number(minutes);

    if (!Number.isFinite(value) || value <= 0) {
        return 'Not set';
    }

    if (value < 60) {
        return `${value} min`;
    }

    if (value % 43200 === 0) {
        const months = value / 43200;
        return `${months} ${months === 1 ? 'month' : 'months'}`;
    }

    if (value % 10080 === 0) {
        const weeks = value / 10080;
        return `${weeks} ${weeks === 1 ? 'week' : 'weeks'}`;
    }

    if (value % 1440 === 0) {
        const days = value / 1440;
        return `${days} ${days === 1 ? 'day' : 'days'}`;
    }

    if (value % 60 === 0) {
        const hours = value / 60;
        return `${hours} ${hours === 1 ? 'hour' : 'hours'}`;
    }

    return `${value} min`;
}

function formatSpeed(speed, locale) {
    return `${formatNumber(speed, locale)} Kbps`;
}

function formatDataLimit(bytes, locale) {
    const value = Number(bytes);

    if (!Number.isFinite(value) || value <= 0) {
        return 'Unlimited';
    }

    const megabytes = value / (1024 * 1024);

    return `${formatNumber(megabytes, locale)} MB`;
}

function DeletePackageButton({ packageItem }) {
    const { delete: destroy, processing } = useForm();

    const handleDelete = () => {
        if (!window.confirm(
            `Are you sure you want to delete "${packageItem.name}"?`
        )) {
            return;
        }

        destroy(`/packages/${packageItem.id}`, {
            preserveScroll: true,
        });
    };

    const hasSubscriptions = Number(
        packageItem.subscriptions_count || 0
    ) > 0;

    return (
        <button
            type="button"
            className="package-action-button delete"
            onClick={handleDelete}
            disabled={processing || hasSubscriptions}
            title={
                hasSubscriptions
                    ? 'This package has subscription history and cannot be deleted.'
                    : 'Delete package'
            }
        >
            Delete
        </button>
    );
}

export default function Index({
    sites = [],
    selectedSiteId = null,
    packages = [],
}) {
    const {
        flash = {},
        errors = {},
        platformSettings = {},
    } = usePage().props;

    const platformCurrency = String(
        platformSettings.currency || 'TZS'
    ).toUpperCase();

    const numberLocale = getNumberLocale(
        platformSettings.country
    );

    const activeCount = packages.filter(
        (item) => item.is_active
    ).length;

    const inactiveCount = packages.length - activeCount;

    const handleSiteChange = (event) => {
        router.get(
            '/packages',
            { site_id: event.target.value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="WiFi Packages" />

            <div className="packages-page">

                <div className="packages-heading">
                    <div>
                        <h1>WiFi Packages</h1>

                        <p>
                            Create and manage internet packages for your hotspot sites.
                        </p>
                    </div>

                    <Link
                        href={`/packages/create${selectedSiteId ? `?site_id=${selectedSiteId}` : ''}`}
                        className="package-primary-button"
                    >
                        <span>＋</span> Create Package
                    </Link>
                </div>

                {flash.success && (
                    <div className="package-alert success">
                        <span>✓</span>
                        {flash.success}
                    </div>
                )}

                {errors.package && (
                    <div className="package-alert error">
                        <span>!</span>
                        {errors.package}
                    </div>
                )}

                <div className="package-toolbar">

                    <div className="package-site-filter">
                        <label htmlFor="package-site">
                            Select Site
                        </label>

                        <select
                            id="package-site"
                            value={selectedSiteId ?? ''}
                            onChange={handleSiteChange}
                            disabled={sites.length === 0}
                        >
                            {sites.map((site) => (
                                <option
                                    key={site.id}
                                    value={site.id}
                                >
                                    {site.name}
                                    {site.code ? ` (${site.code})` : ''}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="package-currency-label">
                        Default Currency:{' '}
                        <strong>{platformCurrency}</strong>
                    </div>

                </div>

                <div className="package-statistics">

                    <div className="package-stat-card">
                        <div className="package-stat-icon">
                            📦
                        </div>

                        <div>
                            <span>Total Packages</span>
                            <strong>{packages.length}</strong>
                        </div>
                    </div>

                    <div className="package-stat-card">
                        <div className="package-stat-icon active">
                            ✓
                        </div>

                        <div>
                            <span>Active Packages</span>
                            <strong>{activeCount}</strong>
                        </div>
                    </div>

                    <div className="package-stat-card">
                        <div className="package-stat-icon inactive">
                            ⏸
                        </div>

                        <div>
                            <span>Inactive Packages</span>
                            <strong>{inactiveCount}</strong>
                        </div>
                    </div>

                </div>

                <div className="package-table-panel">

                    <div className="package-table-heading">
                        <div>
                            <h3>Package List</h3>

                            <p>
                                Internet packages registered under the selected site.
                            </p>
                        </div>

                        <span className="package-count">
                            {packages.length} packages
                        </span>
                    </div>

                    {sites.length === 0 ? (

                        <div className="packages-empty">
                            <span>📍</span>

                            <h3>No manageable sites found</h3>

                            <p>
                                You do not currently have access to manage any hotspot site.
                            </p>
                        </div>

                    ) : packages.length === 0 ? (

                        <div className="packages-empty">
                            <span>📦</span>

                            <h3>No packages registered</h3>

                            <p>
                                Create your first WiFi package for this site.
                            </p>

                            <Link
                                href={`/packages/create?site_id=${selectedSiteId}`}
                                className="package-primary-button"
                            >
                                Create First Package
                            </Link>
                        </div>

                    ) : (

                        <div className="package-table-scroll">

                            <table className="package-table">

                                <thead>
                                    <tr>
                                        <th>Package</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Download</th>
                                        <th>Upload</th>
                                        <th>Data Limit</th>
                                        <th>Devices</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {packages.map((item) => {
                                        const packageCurrency = String(
                                            item.currency || platformCurrency
                                        ).toUpperCase();

                                        return (
                                            <tr key={item.id}>

                                                <td>
                                                    <div className="package-name-cell">
                                                        <strong>
                                                            {item.name}
                                                        </strong>

                                                        <small>
                                                            {item.description || 'No description'}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td className="package-price">
                                                    {formatMoney(
                                                        item.price,
                                                        packageCurrency,
                                                        numberLocale
                                                    )}
                                                </td>

                                                <td>
                                                    {formatDuration(
                                                        item.duration_minutes
                                                    )}
                                                </td>

                                                <td>
                                                    {formatSpeed(
                                                        item.download_speed_kbps,
                                                        numberLocale
                                                    )}
                                                </td>

                                                <td>
                                                    {formatSpeed(
                                                        item.upload_speed_kbps,
                                                        numberLocale
                                                    )}
                                                </td>

                                                <td>
                                                    {formatDataLimit(
                                                        item.data_limit_bytes,
                                                        numberLocale
                                                    )}
                                                </td>

                                                <td>
                                                    {formatNumber(
                                                        item.device_limit,
                                                        numberLocale,
                                                        { maximumFractionDigits: 0 }
                                                    )}
                                                </td>

                                                <td>
                                                    <span
                                                        className={`package-status ${
                                                            item.is_active
                                                                ? 'enabled'
                                                                : 'disabled'
                                                        }`}
                                                    >
                                                        {item.is_active
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </span>
                                                </td>

                                                <td>
                                                    <div className="package-actions">

                                                        <Link
                                                            href={`/packages/${item.id}/edit`}
                                                            className="package-action-button edit"
                                                        >
                                                            Edit
                                                        </Link>

                                                        <DeletePackageButton
                                                            packageItem={item}
                                                        />

                                                    </div>
                                                </td>

                                            </tr>
                                        );
                                    })}
                                </tbody>

                            </table>

                        </div>
                    )}

                </div>

            </div>
        </AuthenticatedLayout>
    );
}
