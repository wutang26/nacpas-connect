import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Products.css';

function formatPrice(value, currency = 'TZS') {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `${currency} ${Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export default function Index({
    products,
    categories = [],
    filters = {},
    statistics = {},
}) {
    const { flash = {} } = usePage().props;

    const searchForm = useForm({
        q: filters.q || '',
        category: filters.category || '',
        status: filters.status || '',
    });

    const submitFilters = (event) => {
        event.preventDefault();

        router.get('/hardware/products', searchForm.data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        searchForm.setData({
            q: '',
            category: '',
            status: '',
        });

        router.get('/hardware/products', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const deleteProduct = (product) => {
        const confirmed = window.confirm(
            `Are you sure you want to delete "${product.name}" (${product.sku})?`
        );

        if (confirmed) {
            router.delete(`/hardware/products/${product.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Hardware Products" />

            <div className="hardware-products-page">
                <div className="hardware-products-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/hardware">Hardware Management</Link>
                    <span>/</span>
                    <span>Products Catalogue</span>
                </div>

                <div className="hardware-products-heading">
                    <div>
                        <span className="hardware-products-eyebrow">HARDWARE MANAGEMENT</span>
                        <h1>Products Catalogue</h1>
                        <p>Manage hardware products, brands, models and default prices.</p>
                    </div>

                    <Link
                        href="/hardware/products/create"
                        className="hardware-products-primary-button"
                    >
                        <span>+</span> Register Product
                    </Link>
                </div>

                {flash.success && (
                    <div className="hardware-products-alert success">
                        <span>✓</span> {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="hardware-products-alert error">
                        {flash.error}
                    </div>
                )}

                <div className="hardware-products-statistics">
                    <div className="hardware-products-stat-card">
                        <div className="hardware-products-stat-icon">📦</div>
                        <div>
                            <span>Total Products</span>
                            <strong>{statistics.total ?? 0}</strong>
                        </div>
                    </div>

                    <div className="hardware-products-stat-card">
                        <div className="hardware-products-stat-icon active">✓</div>
                        <div>
                            <span>Active Products</span>
                            <strong>{statistics.active ?? 0}</strong>
                        </div>
                    </div>

                    <div className="hardware-products-stat-card">
                        <div className="hardware-products-stat-icon inactive">◷</div>
                        <div>
                            <span>Inactive Products</span>
                            <strong>{statistics.inactive ?? 0}</strong>
                        </div>
                    </div>
                </div>

                <form className="hardware-products-toolbar" onSubmit={submitFilters}>
                    <div className="hardware-products-search">
                        <label htmlFor="product-search">Search Products</label>
                        <input
                            id="product-search"
                            type="search"
                            value={searchForm.data.q}
                            onChange={(e) => searchForm.setData('q', e.target.value)}
                            placeholder="Search SKU, name, brand or model..."
                        />
                    </div>

                    <div className="hardware-products-filter">
                        <label htmlFor="product-category">Category</label>
                        <select
                            id="product-category"
                            value={searchForm.data.category}
                            onChange={(e) => searchForm.setData('category', e.target.value)}
                        >
                            <option value="">All Categories</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>{category}</option>
                            ))}
                        </select>
                    </div>

                    <div className="hardware-products-filter">
                        <label htmlFor="product-status">Status</label>
                        <select
                            id="product-status"
                            value={searchForm.data.status}
                            onChange={(e) => searchForm.setData('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div className="hardware-products-filter-actions">
                        <button type="submit" className="hardware-products-search-button">
                            Search
                        </button>
                        <button type="button" onClick={clearFilters} className="hardware-products-clear-button">
                            Clear
                        </button>
                    </div>
                </form>

                <section className="hardware-products-table-panel">
                    <div className="hardware-products-table-heading">
                        <div>
                            <h2>Registered Products</h2>
                            <p>Products available in the NACPASI hardware catalogue.</p>
                        </div>

                        <span className="hardware-products-count">
                            {products.total ?? 0} products
                        </span>
                    </div>

                    <div className="hardware-products-table-scroll">
                        <table className="hardware-products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Brand / Model</th>
                                    <th>Purchase Price</th>
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                {products.data?.length > 0 ? (
                                    products.data.map((product) => (
                                        <tr key={product.id}>
                                            <td>
                                                <div className="hardware-products-name">
                                                    <strong>{product.name}</strong>
                                                    <small>{product.unit_of_measure || 'piece'}</small>
                                                </div>
                                            </td>
                                            <td><span className="hardware-products-sku">{product.sku}</span></td>
                                            <td>{product.category}</td>
                                            <td>
                                                <div className="hardware-products-brand">
                                                    <strong>{product.brand || '—'}</strong>
                                                    <small>{product.model || 'No model'}</small>
                                                </div>
                                            </td>
                                            <td>{formatPrice(product.default_purchase_price, product.currency)}</td>
                                            <td>{formatPrice(product.default_selling_price, product.currency)}</td>
                                            <td>
                                                <span className={`hardware-products-status ${product.is_active ? 'active' : 'inactive'}`}>
                                                    {product.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td>
                                                <div className="hardware-products-actions">
                                                    <Link
                                                        href={`/hardware/products/${product.id}/edit`}
                                                        className="hardware-products-edit-button"
                                                    >
                                                        Edit
                                                    </Link>

                                                    <button
                                                        type="button"
                                                        onClick={() => deleteProduct(product)}
                                                        className="hardware-products-delete-button"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8">
                                            <div className="hardware-products-empty">
                                                <span>📦</span>
                                                <strong>No products found</strong>
                                                <p>Register a product or change your search filters.</p>
                                                <Link href="/hardware/products/create">
                                                    Register your first product
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {products.links && products.links.length > 3 && (
                        <div className="hardware-products-pagination">
                            <span>
                                Showing {products.from ?? 0} to {products.to ?? 0} of {products.total ?? 0}
                            </span>

                            <div>
                                {products.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        preserveScroll
                                        className={[
                                            'hardware-products-page-link',
                                            link.active ? 'active' : '',
                                            !link.url ? 'disabled' : '',
                                        ].join(' ')}
                                        onClick={(event) => {
                                            if (!link.url) event.preventDefault();
                                        }}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
