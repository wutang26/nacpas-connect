import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Assets.css';

const statusLabels = {
    in_stock: 'In Stock',
    reserved: 'Reserved',
    sold: 'Sold',
    installed: 'Installed',
    maintenance: 'Maintenance',
    damaged: 'Damaged',
    retired: 'Retired',
};

const conditionLabels = {
    new: 'New',
    used: 'Used',
    refurbished: 'Refurbished',
    damaged: 'Damaged',
};

function formatPrice(value, currency = 'TZS') {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `${currency} ${Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

function formatDate(value) {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '—';

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export default function Index({
    assets,
    filters = {},
    products = [],
    statuses = [],
    conditions = [],
    statistics = {},
}) {
    const { flash = {} } = usePage().props;

    const searchForm = useForm({
        q: filters.q || '',
        status: filters.status || '',
        condition: filters.condition || '',
        product_id: filters.product_id || '',
    });

    const submitFilters = (event) => {
        event.preventDefault();

        router.get('/hardware/assets', searchForm.data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        searchForm.setData({
            q: '',
            status: '',
            condition: '',
            product_id: '',
        });

        router.get('/hardware/assets', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Device Asset Tracking" />

            <div className="hardware-assets-page">
                <div className="hardware-assets-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/hardware">Hardware Management</Link>
                    <span>/</span>
                    <span>Device Asset Tracking</span>
                </div>

                <div className="hardware-assets-heading">
                    <div>
                        <span className="hardware-assets-eyebrow">
                            HARDWARE MANAGEMENT
                        </span>
                        <h1>Device Asset Tracking</h1>
                        <p>
                            Register and track individual physical devices owned
                            by NACPASI or supplied to business customers.
                        </p>
                    </div>

                    <Link
                        href="/hardware/assets/create"
                        className="hardware-assets-primary-button"
                    >
                        <span>+</span> Register Device
                    </Link>
                </div>

                {flash.success && (
                    <div className="hardware-assets-alert success">
                        <span>✓</span> {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="hardware-assets-alert error">
                        <span>!</span> {flash.error}
                    </div>
                )}

                <section className="hardware-assets-statistics">
                    <div className="hardware-assets-stat-card">
                        <div className="hardware-assets-stat-icon">▣</div>
                        <div>
                            <span>Total Registered Assets</span>
                            <strong>{statistics.total ?? 0}</strong>
                        </div>
                    </div>

                    <div className="hardware-assets-stat-card">
                        <div className="hardware-assets-stat-icon in-stock">📦</div>
                        <div>
                            <span>Available in Stock</span>
                            <strong>{statistics.in_stock ?? 0}</strong>
                        </div>
                    </div>

                    <div className="hardware-assets-stat-card">
                        <div className="hardware-assets-stat-icon sold">🧾</div>
                        <div>
                            <span>Sold Devices</span>
                            <strong>{statistics.sold ?? 0}</strong>
                        </div>
                    </div>

                    <div className="hardware-assets-stat-card">
                        <div className="hardware-assets-stat-icon installed">📡</div>
                        <div>
                            <span>Installed Devices</span>
                            <strong>{statistics.installed ?? 0}</strong>
                        </div>
                    </div>

                    <div className="hardware-assets-stat-card">
                        <div className="hardware-assets-stat-icon maintenance">🔧</div>
                        <div>
                            <span>Under Maintenance</span>
                            <strong>{statistics.maintenance ?? 0}</strong>
                        </div>
                    </div>
                </section>

                <form onSubmit={submitFilters} className="hardware-assets-toolbar">
                    <div className="hardware-assets-search">
                        <label htmlFor="asset-search">Search Assets</label>
                        <input
                            id="asset-search"
                            type="text"
                            value={searchForm.data.q}
                            onChange={(e) => searchForm.setData('q', e.target.value)}
                            placeholder="Asset ID, serial number, MAC, product..."
                        />
                    </div>

                    <div className="hardware-assets-filter">
                        <label htmlFor="asset-product">Product</label>
                        <select
                            id="asset-product"
                            value={searchForm.data.product_id}
                            onChange={(e) => searchForm.setData('product_id', e.target.value)}
                        >
                            <option value="">All Products</option>
                            {products.map((product) => (
                                <option key={product.id} value={product.id}>
                                    {product.name} ({product.sku})
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="hardware-assets-filter">
                        <label htmlFor="asset-status">Asset Status</label>
                        <select
                            id="asset-status"
                            value={searchForm.data.status}
                            onChange={(e) => searchForm.setData('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {statusLabels[status] || status}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="hardware-assets-filter">
                        <label htmlFor="asset-condition">Condition</label>
                        <select
                            id="asset-condition"
                            value={searchForm.data.condition}
                            onChange={(e) => searchForm.setData('condition', e.target.value)}
                        >
                            <option value="">All Conditions</option>
                            {conditions.map((condition) => (
                                <option key={condition} value={condition}>
                                    {conditionLabels[condition] || condition}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="hardware-assets-filter-actions">
                        <button type="submit" className="hardware-assets-search-button">
                            Search
                        </button>
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="hardware-assets-clear-button"
                        >
                            Clear
                        </button>
                    </div>
                </form>

                <section className="hardware-assets-table-panel">
                    <div className="hardware-assets-table-heading">
                        <div>
                            <h2>Registered Device Assets</h2>
                            <p>
                                Individual hardware units registered in the NACPASI
                                inventory.
                            </p>
                        </div>

                        <span className="hardware-assets-count">
                            {assets.total ?? 0} assets
                        </span>
                    </div>

                    <div className="hardware-assets-table-scroll">
                        <table className="hardware-assets-table">
                            <thead>
                                <tr>
                                    <th>Asset ID</th>
                                    <th>Product</th>
                                    <th>Serial Number / MAC</th>
                                    <th>Condition</th>
                                    <th>Purchase Cost</th>
                                    <th>Purchase Date</th>
                                    <th>Status</th>
                                    <th>Customer / Site</th>
                                </tr>
                            </thead>

                            <tbody>
                                {assets.data?.length > 0 ? (
                                    assets.data.map((asset) => (
                                        <tr key={asset.id}>
                                            <td>
                                                <span className="hardware-assets-tag">
                                                    {asset.asset_tag || `ASSET-${asset.id}`}
                                                </span>
                                            </td>

                                            <td>
                                                <div className="hardware-assets-product">
                                                    <strong>{asset.product?.name || 'Unknown Product'}</strong>
                                                    <small>
                                                        {asset.product?.brand || '—'}
                                                        {asset.product?.model
                                                            ? ` / ${asset.product.model}`
                                                            : ''}
                                                    </small>
                                                </div>
                                            </td>

                                            <td>
                                                <div className="hardware-assets-identifiers">
                                                    <strong>{asset.serial_number || 'No serial number'}</strong>
                                                    <small>{asset.mac_address || 'No MAC address'}</small>
                                                </div>
                                            </td>

                                            <td>
                                                <span className={`hardware-assets-condition ${asset.condition}`}>
                                                    {conditionLabels[asset.condition] || asset.condition}
                                                </span>
                                            </td>

                                            <td>
                                                {formatPrice(asset.purchase_cost, asset.currency)}
                                            </td>

                                            <td>{formatDate(asset.purchase_date)}</td>

                                            <td>
                                                <span className={`hardware-assets-status ${asset.status}`}>
                                                    {statusLabels[asset.status] || asset.status}
                                                </span>
                                            </td>

                                            <td>
                                                <div className="hardware-assets-location">
                                                    <strong>{asset.organization?.name || 'NACPASI Inventory'}</strong>
                                                    <small>{asset.site?.name || 'Not installed'}</small>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8">
                                            <div className="hardware-assets-empty">
                                                <span>📦</span>
                                                <strong>No device assets found</strong>
                                                <p>
                                                    Register a physical device or change
                                                    your search filters.
                                                </p>
                                                <Link href="/hardware/assets/create">
                                                    Register your first device
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {assets.links && assets.links.length > 3 && (
                        <div className="hardware-assets-pagination">
                            <span>
                                Showing {assets.from ?? 0} to {assets.to ?? 0} of {assets.total ?? 0}
                            </span>

                            <div>
                                {assets.links.map((link, index) => (
                                    link.url ? (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={`hardware-assets-page-link ${link.active ? 'active' : ''}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            preserveScroll
                                        />
                                    ) : (
                                        <span
                                            key={index}
                                            className="hardware-assets-page-link disabled"
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )
                                ))}
                            </div>
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
