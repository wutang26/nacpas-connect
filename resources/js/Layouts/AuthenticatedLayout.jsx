
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import './AuthenticatedLayout.css';

export default function AuthenticatedLayout({ children }) {
    const { auth, url = '' } = usePage().props;
    const user = auth?.user;

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    const userRoles = Array.isArray(user?.roles)
        ? user.roles
        : [];

    const userRole = userRoles.includes('super_admin')
        ? 'Super Administrator'
        : userRoles.includes('admin')
            ? 'Administrator'
            : userRoles.includes('owner')
                ? 'Organization Owner'
                : userRoles.includes('manager')
                    ? 'Manager'
                    : userRoles.length > 0
                        ? userRoles[0].replaceAll('_', ' ')
                            .replace(/\b\w/g, (char) => char.toUpperCase())
                        : 'Platform User';

    const menuGroups = [
        {
            title: 'PLATFORM',
            items: [
                {
                    label: 'Dashboard',
                    icon: '▦',
                    href: '/dashboard',
                },
                {
                    label: 'Organizations',
                    icon: '🏢',
                    href: '/organizations',
                },
                {
                    label: 'Sites',
                    icon: '📍',
                    href: '/sites',
                },
                {
                    label: 'Users',
                    icon: '👥',
                    href: '/users',
                },
                {
                    label: 'WiFi Packages',
                    icon: '📶',
                    href: '/packages',
                },
                {
                    label: 'WiFi Access',
                    icon: '📡',
                    href: '/wifi-networks',
                },
            ],
        },
        {
            title: 'SAAS MANAGEMENT',
            items: [
                {
                    label: 'SaaS Plans',
                    icon: '📦',
                    href: '/saas-plans',
                },
                {
                    label: 'Subscriptions',
                    icon: '🔄',
                    href: '/subscriptions',
                },
                {
                    label: 'SaaS Payments',
                    icon: '💳',
                    href: '/saas-payments',
                },
            ],
        },
        {
            title: 'REPORTS & SETTINGS',
            items: [
                {
                    label: 'Reports',
                    icon: '📊',
                    href: '/reports',
                },
                {
                    label: 'Platform Settings',
                    icon: '⚙',
                    href: '/settings',
                },
            ],
        },
    ];

    const isActive = (href) => {
        const currentPath = url.split('?')[0].replace(/\/+$/, '') || '/';

        if (href === '/dashboard') {
            return currentPath === '/dashboard';
        }

        return currentPath === href || currentPath.startsWith(`${href}/`);
    };

    return (
        <div className="nac-layout">
            {sidebarOpen && (
                <div
                    className="sidebar-overlay"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            <aside
                className={`nac-sidebar ${
                    sidebarCollapsed ? 'collapsed' : ''
                } ${sidebarOpen ? 'mobile-open' : ''}`}
            >
                <div className="sidebar-brand">
                    <div className="brand-logo">N</div>

                    {!sidebarCollapsed && (
                        <div className="brand-info">
                            <h2>NACPASI</h2>
                            <span>CONNECT</span>
                        </div>
                    )}

                    <button
                        type="button"
                        className="sidebar-close"
                        onClick={() => setSidebarOpen(false)}
                        aria-label="Close sidebar"
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

                            {group.items.map((item) => {
                                const active = isActive(item.href);

                                return (
                                    <Link
                                        key={item.label}
                                        href={item.href}
                                        onClick={() => setSidebarOpen(false)}
                                        className={`sidebar-link ${
                                            active ? 'active' : ''
                                        }`}
                                        title={
                                            sidebarCollapsed
                                                ? item.label
                                                : undefined
                                        }
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                    >
                                        <span className="menu-icon">
                                            {item.icon}
                                        </span>

                                        {!sidebarCollapsed && (
                                            <span>{item.label}</span>
                                        )}
                                    </Link>
                                );
                            })}
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
                                <strong>
                                    {user?.name || 'Administrator'}
                                </strong>

                                <span>{userRole}</span>
                            </div>
                        )}
                    </div>

                    {!sidebarCollapsed && (
                        <Link
                            href="/profile"
                            className="sidebar-profile"
                            onClick={() => setSidebarOpen(false)}
                        >
                            My Account
                        </Link>
                    )}

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="sidebar-logout"
                        onClick={() => setSidebarOpen(false)}
                    >
                        <span>↪</span>

                        {!sidebarCollapsed && (
                            <span>Sign Out</span>
                        )}
                    </Link>
                </div>
            </aside>

            <div className="nac-main">
                <header className="nac-header">
                    <div className="header-left">
                        <button
                            type="button"
                            className="mobile-menu-button"
                            onClick={() => setSidebarOpen(true)}
                            aria-label="Open sidebar"
                        >
                            ☰
                        </button>

                        <button
                            type="button"
                            className="collapse-button"
                            onClick={() =>
                                setSidebarCollapsed((previous) => !previous)
                            }
                            title="Toggle sidebar"
                            aria-label="Toggle sidebar"
                        >
                            ☰
                        </button>

                        <div className="header-title">
                            <strong>Platform Administration</strong>
                            <span>
                                Manage your NACPASI Connect platform
                            </span>
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
                                <strong>
                                    {user?.name || 'Administrator'}
                                </strong>

                                <span>{userRole}</span>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="nac-content">
                    {children}
                </main>

                <footer className="nac-footer">
                    <span>
                        © {new Date().getFullYear()} NACPASI Connect
                    </span>

                    <span>Powered by NAC Technologies</span>
                </footer>
            </div>
        </div>
    );
}
