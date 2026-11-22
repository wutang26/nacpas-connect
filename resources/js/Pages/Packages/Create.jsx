
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PackageForm from './PackageForm';
import './Packages.css';

export default function Create({ sites = [], selectedSiteId = null }) {
    return (
        <AuthenticatedLayout>
            <Head title="Create WiFi Package" />

            <div className="packages-page">
                <div className="packages-heading">
                    <div>
                        <div className="packages-breadcrumb">
                            <Link href="/dashboard">Dashboard</Link>
                            <span>/</span>
                            <Link href="/packages">WiFi Packages</Link>
                            <span>/</span>
                            <span>Create</span>
                        </div>

                        <h1>Create WiFi Package</h1>
                        <p>Register a new internet package for your hotspot site.</p>
                    </div>
                </div>

                {sites.length === 0 ? (
                    <div className="packages-empty">
                        <span>📍</span>
                        <h3>No manageable sites found</h3>
                        <p>You need an authorized Site before creating a WiFi package.</p>
                    </div>
                ) : (
                    <PackageForm
                        sites={sites}
                        selectedSiteId={selectedSiteId}
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
