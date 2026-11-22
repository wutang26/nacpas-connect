
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PackageForm from './PackageForm';
import '../Packages.css';

export default function Edit({ package: packageData, sites = [] }) {
    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${packageData.name}`} />

            <div className="packages-page">
                <div className="packages-heading">
                    <div>
                        <div className="packages-breadcrumb">
                            <Link href="/dashboard">Dashboard</Link>
                            <span>/</span>
                            <Link href={`/packages?site_id=${packageData.site_id}`}>
                                WiFi Packages
                            </Link>
                            <span>/</span>
                            <span>Edit</span>
                        </div>

                        <h1>Edit WiFi Package</h1>
                        <p>Update the settings for {packageData.name}.</p>
                    </div>
                </div>

                <PackageForm
                    sites={sites}
                    package={packageData}
                />
            </div>
        </AuthenticatedLayout>
    );
}
