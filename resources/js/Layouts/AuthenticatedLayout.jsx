
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import './AuthenticatedLayout.css';

export default function AuthenticatedLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    const menuGroups = [
        {
            title: 'PLATFORM',
            items: [
                { label: 'Dashboard', icon: '▦', href: '/dashboard', active: true },
                { label: 'Organizations', icon: '🏢', href: '/organizations' },
                { label: 'Sites', icon: '📍', href: '/sites' },
                { label: 'Users', icon: '👥', href: '/users' },
            ],
        },
        {
            title: 'SAAS MANAGEMENT',
            items: [
                { label: 'SaaS Plans', icon: '📦', href: '/saas-plans' },
                { label: 'Subscriptions', icon: '🔄', href: '/subscriptions' },
                { label: 'SaaS Payments', icon: '💳', href: '/saas-payments' },
            ],
        },
        {
            title: 'REPORTS & SETTINGS',
            items: [
                { label: 'Reports', icon: '📊', href: '/reports' },
                { label: 'Platform Settings', icon: '⚙', href: '/settings' },
            ],
        },
    ];

    return (
        <div className="nac-layout">
            {sidebarOpen && (
                <div
                    className="sidebar-overlay"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            <aside
                className={`nac-sidebar ${sidebarCollapsed ? 'collapsed' : ''} ${
                    sidebarOpen ? 'mobile-open' : ''
                }`}
            >
                <div className="sidebar-brand">
                    <div className="brand-logo">N</div>

                    {!sidebarCollapsed && (
                        <div className="brand-info">
                            <h2>NACPAS</h2>
                            <span>CONNECT</span>
                        </div>
                    )}

                    <button
                        className="sidebar-close"
                        onClick={() => setSidebarOpen(false)}
                    >
                        ×
                    </button>
                </div>

                <div className="sidebar-menu">
                    {menuGroups.map((group) => (
                        <div className="menu-group" key={group.title}>
                            {!sidebarCollapsed && (
                                <div className="menu-group-title">
                                    {group.title}
                                </div>
                            )}

                            {group.items.map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    onClick={() => setSidebarOpen(false)}
                                    className={`sidebar-link ${
                                        item.active ? 'active' : ''
                                    }`}
                                    title={sidebarCollapsed ? item.label : ''}
                                >
                                    <span className="menu-icon">{item.icon}</span>

                                    {!sidebarCollapsed && (
                                        <span>{item.label}</span>
                                    )}
                                </Link>
                            ))}
                        </div>
                    ))}
                </div>

                <div className="sidebar-bottom">
                    <div className="sidebar-user">
                        <div className="user-avatar">
                            {user?.name?.charAt(0)?.toUpperCase() || 'A'}
                        </div>

                        {!sidebarCollapsed && (
                            <div className="user-info">
                                <strong>{user?.name || 'Administrator'}</strong>
                                <span>Super Administrator</span>
                            </div>
                        )}
                    </div>

                    {!sidebarCollapsed && (
                        <Link href="/profile" className="sidebar-profile">
                            My Account
                        </Link>
                    )}

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="sidebar-logout"
                    >
                        <span>↪</span>
                        {!sidebarCollapsed && <span>Sign Out</span>}
                    </Link>
                </div>
            </aside>

            <div className="nac-main">
                <header className="nac-header">
                    <div className="header-left">
                        <button
                            className="mobile-menu-button"
                            onClick={() => setSidebarOpen(true)}
                        >
                            ☰
                        </button>

                        <button
                            className="collapse-button"
                            onClick={() =>
                                setSidebarCollapsed(!sidebarCollapsed)
                            }
                            title="Toggle sidebar"
                        >
                            ☰
                        </button>

                        <div className="header-title">
                            <strong>Platform Administration</strong>
                            <span>Manage your NACPAS Connect platform</span>
                        </div>
                    </div>

                    <div className="header-right">
                        <div className="header-status">
                            <span className="status-dot"></span>
                            Platform Online
                        </div>

                        <div className="header-user">
                            <div className="header-avatar">
                                {user?.name?.charAt(0)?.toUpperCase() || 'A'}
                            </div>

                            <div>
                                <strong>{user?.name || 'Administrator'}</strong>
                                <span>Super Admin</span>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="nac-content">
                    {children}
                </main>

                <footer className="nac-footer">
                    <span>© {new Date().getFullYear()} NACPAS Connect</span>
                    <span>Powered by NAC Technologies</span>
                </footer>
            </div>
        </div>
    );
}
