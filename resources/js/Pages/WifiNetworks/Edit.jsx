
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import WifiNetworkForm from './WifiNetworkForm';
import './WifiNetworks.css';

export default function Edit({ network, sites = [] }) {
    return (
        <AuthenticatedLayout>
            <Head title="Hariri WiFi Network" />

            <div className="wifi-page">
                <div className="wifi-heading">
                    <div>
                        <div className="wifi-breadcrumb">
                            <Link href="/dashboard">Dashboard</Link>
                            <span>/</span>
                            <Link href="/wifi-networks">WiFi Access</Link>
                            <span>/</span>
                            <span>Hariri</span>
                        </div>

                        <h1>Hariri WiFi Network</h1>
                        <p>
                            Badilisha taarifa na security ya {network.name}.
                        </p>
                    </div>
                </div>

                <WifiNetworkForm
                    sites={sites}
                    network={network}
                    mode="edit"
                />
            </div>
        </AuthenticatedLayout>
    );
}
