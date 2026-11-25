
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import './Dashboard.css';

function formatPlatformDate(date, format, timezone) {
    const parts = new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: timezone,
    }).formatToParts(date);

    const values = {};

    parts.forEach((part) => {
        if (part.type !== 'literal') {
            values[part.type] = part.value;
        }
    });

    switch (format) {
        case 'Y-m-d':
            return `${values.year}-${values.month}-${values.day}`;

        case 'm/d/Y':
            return `${values.month}/${values.day}/${values.year}`;

        case 'd/m/Y':
        default:
            return `${values.day}/${values.month}/${values.year}`;
    }
}

export default function Dashboard() {
    const { platformSettings = {} } = usePage().props;

    const platformName = platformSettings.platform_name || 'NACPASI';
    const currency = platformSettings.currency || 'TZS';
    const timezone = platformSettings.timezone || 'Africa/Dar_es_Salaam';
    const dateFormat = platformSettings.date_format || 'd/m/Y';

    const today = formatPlatformDate(
        new Date(),
        dateFormat,
        timezone
    );

    const statistics = [
        {
            title: 'Organizations',
            value: '0',
            description: 'Registered businesses',
            icon: '🏢',
        },
        {
            title: 'Total Sites',
            value: '0',
            description: 'WiFi hotspot locations',
            icon: '📍',
        },
        {
            title: 'Platform Users',
            value: '0',
            description: 'Registered platform users',
            icon: '👥',
        },
        {
            title: 'SaaS Revenue',
            value: `${currency} 0`,
            description: 'Total collected revenue',
            icon: '💰',
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Platform Dashboard" />

            <div className="dashboard-page">
                <div className="dashboard-heading">
                    <div>
                        <h1>Dashboard Overview</h1>
                        <p>
                            Welcome to {platformName} platform administration.
                        </p>
                    </div>

                    <div className="dashboard-date">
                        {today}
                    </div>
                </div>

                <div className="statistics-grid">
                    {statistics.map((stat) => (
                        <div className="stat-card" key={stat.title}>
                            <div className="stat-card-top">
                                <div>
                                    <span className="stat-title">
                                        {stat.title}
                                    </span>
                                    <h2>{stat.value}</h2>
                                </div>

                                <div className="stat-icon">
                                    {stat.icon}
                                </div>
                            </div>

                            <p>{stat.description}</p>
                        </div>
                    ))}
                </div>

                <div className="dashboard-grid">
                    <section className="dashboard-panel">
                        <div className="panel-heading">
                            <div>
                                <h3>Platform Overview</h3>
                                <p>General platform information</p>
                            </div>
                        </div>

                        <div className="overview-content">
                            <div className="overview-item">
                                <span>Platform Name</span>
                                <strong>{platformName}</strong>
                            </div>

                            <div className="overview-item">
                                <span>Platform Status</span>
                                <strong className="online-status">
                                    <i></i> Operational
                                </strong>
                            </div>

                            <div className="overview-item">
                                <span>Business Model</span>
                                <strong>Multi-tenant SaaS</strong>
                            </div>

                            <div className="overview-item">
                                <span>Default Currency</span>
                                <strong>{currency}</strong>
                            </div>

                            <div className="overview-item">
                                <span>Timezone</span>
                                <strong>{timezone}</strong>
                            </div>

                            <div className="overview-item">
                                <span>Date Format</span>
                                <strong>{dateFormat}</strong>
                            </div>
                        </div>
                    </section>

                    <section className="dashboard-panel">
                        <div className="panel-heading">
                            <div>
                                <h3>Quick Access</h3>
                                <p>Platform management shortcuts</p>
                            </div>
                        </div>

                        <div className="quick-links">
                            <a href="/organizations">
                                <span>🏢</span>
                                <div>
                                    <strong>Organizations</strong>
                                    <small>Manage registered businesses</small>
                                </div>
                                <b>→</b>
                            </a>

                            <a href="/saas-plans">
                                <span>📦</span>
                                <div>
                                    <strong>SaaS Plans</strong>
                                    <small>Manage platform subscription plans</small>
                                </div>
                                <b>→</b>
                            </a>

                            <a href="/subscriptions">
                                <span>🔄</span>
                                <div>
                                    <strong>Subscriptions</strong>
                                    <small>Monitor business subscriptions</small>
                                </div>
                                <b>→</b>
                            </a>

                            <a href="/saas-payments">
                                <span>💳</span>
                                <div>
                                    <strong>SaaS Payments</strong>
                                    <small>View business payments</small>
                                </div>
                                <b>→</b>
                            </a>
                        </div>
                    </section>
                </div>

                <div className="dashboard-notice">
                    <strong>Dashboard setup</strong>
                    <p>
                        These figures are currently placeholders. We will
                        connect them to real database statistics when we
                        implement the platform dashboard controller.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
