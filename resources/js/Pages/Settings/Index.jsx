import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './Settings.css';

const tabs = [
    { id: 'general', label: 'General Settings', icon: '⚙️' },
    { id: 'company', label: 'Company Information', icon: '🏢' },
    { id: 'regional', label: 'Regional Settings', icon: '🌍' },
];

const settingGroups = {
    general: [
        { key: 'platform_name', label: 'Platform Name' },
        { key: 'platform_tagline', label: 'Platform Tagline' },
        { key: 'support_email', label: 'Support Email' },
        { key: 'support_phone', label: 'Support Phone' },
    ],
    company: [
        { key: 'company_name', label: 'Company Name' },
        { key: 'company_website', label: 'Company Website' },
        { key: 'company_address', label: 'Company Address' },
    ],
    regional: [
        { key: 'country', label: 'Country' },
        { key: 'currency', label: 'Default Currency' },
        { key: 'timezone', label: 'Timezone' },
        { key: 'date_format', label: 'Date Format' },
    ],
};

const formatValue = (key, value) => {
    if (value === null || value === undefined || value === '') {
        return 'Not configured';
    }

    if (key === 'date_format') {
        const formats = {
            'd/m/Y': 'DD/MM/YYYY',
            'Y-m-d': 'YYYY-MM-DD',
            'm/d/Y': 'MM/DD/YYYY',
        };

        return formats[value] || value;
    }

    if (key === 'currency') {
        const currencies = {
            TZS: 'TZS - Tanzanian Shilling',
            USD: 'USD - US Dollar',
            KES: 'KES - Kenyan Shilling',
            UGX: 'UGX - Ugandan Shilling',
        };

        return currencies[value] || value;
    }

    return value;
};

export default function Index({ settings, canManageSettings }) {
    const [activeTab, setActiveTab] = useState('general');
    const [activeView, setActiveView] = useState('edit');

    const { flash = {} } = usePage().props;

    const {
        data,
        setData,
        put,
        processing,
        errors,
    } = useForm({
        settings: settings || {},
    });

    const updateField = (field, value) => {
        setData('settings', {
            ...data.settings,
            [field]: value,
        });
    };

    const submit = (event) => {
        event.preventDefault();

        put('/settings', {
            preserveScroll: true,
            onSuccess: () => setActiveView('saved'),
        });
    };

    const field = (name, label, options = {}) => (
        <div className="setting-field" key={name}>
            <label htmlFor={name}>{label}</label>

            {options.type === 'select' ? (
                <select
                    id={name}
                    value={data.settings[name] ?? ''}
                    onChange={(event) =>
                        updateField(name, event.target.value)
                    }
                    disabled={!canManageSettings}
                >
                    {options.choices.map((choice) => (
                        <option key={choice.value} value={choice.value}>
                            {choice.label}
                        </option>
                    ))}
                </select>
            ) : options.type === 'textarea' ? (
                <textarea
                    id={name}
                    rows={3}
                    value={data.settings[name] ?? ''}
                    onChange={(event) =>
                        updateField(name, event.target.value)
                    }
                    disabled={!canManageSettings}
                    placeholder={options.placeholder || ''}
                />
            ) : (
                <input
                    id={name}
                    type={options.type || 'text'}
                    value={data.settings[name] ?? ''}
                    onChange={(event) =>
                        updateField(name, event.target.value)
                    }
                    disabled={!canManageSettings}
                    placeholder={options.placeholder || ''}
                />
            )}

            {options.description && (
                <small>{options.description}</small>
            )}

            {errors[`settings.${name}`] && (
                <span className="setting-error">
                    {errors[`settings.${name}`]}
                </span>
            )}
        </div>
    );

    const renderTab = () => {
        if (activeTab === 'general') {
            return (
                <div className="settings-form-grid">
                    {field('platform_name', 'Platform Name', {
                        placeholder: 'NACPASI',
                    })}

                    {field('platform_tagline', 'Platform Tagline', {
                        placeholder: 'Connect. Manage. Grow.',
                    })}

                    {field('support_email', 'Support Email', {
                        type: 'email',
                        placeholder: 'support@nacpasi.co.tz',
                    })}

                    {field('support_phone', 'Support Phone', {
                        placeholder: '+255...',
                    })}
                </div>
            );
        }

        if (activeTab === 'company') {
            return (
                <div className="settings-form-grid">
                    {field('company_name', 'Company Name', {
                        placeholder: 'Company or organization name',
                    })}

                    {field('company_website', 'Company Website', {
                        type: 'url',
                        placeholder: 'https://example.com',
                    })}

                    {field('company_address', 'Company Address', {
                        type: 'textarea',
                        placeholder: 'Enter company physical address',
                    })}
                </div>
            );
        }

        return (
            <div className="settings-form-grid">
                {field('country', 'Country', {
                    placeholder: 'Tanzania',
                })}

                {field('currency', 'Default Currency', {
                    type: 'select',
                    choices: [
                        { value: 'TZS', label: 'TZS - Tanzanian Shilling' },
                        { value: 'USD', label: 'USD - US Dollar' },
                        { value: 'KES', label: 'KES - Kenyan Shilling' },
                        { value: 'UGX', label: 'UGX - Ugandan Shilling' },
                    ],
                })}

                {field('timezone', 'Timezone', {
                    type: 'select',
                    choices: [
                        {
                            value: 'Africa/Dar_es_Salaam',
                            label: 'Africa/Dar_es_Salaam',
                        },
                        {
                            value: 'Africa/Nairobi',
                            label: 'Africa/Nairobi',
                        },
                        {
                            value: 'Africa/Kampala',
                            label: 'Africa/Kampala',
                        },
                        {
                            value: 'UTC',
                            label: 'UTC',
                        },
                    ],
                })}

                {field('date_format', 'Date Format', {
                    type: 'select',
                    choices: [
                        { value: 'd/m/Y', label: '25/11/2026' },
                        { value: 'Y-m-d', label: '2026-11-25' },
                        { value: 'm/d/Y', label: '11/25/2026' },
                    ],
                })}
            </div>
        );
    };

    const currentSettings = settingGroups[activeTab] || [];

    const configuredCount = currentSettings.filter(({ key }) => {
        const value = data.settings[key];
        return value !== null && value !== undefined && value !== '';
    }).length;

    return (
        <AuthenticatedLayout>
            <div className="platform-settings-page">
                <div className="settings-breadcrumb">
                    <span>Dashboard</span>
                    <span>/</span>
                    <span>Settings</span>
                    <span>/</span>
                    <strong>Platform Settings</strong>
                </div>

                <div className="settings-page-heading">
                    <div>
                        <h1>Platform Settings</h1>
                        <p>
                            Manage general information and configuration
                            for your NACPASI platform.
                        </p>
                    </div>

                    <div className="settings-heading-icon">⚙️</div>
                </div>

                {flash.success && (
                    <div className="settings-alert settings-alert-success">
                        ✓ {flash.success}
                    </div>
                )}

                {!canManageSettings && (
                    <div className="settings-alert settings-alert-info">
                        Una ruhusa ya kuangalia mipangilio hii tu.
                        Huwezi kufanya mabadiliko.
                    </div>
                )}

                <div className="settings-layout">
                    <aside className="settings-tabs">
                        <div className="settings-tabs-heading">
                            CONFIGURATION
                        </div>

                        {tabs.map((tab) => (
                            <button
                                type="button"
                                key={tab.id}
                                className={`settings-tab ${
                                    activeTab === tab.id ? 'active' : ''
                                }`}
                                onClick={() => {
                                    setActiveTab(tab.id);
                                    setActiveView('edit');
                                }}
                            >
                                <span>{tab.icon}</span>
                                <span>{tab.label}</span>
                                {activeTab === tab.id && (
                                    <span className="settings-tab-arrow">
                                        ›
                                    </span>
                                )}
                            </button>
                        ))}
                    </aside>

                    <section className="settings-content">
                        <div className="settings-content-heading">
                            <div>
                                <h2>
                                    {tabs.find(
                                        (tab) => tab.id === activeTab
                                    )?.label}
                                </h2>
                                <p>
                                    Manage and review your saved platform
                                    configuration.
                                </p>
                            </div>

                            <span className="settings-status">
                                {canManageSettings ? 'Editable' : 'Read only'}
                            </span>
                        </div>

                        <div className="settings-view-tabs">
                            <button
                                type="button"
                                className={
                                    activeView === 'edit' ? 'active' : ''
                                }
                                onClick={() => setActiveView('edit')}
                            >
                                ✏️ Edit Settings
                            </button>

                            <button
                                type="button"
                                className={
                                    activeView === 'saved' ? 'active' : ''
                                }
                                onClick={() => setActiveView('saved')}
                            >
                                ☷ Saved Settings
                            </button>
                        </div>

                        {activeView === 'edit' ? (
                            <form onSubmit={submit}>
                                <div className="settings-fields">
                                    {renderTab()}
                                </div>

                                {canManageSettings && (
                                    <div className="settings-form-footer">
                                        <span>
                                            Changes are saved to the platform
                                            database.
                                        </span>

                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="settings-save-button"
                                        >
                                            {processing
                                                ? 'Saving...'
                                                : 'Save Changes'}
                                        </button>
                                    </div>
                                )}
                            </form>
                        ) : (
                            <div className="saved-settings-section">
                                <div className="saved-settings-summary">
                                    <div>
                                        <h3>Saved Configuration</h3>
                                        <p>
                                            Current configuration values for
                                            this section.
                                        </p>
                                    </div>

                                    <span className="saved-count">
                                        {configuredCount} / {currentSettings.length} Configured
                                    </span>
                                </div>

                                <div className="saved-settings-list">
                                    {currentSettings.map(({ key, label }) => {
                                        const value = settings?.[key];

                                        const isConfigured =
                                            value !== null &&
                                            value !== undefined &&
                                            value !== '';

                                        return (
                                            <div
                                                className="saved-setting-item"
                                                key={key}
                                            >
                                                <div className="saved-setting-info">
                                                    <span className="saved-setting-label">
                                                        {label}
                                                    </span>

                                                    <strong>
                                                        {formatValue(key, value)}
                                                    </strong>

                                                    <small>
                                                        Setting key: {key}
                                                    </small>
                                                </div>

                                                <span
                                                    className={`saved-setting-status ${
                                                        isConfigured
                                                            ? 'configured'
                                                            : 'not-configured'
                                                    }`}
                                                >
                                                    {isConfigured
                                                        ? '✓ Configured'
                                                        : 'Not configured'}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>

                                {canManageSettings && (
                                    <div className="settings-form-footer">
                                        <span>
                                            These values are loaded from the
                                            platform database.
                                        </span>

                                        <button
                                            type="button"
                                            className="settings-save-button"
                                            onClick={() => setActiveView('edit')}
                                        >
                                            ✏️ Edit Settings
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
