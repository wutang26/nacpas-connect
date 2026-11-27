
import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import './AuthenticatedLayout.css';

export default function AuthenticatedLayout({ children }) {
    const {
        auth,
        url = '',
        platformSettings = {},
    } = usePage().props;

    const user = auth?.user;

    const platformName = platformSettings.platform_name || 'NACPASI';
    const platformTagline =
        platformSettings.platform_tagline || 'Connect. Manage. Grow.';

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);

    const userMenuRef = useRef(null);

    // =========================================
    // USER ROLES & PERMISSIONS
    // =========================================

    const userRoles = Array.isArray(user?.roles)
        ? user.roles
        : [];

    const userPermissions = Array.isArray(user?.permissions)
        ? user.permissions
        : [];

    const isSuperAdmin = userRoles.includes('super_admin');

    const can = (permission) => {
        return isSuperAdmin || userPermissions.includes(permission);
    };

    const canViewRoles =
        can('platform.roles.view') ||
        can('platform.roles.manage');

    const canViewPermissions =
        can('platform.permissions.view') ||
        can('platform.permissions.manage');

    const canViewUsers =
        can('platform.users.view') ||
        can('platform.users.manage');

    const canViewSettings =
        can('platform.settings.view') ||
        can('platform.settings.manage');

    // =========================================
    // USER ROLE DISPLAY
    // =========================================

    const userRole = userRoles.includes('super_admin')
        ? 'Super Administrator'
        : userRoles.includes('admin')
            ? 'Administrator'
            : userRoles.includes('organization_manager')
                ? 'Organization Manager'
                : userRoles.includes('site_manager')
                    ? 'Site Manager'
                    : userRoles.includes('support_agent')
                        ? 'Support Agent'
                        : userRoles.includes('accountant')
                            ? 'Accountant'
                            : userRoles.length > 0
                                ? userRoles[0]
                                    .replaceAll('_', ' ')
                                    .replace(/\b\w/g, (char) =>
                                        char.toUpperCase()
                                    )
                                : 'Platform User';

    // =========================================
    // USER AVATAR INITIALS
    // =========================================

    const userInitials = (user?.name || 'Administrator')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    // =========================================
    // USER DROPDOWN - OUTSIDE CLICK & ESCAPE
    // =========================================

    useEffect(() => {
        const handleOutsideClick = (event) => {
            if (
                userMenuRef.current &&
                !userMenuRef.current.contains(event.target)
            ) {
                setUserMenuOpen(false);
            }
        };

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setUserMenuOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutsideClick);
        document.addEventListener('touchstart', handleOutsideClick);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
            document.removeEventListener('touchstart', handleOutsideClick);
            document.removeEventListener('keydown', handleEscape);
        };
    }, []);

    // =========================================
    // ACCOUNT MENU ITEMS
    // =========================================

    const accountMenuItems = [
        {
            label: 'My Profile',
            description: 'Personal information',
            icon: '👤',
            href: '/profile',
        },
        {
            label: 'Change Password',
            description: 'Update your password',
            icon: '🔒',
            href: '/profile#password',
        },
        ...(canViewSettings
            ? [
                {
                    label: 'Account Settings',
                    description: 'Platform configuration',
                    icon: '⚙️',
                    href: '/settings',
                },
            ]
            : []),
    ];

    // =========================================
    // MAIN SIDEBAR MENU
    // =========================================

    const menuGroups = [
        {
            title: 'PLATFORM',
            items: [
                {
                    label: 'Dashboard',
                    icon: '▦',
                    href: '/dashboard',
                },
                ...(can('platform.organizations.view')
                    ? [{
                        label: 'Organizations',
                        icon: '🏢',
                        href: '/organizations',
                    }]
                    : []),
                ...(can('platform.sites.view')
                    ? [{
                        label: 'Sites',
                        icon: '📍',
                        href: '/sites',
                    }]
                    : []),
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
                {
                    label: 'QR Code Management',
                    icon: '▦',
                    href: '/qr-codes',
                },
            ],
        },

        // =====================================
        // HARDWARE MANAGEMENT
        // =====================================

        ...(can('hardware.dashboard.view')
            ? [
                {
                    title: 'HARDWARE MANAGEMENT',
                    items: [
                        {
                            label: 'Hardware Dashboard',
                            icon: '🖥️',
                            href: '/hardware',
                        },

                        ...(can('hardware.products.view')
                            ? [
                                {
                                    label: 'Products Catalogue',
                                    icon: '📦',
                                    href: '/hardware/products',
                                },
                            ]
                            : []),

                        ...(can('hardware.inventory.view')
                            ? [
                                {
                                    label: 'Device Asset Tracking',
                                    icon: '🔖',
                                    href: '/hardware/assets',
                                },
                            ]
                            : []),
                    ],
                },
            ]
            : []),

        // =====================================
        // NETWORK MANAGEMENT
        // =====================================

       ...(can('network.management.view')
    ? [
        {
            title: 'NETWORK MANAGEMENT',
            items: [
                {
                    label: 'Network Connections',
                    icon: '🔌',
                    href: '/network-connections',
                },
                {
                    label: 'Captive Portal Sessions',
                    icon: '📡',
                    href: '/captive-portal-sessions',
                },
            ],
        },
    ]
    : []),

        // =====================================
        // VOUCHER MANAGEMENT
        // =====================================

        ...(can('voucher.management.view') ||
            can('voucher.management.manage') ||
            can('voucher.agents.view') ||
            can('voucher.agents.manage') ||
            can('voucher.reports.view')
            ? [
                {
                    title: 'VOUCHER MANAGEMENT',
                    items: [
                        ...(can('voucher.management.view') ||
                            can('voucher.management.manage')
                            ? [{
                                label: 'Voucher Batches',
                                icon: '🎟️',
                                href: '/vouchers/batches',
                            }]
                            : []),

                        ...(can('voucher.agents.view') ||
                            can('voucher.agents.manage')
                            ? [
                                {
                                    label: 'Voucher Agents',
                                    icon: '👥',
                                    href: '/vouchers/agents',
                                },
                                {
                                    label: 'Agent Purchases',
                                    icon: '🧾',
                                    href: '/vouchers/purchases',
                                },
                            ]
                            : []),

                        ...(can('voucher.reports.view')
                            ? [{
                                label: 'Voucher Reports',
                                icon: '📊',
                                href: '/vouchers/reports',
                            }]
                            : []),
                    ],
                },
            ]
            : []),

        ...(can('voucher.agent.dashboard')
            ? [
                {
                    title: 'AGENT PORTAL',
                    items: [
                        {
                            label: 'My Voucher Dashboard',
                            icon: '🎫',
                            href: '/voucher-agent',
                        },
                    ],
                },
            ]
            : []),

        // =====================================
        // SAAS MANAGEMENT
        // =====================================

        ...(can('platform.saas_plans.view') ||
            can('platform.subscriptions.view') ||
            can('platform.payments.view')
            ? [
                {
                    title: 'SAAS MANAGEMENT',
                    items: [
                        ...(can('platform.saas_plans.view')
                            ? [
                                {
                                    label: 'SaaS Plans',
                                    icon: '📦',
                                    href: '/saas-plans',
                                },
                            ]
                            : []),

                        ...(can('platform.subscriptions.view')
                            ? [
                                {
                                    label: 'Subscriptions',
                                    icon: '🔄',
                                    href: '/subscriptions',
                                },
                            ]
                            : []),

                        ...(can('platform.payments.view')
                            ? [
                                {
                                    label: 'SaaS Payments',
                                    icon: '💳',
                                    href: '/saas-payments',
                                },
                            ]
                            : []),
                    ],
                },
            ]
            : []),

        // =====================================
        // REPORTS & SETTINGS
        // =====================================

        {
            title: 'REPORTS & SETTINGS',
            items: [
                {
                    label: 'Reports',
                    icon: '📊',
                    href: '/reports',
                },
            ],
        },
    ];

    // =========================================
    // SETTINGS SUBMENU
    // =========================================

    const settingsItems = [
        ...(canViewRoles
            ? [
                {
                    label: 'Roles & Permissions',
                    icon: '🛡️',
                    href: '/roles',
                },
            ]
            : []),

        ...(canViewPermissions
            ? [
                {
                    label: 'Permissions Management',
                    icon: '🔐',
                    href: '/permissions',
                },
            ]
            : []),

        ...(canViewUsers
            ? [
                {
                    label: 'User Management',
                    icon: '👥',
                    href: '/users',
                },
            ]
            : []),

        ...(canViewSettings
            ? [
                {
                    label: 'Platform Settings',
                    icon: '⚙️',
                    href: '/settings',
                },
                {
                    label: 'Email Configuration',
                    icon: '✉️',
                    href: '/settings/email',
                },
            ]
            : []),
    ];

    // =========================================
    // ACTIVE ROUTE DETECTION
    // =========================================

    const isActive = (href) => {
        const currentPath =
            url.split('?')[0].replace(/\/+$/, '') || '/';

        if (href === '/dashboard') {
            return currentPath === '/dashboard';
        }

        return (
            currentPath === href ||
            currentPath.startsWith(`${href}/`)
        );
    };

    const settingsActive = settingsItems.some(
        (item) => isActive(item.href)
    );

    // =========================================
    // RENDER
    // =========================================

    return (
        <div className="nac-layout">

            {/* MOBILE SIDEBAR OVERLAY */}

            {sidebarOpen && (
                <div
                    className="sidebar-overlay"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* =================================
                SIDEBAR
            ================================= */}

            <aside
                className={`nac-sidebar ${
                    sidebarCollapsed ? 'collapsed' : ''
                } ${sidebarOpen ? 'mobile-open' : ''}`}
            >
                {/* BRAND */}

                <div className="sidebar-brand">

                    <div className="brand-logo">N</div>

                    {!sidebarCollapsed && (
                        <div className="brand-info">
                            <h2>{platformName}</h2>
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

                {/* SIDEBAR NAVIGATION */}

                <div className="sidebar-menu">

                    {menuGroups.map((group) => (
                        <div
                            className="menu-group"
                            key={group.title}
                        >
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

                    {/* SETTINGS */}

                    {settingsItems.length > 0 && (
                        <div className="menu-group settings-menu-group">

                            {!sidebarCollapsed && (
                                <div className="menu-group-title">
                                    SETTINGS
                                </div>
                            )}

                            <button
                                type="button"
                                className={`sidebar-link settings-toggle ${
                                    settingsActive ? 'active' : ''
                                }`}
                                onClick={() => {
                                    setSettingsOpen(
                                        (previous) => !previous
                                    );
                                }}
                                title={
                                    sidebarCollapsed
                                        ? 'Settings'
                                        : undefined
                                }
                                aria-expanded={settingsOpen}
                            >
                                <span className="menu-icon">⚙</span>

                                {!sidebarCollapsed && (
                                    <>
                                        <span>Settings</span>

                                        <span className="settings-chevron">
                                            {settingsOpen ? '▴' : '▾'}
                                        </span>
                                    </>
                                )}
                            </button>

                            {settingsOpen && !sidebarCollapsed && (
                                <div className="settings-submenu">

                                    {settingsItems.map((item) => {
                                        const active = isActive(item.href);

                                        return (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                onClick={() => {
                                                    setSidebarOpen(false);
                                                }}
                                                className={`settings-submenu-link ${
                                                    active ? 'active' : ''
                                                }`}
                                                aria-current={
                                                    active ? 'page' : undefined
                                                }
                                            >
                                                <span>{item.icon}</span>
                                                <span>{item.label}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}

                            {sidebarCollapsed && settingsOpen && (
                                <div className="settings-collapsed-links">

                                    {settingsItems.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            title={item.label}
                                            onClick={() => {
                                                setSidebarOpen(false);
                                            }}
                                            className={`sidebar-link ${
                                                isActive(item.href)
                                                    ? 'active'
                                                    : ''
                                            }`}
                                        >
                                            <span className="menu-icon">
                                                {item.icon}
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* SIDEBAR BOTTOM */}

                <div className="sidebar-bottom">

                    <div className="sidebar-user">

                        <div className="user-avatar">
                            {userInitials || 'A'}
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

            {/* =================================
                MAIN CONTENT
            ================================= */}

            <div className="nac-main">

                {/* TOP HEADER */}

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
                                setSidebarCollapsed(
                                    (previous) => !previous
                                )
                            }
                            title="Toggle sidebar"
                            aria-label="Toggle sidebar"
                        >
                            ☰
                        </button>

                        <div className="header-title">

                            <strong>Platform Administration</strong>

                            <span>
                                Manage your {platformName} platform
                            </span>
                        </div>
                    </div>

                    {/* HEADER RIGHT / USER DROPDOWN */}

                    <div className="header-right">

                        <div className="header-status">
                            <span className="status-dot"></span>
                            <span>Platform Online</span>
                        </div>

                        <div
                            className="header-account"
                            ref={userMenuRef}
                        >
                            {/* ACCOUNT TRIGGER */}

                            <button
                                type="button"
                                className={`header-account-trigger ${
                                    userMenuOpen ? 'active' : ''
                                }`}
                                onClick={() =>
                                    setUserMenuOpen(
                                        (previous) => !previous
                                    )
                                }
                                aria-expanded={userMenuOpen}
                                aria-haspopup="menu"
                                aria-label="Open user account menu"
                            >
                                <div className="header-avatar">
                                    {userInitials || 'A'}
                                </div>

                                <div className="header-account-info">

                                    <strong>
                                        {user?.name || 'Administrator'}
                                    </strong>

                                    <span>{userRole}</span>
                                </div>

                                <span
                                    className={`account-chevron ${
                                        userMenuOpen ? 'rotated' : ''
                                    }`}
                                >
                                    ▾
                                </span>
                            </button>

                            {/* ACCOUNT DROPDOWN */}

                            {userMenuOpen && (
                                <div
                                    className="account-dropdown"
                                    role="menu"
                                >
                                    {/* USER DETAILS */}

                                    <div className="account-dropdown-header">

                                        <div className="account-dropdown-avatar">
                                            {userInitials || 'A'}
                                        </div>

                                        <div className="account-dropdown-identity">

                                            <strong>
                                                {user?.name || 'Administrator'}
                                            </strong>

                                            <span>
                                                {user?.email || ''}
                                            </span>

                                            <span className="account-role-badge">
                                                {userRole}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="account-dropdown-divider" />

                                    <div className="account-dropdown-label">
                                        MY ACCOUNT
                                    </div>

                                    {/* ACCOUNT LINKS */}

                                    <div className="account-dropdown-links">

                                        {accountMenuItems.map((item) => (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                className="account-dropdown-link"
                                                role="menuitem"
                                                onClick={() =>
                                                    setUserMenuOpen(false)
                                                }
                                            >
                                                <span className="account-menu-icon">
                                                    {item.icon}
                                                </span>

                                                <span className="account-menu-text">

                                                    <strong>
                                                        {item.label}
                                                    </strong>

                                                    <small>
                                                        {item.description}
                                                    </small>
                                                </span>

                                                <span className="account-menu-arrow">
                                                    ›
                                                </span>
                                            </Link>
                                        ))}
                                    </div>

                                    <div className="account-dropdown-divider" />

                                    {/* LOGOUT */}

                                    <div className="account-dropdown-footer">

                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            className="account-logout-button"
                                            role="menuitem"
                                            onClick={() =>
                                                setUserMenuOpen(false)
                                            }
                                        >
                                            <span>↪</span>
                                            <span>Sign Out</span>
                                        </Link>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                {/* PAGE CONTENT */}

                <main className="nac-content">
                    {children}
                </main>

                {/* FOOTER */}

                <footer className="nac-footer">

                    <span>
                        © {new Date().getFullYear()} {platformName}
                    </span>

                    <span>
                        Powered by NAC Technologies
                    </span>
                </footer>
            </div>
        </div>
    );
}
