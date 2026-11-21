
import { Link } from '@inertiajs/react';
import './GuestLayout.css';

export default function GuestLayout({ children }) {
    return (
        <div className="guest-layout">
            <div className="guest-brand">
                <Link href="/" className="guest-brand-link">
                    <img
                        src="/images/logo.png"
                        alt="NACPAS Connect"
                        className="guest-brand-logo"
                    />

                    <div className="guest-brand-text">
                        <h1>NacPas Connect</h1>
                        <p>Smart WiFi Management Platform</p>
                    </div>
                </Link>
            </div>

            <main className="guest-content">
                {children}
            </main>
        </div>
    );
}
