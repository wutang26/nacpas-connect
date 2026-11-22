
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import WifiNetworkForm from './WifiNetworkForm';
import './WifiNetworks.css';

export default function Create({ sites = [], selectedSiteId = null }) {
    return (
        <AuthenticatedLayout>
            <Head title="Sajili WiFi Network" />

            <div className="wifi-page">
                <div className="wifi-heading">
                    <div>
                        <div className="wifi-breadcrumb">
                            <Link href="/dashboard">Dashboard</Link>
                            <span>/</span>
                            <Link href="/wifi-networks">WiFi Access</Link>
                            <span>/</span>
                            <span>Sajili</span>
                        </div>

                        <h1>Sajili WiFi Network</h1>
                        <p>
                            Ongeza SSID na mipangilio ya usalama wa WiFi.
                        </p>
                    </div>
                </div>

                {sites.length === 0 ? (
                    <div className="wifi-empty">
                        <h3>Hakuna Site inayopatikana</h3>
                        <p>Unahitaji Site unayoruhusiwa kusimamia.</p>
                    </div>
                ) : (
                    <WifiNetworkForm
                        sites={sites}
                        selectedSiteId={selectedSiteId}
                        mode="create"
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
