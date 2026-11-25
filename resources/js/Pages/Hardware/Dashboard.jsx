import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import './Dashboard.css';

const statistics = [
    {
        title: 'Products',
        value: '—',
        description: 'Hardware product catalogue',
        icon: '📦',
    },
    {
        title: 'Stock Assets',
        value: '—',
        description: 'Registered physical devices',
        icon: '🖥️',
    },
    {
        title: 'Device Sales',
        value: '—',
        description: 'Hardware sales records',
        icon: '🛒',
    },
    {
        title: 'Installations',
        value: '—',
        description: 'Customer installation records',
        icon: '🔧',
    },
];

const lifecycle = [
    {
        number: '01',
        title: 'Purchasing',
        description:
            'Record hardware purchases from suppliers and capture purchase costs.',
        icon: '🧾',
    },
    {
        number: '02',
        title: 'Inventory',
        description:
            'Register each physical device with its own NACPASI Asset ID, serial number and MAC address.',
        icon: '📦',
    },
    {
        number: '03',
        title: 'Device Sales',
        description:
            'Record sales, invoices and payments linked to the business organization.',
        icon: '💳',
    },
    {
        number: '04',
        title: 'Installation',
        description:
            'Assign sold hardware to an organization and its installation site.',
        icon: '🛠️',
    },
    {
        number: '05',
        title: 'Network Management',
        description:
            'Connect installed equipment to supported network controllers for remote monitoring and management.',
        icon: '📡',
    },
];

export default function HardwareDashboard() {
    return (
        <AuthenticatedLayout>
            <Head title="Hardware Management" />

            <div className="hardware-dashboard">
                <div className="hardware-heading">
                    <div>
                        <span className="hardware-eyebrow">
                            NACPASI BUSINESS OPERATIONS
                        </span>

                        <h1>Hardware Management</h1>

                        <p>
                            Manage hardware products, purchases, inventory,
                            sales and customer installations.
                        </p>
                    </div>

                    <div className="hardware-heading-icon">
                        🖥️
                    </div>
                </div>

                <div className="hardware-status">
                    <div className="hardware-status-icon">ℹ</div>

                    <div>
                        <strong>Module setup in progress</strong>

                        <p>
                            Hardware Management is being configured.
                            Inventory and transaction statistics will appear
                            after the respective modules and database tables
                            have been implemented.
                        </p>
                    </div>
                </div>

                <div className="hardware-statistics">
                    {statistics.map((stat) => (
                        <div className="hardware-stat-card" key={stat.title}>
                            <div className="hardware-stat-top">
                                <div>
                                    <span>{stat.title}</span>
                                    <h2>{stat.value}</h2>
                                </div>

                                <div className="hardware-stat-icon">
                                    {stat.icon}
                                </div>
                            </div>

                            <p>{stat.description}</p>
                        </div>
                    ))}
                </div>

                <section className="hardware-panel">
                    <div className="hardware-panel-heading">
                        <div>
                            <h2>Hardware Business Workflow</h2>

                            <p>
                                The lifecycle of every physical device
                                managed through NACPASI.
                            </p>
                        </div>
                    </div>

                    <div className="hardware-lifecycle">
                        {lifecycle.map((step) => (
                            <div
                                className="hardware-lifecycle-item"
                                key={step.number}
                            >
                                <div className="hardware-step-number">
                                    {step.number}
                                </div>

                                <div className="hardware-step-icon">
                                    {step.icon}
                                </div>

                                <div className="hardware-step-content">
                                    <h3>{step.title}</h3>
                                    <p>{step.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="hardware-panel hardware-principles">
                    <div className="hardware-panel-heading">
                        <div>
                            <h2>Important System Structure</h2>

                            <p>
                                Hardware operations will remain separate
                                from hotspot billing and NACPASI SaaS billing.
                            </p>
                        </div>
                    </div>

                    <div className="hardware-principle-grid">
                        <div>
                            <span className="principle-icon">🏢</span>
                            <h3>Organization ownership</h3>
                            <p>
                                Hardware sales and installations will be
                                associated with business organizations,
                                not end-user WiFi customer records.
                            </p>
                        </div>

                        <div>
                            <span className="principle-icon">🔐</span>
                            <h3>Asset traceability</h3>
                            <p>
                                Each serialized physical device will have
                                its own NACPASI Asset ID and ownership history.
                            </p>
                        </div>

                        <div>
                            <span className="principle-icon">📡</span>
                            <h3>Remote management</h3>
                            <p>
                                Installed devices will be linked to supported
                                network integrations for remote operations.
                            </p>
                        </div>

                        <div>
                            <span className="principle-icon">💰</span>
                            <h3>Separate billing</h3>
                            <p>
                                Hardware sales, WiFi customer payments and
                                NACPASI SaaS subscriptions will remain separate.
                            </p>
                        </div>
                    </div>
                </section>

                <div className="hardware-footer-note">
                    <strong>Next implementation stage</strong>

                    <p>
                        Product Catalogue → Suppliers → Purchases →
                        Inventory → Device Sales → Installations.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
