import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import AssetForm from './AssetForm';
import './Assets.css';

export default function Create({ products = [] }) {
    const { flash = {} } = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title="Register Device Asset" />

            <div className="hardware-assets-page">
                <div className="hardware-assets-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <Link href="/hardware">Hardware Management</Link>
                    <span>/</span>
                    <Link href="/hardware/assets">Device Asset Tracking</Link>
                    <span>/</span>
                    <span>Register Device</span>
                </div>

                <div className="hardware-assets-heading">
                    <div>
                        <span className="hardware-assets-eyebrow">
                            HARDWARE INVENTORY
                        </span>
                        <h1>Register Device Asset</h1>
                        <p>
                            Register an individual physical device in the
                            NACPASI inventory.
                        </p>
                    </div>

                    <Link
                        href="/hardware/assets"
                        className="hardware-assets-secondary-button"
                    >
                        ← Back to Assets
                    </Link>
                </div>

                {flash.error && (
                    <div className="hardware-assets-alert error">
                        {flash.error}
                    </div>
                )}

                {products.length === 0 ? (
                    <div className="hardware-assets-notice">
                        <strong>No active products available</strong>
                        <p>
                            You must register a product in the Products Catalogue
                            before registering an individual device asset.
                        </p>
                        <Link href="/hardware/products/create">
                            Register a Product
                        </Link>
                    </div>
                ) : (
                    <AssetForm products={products} />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
