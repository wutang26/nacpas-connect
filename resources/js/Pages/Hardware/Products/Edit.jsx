import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProductForm from './ProductForm';
import './Products.css';

export default function Edit({ product, categories = [] }) {
    const { flash = {} } = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${product.name}`} />

            <div className="hardware-products-page">
                <div className="hardware-products-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/hardware">Hardware Management</Link>
                    <span>/</span>
                    <Link href="/hardware/products">Products</Link>
                    <span>/</span>
                    <span>Edit Product</span>
                </div>

                <div className="hardware-products-heading">
                    <div>
                        <h1>Edit Product</h1>
                        <p>Update catalogue information for {product.name}.</p>
                    </div>

                    <Link href="/hardware/products" className="hardware-products-secondary-button">
                        ← Back to Products
                    </Link>
                </div>

                {flash.error && (
                    <div className="hardware-products-alert error">{flash.error}</div>
                )}

                <ProductForm product={product} categories={categories} />
            </div>
        </AuthenticatedLayout>
    );
}
