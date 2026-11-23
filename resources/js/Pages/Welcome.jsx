
import { Head, Link } from '@inertiajs/react';
import './Welcome.css';

export default function Welcome({ canLogin, canRegister }) {
    // Site ya majaribio kwa sasa.
    // Baadaye tutafanya iwe dynamic kulingana na Site husika.
    const portalUrl = '/portal/nacpas-main-site';

    const features = [
        {
            icon: '📡',
            title: 'WiFi Hotspot Management',
            description:
                'Manage your WiFi hotspots, access points, connected devices and internet users from one central dashboard.',
        },
        {
            icon: '💳',
            title: 'Smart Billing & Payments',
            description:
                'Sell internet packages, manage payments and track customer transactions with ease.',
        },
        {
            icon: '🏢',
            title: 'Multi-Site Management',
            description:
                'Manage multiple hotspot locations, branches and WiFi packages from one account.',
        },
        {
            icon: '📊',
            title: 'Reports & Analytics',
            description:
                'Monitor sales, internet usage, active sessions and business performance in real time.',
        },
        {
            icon: '📱',
            title: 'QR Code Access',
            description:
                'Make it easier for customers to connect to your WiFi through QR-assisted onboarding.',
        },
        {
            icon: '🔐',
            title: 'Secure Network Control',
            description:
                'Control customer access, manage sessions and monitor your network securely.',
        },
    ];

    return (
        <>
            <Head title="NACPAS Connect - Smart WiFi Management" />

            <div className="welcome-page">
                {/* NAVIGATION */}
                <header className="welcome-navbar">
                    <div className="welcome-nav-container">
                        <Link href="/" className="welcome-brand">
                            <img
                                src="/images/logo.png"
                                alt="NACPAS Connect"
                            />

                            <div>
                                <strong>NACPASI Connect</strong>
                                <span>Smart WiFi Management</span>
                            </div>
                        </Link>

                        <nav className="welcome-nav-links">
                            <a href="#home">Home</a>
                            <a href="#features">Features</a>
                            <a href="#about">About</a>
                            <a href="#contact">Contact</a>
                        </nav>

                        <div className="welcome-nav-actions">
                            <Link
                                href={portalUrl}
                                className="nav-portal-link"
                            >
                                <span>📶</span>
                                WiFi Portal
                            </Link>

                            {canLogin && (
                                <Link href="/login" className="nav-login">
                                    Sign In
                                </Link>
                            )}

                            {canRegister && (
                                <Link
                                    href="/register"
                                    className="nav-register"
                                >
                                    Get Started
                                </Link>
                            )}
                        </div>
                    </div>
                </header>

                {/* HERO */}
                <section className="welcome-hero" id="home">
                    <div className="welcome-hero-container">
                        <div className="welcome-hero-content">
                            <div className="hero-label">
                                <span className="hero-label-dot"></span>
                                THE SMARTER WAY TO MANAGE WIFI
                            </div>

                            <h1>
                                Manage Your WiFi Business.
                                <span> Connect More People.</span>
                            </h1>

                            <p>
                                NACPASI Connect is an intelligent WiFi hotspot
                                management and billing platform designed to
                                help internet service providers and hotspot
                                businesses manage customers, packages,
                                payments and networks in one place.
                            </p>

                            <div className="hero-actions">
                                {canRegister && (
                                    <Link
                                        href="/register"
                                        className="hero-primary-button"
                                    >
                                        Get Started
                                        <span>→</span>
                                    </Link>
                                )}

                                {canLogin && (
                                    <Link
                                        href="/login"
                                        className="hero-secondary-button"
                                    >
                                        Sign In
                                    </Link>
                                )}

                                <Link
                                    href={portalUrl}
                                    className="hero-portal-button"
                                >
                                    <span>📶</span>
                                    Connect to WiFi
                                </Link>
                            </div>

                            <div className="hero-trust">
                                <div className="trust-icon">✓</div>
                                <span>
                                    Centralized management. Smarter business.
                                </span>
                            </div>
                        </div>

                        {/* DASHBOARD PREVIEW */}
                        <div className="hero-visual">
                            <div className="dashboard-preview">
                                <div className="preview-topbar">
                                    <div className="preview-brand">
                                        <img
                                            src="/images/logo.png"
                                            alt=""
                                        />
                                        <span>NACPASI Connect</span>
                                    </div>

                                    <div className="preview-status">
                                        <span></span>
                                        Network Online
                                    </div>
                                </div>

                                <div className="preview-body">
                                    <div className="preview-heading">
                                        <div>
                                            <small>OVERVIEW</small>
                                            <h3>Dashboard</h3>
                                        </div>
                                        <span className="preview-date">
                                            Platform Overview
                                        </span>
                                    </div>

                                    <div className="preview-stats">
                                        <div className="preview-stat">
                                            <span>Organizations</span>
                                            <strong>120+</strong>
                                            <small>Managed businesses</small>
                                        </div>

                                        <div className="preview-stat">
                                            <span>Hotspot Sites</span>
                                            <strong>350+</strong>
                                            <small>Connected locations</small>
                                        </div>

                                        <div className="preview-stat">
                                            <span>WiFi Customers</span>
                                            <strong>12K+</strong>
                                            <small>Customer accounts</small>
                                        </div>
                                    </div>

                                    <div className="preview-network">
                                        <div className="network-heading">
                                            <strong>Network Management</strong>
                                            <span>System overview</span>
                                        </div>

                                        
                                <div className="network-illustration">
                                    {/* CONNECTION LINES */}
                                    <div className="network-line line-one"></div>
                                    <div className="network-line line-two"></div>
                                    <div className="network-line line-three"></div>
                                    <div className="network-line line-four"></div>

                                    {/* CENTRAL WIFI */}
                                    <div className="network-center">
                                        <div className="wifi-pulse pulse-one"></div>
                                        <div className="wifi-pulse pulse-two"></div>
                                        <div className="wifi-pulse pulse-three"></div>

                                        <div className="wifi-center-content">
                                            <span className="wifi-symbol">⌁</span>
                                            <strong>WiFi</strong>
                                            <small>
                                                <span className="wifi-online-dot"></span>
                                                Connected
                                            </small>
                                        </div>
                                    </div>

                                    {/* CONNECTED DEVICES */}
                                    <div className="network-node node-one">
                                        <span>📱</span>
                                    </div>

                                    <div className="network-node node-two">
                                        <span>💻</span>
                                    </div>

                                    <div className="network-node node-three">
                                        <span>📡</span>
                                    </div>

                                    <div className="network-node node-four">
                                        <span>📲</span>
                                    </div>
                                </div>

                                    </div>

                                    <div className="preview-footer">
                                        <span>● All systems operational</span>
                                        <span>Powered by NAC Technologies</span>
                                    </div>
                                </div>
                            </div>

                            <div className="floating-card floating-card-one">
                                <div className="floating-card-icon">✓</div>
                                <div>
                                    <strong>Smart Payments</strong>
                                    <span>Payment management</span>
                                </div>
                            </div>

                            <div className="floating-card floating-card-two">
                                <div className="floating-card-icon">📡</div>
                                <div>
                                    <strong>WiFi Connected</strong>
                                    <span>Network monitoring</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* FEATURES */}
                <section className="welcome-features" id="features">
                    <div className="welcome-section-container">
                        <div className="section-heading">
                            <span>OUR PLATFORM</span>
                            <h2>
                                Everything You Need to Manage Your WiFi Business
                            </h2>
                            <p>
                                Powerful tools designed to simplify your
                                internet business operations.
                            </p>
                        </div>

                        <div className="features-grid">
                            {features.map((feature, index) => (
                                <div className="feature-card" key={index}>
                                    <div className="feature-icon">
                                        {feature.icon}
                                    </div>

                                    <h3>{feature.title}</h3>

                                    <p>{feature.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ABOUT */}
                <section className="welcome-about" id="about">
                    <div className="welcome-section-container about-container">
                        <div className="about-content">
                            <span>BUILT FOR INTERNET BUSINESSES</span>

                            <h2>
                                One Platform.
                                <br />
                                Multiple Sites.
                                <br />
                                Complete Control.
                            </h2>

                            <p>
                                Whether you operate one public WiFi hotspot
                                or manage multiple locations, NACPASI Connect
                                gives you the tools to control your operations,
                                serve your customers and grow your business.
                            </p>

                            <ul>
                                <li>✓ Manage multiple organizations and sites</li>
                                <li>✓ Create flexible internet packages</li>
                                <li>✓ Track customer payments and sessions</li>
                                <li>✓ Monitor network performance</li>
                            </ul>

                            {canRegister && (
                                <Link
                                    href="/register"
                                    className="about-button"
                                >
                                    Start Managing Your WiFi →
                                </Link>
                            )}
                        </div>

                        <div className="about-visual">
                            <div className="about-circle circle-one"></div>
                            <div className="about-circle circle-two"></div>

                            <div className="about-main-card">
                                <img
                                    src="/images/logo.png"
                                    alt="NACPAS Connect"
                                />
                                <h3>NACPASI Connect</h3>
                                <p>Connect. Manage. Grow.</p>
                                <div className="about-wifi-icon">⌁</div>
                                <span>SMART WIFI PLATFORM</span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="welcome-cta" id="contact">
                    <div className="cta-content">
                        <span>READY TO GET STARTED?</span>

                        <h2>
                            Take Your WiFi Business
                            <br />
                            to the Next Level.
                        </h2>

                        <p>
                            Simplify your hotspot operations with NACPASI
                            Connect.
                        </p>

                        <div className="cta-actions">
                            {canRegister && (
                                <Link
                                    href="/register"
                                    className="cta-primary"
                                >
                                    Create Your Account →
                                </Link>
                            )}

                            {canLogin && (
                                <Link href="/login" className="cta-secondary">
                                    Sign In
                                </Link>
                            )}

                            <Link
                                href={portalUrl}
                                className="cta-portal"
                            >
                                📶 Explore WiFi Portal
                            </Link>
                        </div>
                    </div>
                </section>

                {/* FOOTER */}
                <footer className="welcome-footer">
                    <div className="footer-container">
                        <div className="footer-brand">
                            <img
                                src="/images/logo.png"
                                alt="NACPAS Connect"
                            />

                            <div>
                                <strong>NACPASI Connect</strong>
                                <span>Smart WiFi Management Platform</span>
                            </div>
                        </div>

                        <p>
                            © {new Date().getFullYear()} NACPASI Connect.
                            All rights reserved.
                        </p>

                        <div className="footer-links">
                            <Link href={portalUrl}>
                                📶 WiFi Portal
                            </Link>
                            {canLogin && (
                                <Link href="/login">Sign In</Link>
                            )}
                        </div>

                        <div className="footer-powered">
                            Powered by <strong>NAC Technologies</strong>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
