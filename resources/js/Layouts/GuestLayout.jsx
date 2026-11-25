
import { Link, usePage } from '@inertiajs/react';
import './GuestLayout.css';

export default function GuestLayout({ children }) {
    const { platformSettings = {} } = usePage().props;

    const platformName = platformSettings.platform_name || 'NACPASI';
    const platformTagline =
        platformSettings.platform_tagline ||
        'Smart WiFi Management Platform';

    return (
        <div className="guest-layout">
            <div className="guest-brand">
                <Link href="/" className="guest-brand-link">
                    <img
                        src="/images/logo.png"
                        alt={platformName}
                        className="guest-brand-logo"
                    />

                    <div className="guest-brand-text">
                        <h1>{platformName}</h1>
                        <p>{platformTagline}</p>
                    </div>
                </Link>
            </div>

            <main className="guest-content">
                {children}
            </main>
        </div>
    );
}
