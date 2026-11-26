
import { Head, Link, router, usePage, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './NetworkConnections.css';
import NetworkRecords from './NetworkRecords';

function formatDate(value) {
    if (!value) return 'Never';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return 'Unknown';

    return date.toLocaleString();
}

function StatusBadge({ status }) {
    const normalized = String(status || 'pending').toLowerCase();

    const labels = {
        active: 'Previously authenticated',
        pending: 'Not tested',
        inactive: 'Inactive',
        error: 'Authentication error',
    };

    return (
        <span className={`nc-status nc-status-${normalized}`}>
            <span className="nc-status-dot" />
            {labels[normalized] || normalized}
        </span>
    );
}

function FormField({
    label,
    name,
    value,
    onChange,
    error,
    placeholder,
    type = 'text',
    required = false,
    help,
}) {
    return (
        <div className="nc-form-field">
            <label htmlFor={`nc-${name}`}>
                {label}
                {required && <span className="nc-required"> *</span>}
            </label>

            <input
                id={`nc-${name}`}
                type={type}
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                autoComplete="off"
            />

            {help && <small className="nc-field-help">{help}</small>}

            {error && <small className="nc-field-error">{error}</small>}
        </div>
    );
}

function OmadaSettingsForm({ integration }) {
    const form = useForm({
        controller_url: integration.controller_url || '',
        controller_version: integration.controller_version || '',
        controller_site_id: integration.controller_site_id || '',
        controller_id: integration.controller_id || '',
        api_region: integration.api_region || '',
        hotspot_username: integration.credential_username || '',
        hotspot_password: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.put(
            `/network-connections/${integration.id}/settings`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    form.setData('hotspot_password', '');
                },
            }
        );
    };

    return (
        <section className="nc-settings-section">
            <div className="nc-settings-heading">
                <div>
                    <h3>Omada Integration Configuration</h3>
                    <p>
                        Configure the Omada Cloud API connection and
                        Hotspot Operator authentication credentials.
                    </p>
                </div>

                <span className="nc-settings-lock">SECURE SETTINGS</span>
            </div>

            <form onSubmit={submit}>
                <div className="nc-form-grid">
                    <FormField
                        label="Controller API URL"
                        name="controller_url"
                        value={form.data.controller_url}
                        onChange={(value) => form.setData('controller_url', value)}
                        error={form.errors.controller_url}
                        placeholder="https://euw1-api-omada-essential-controller.tplinkcloud.com"
                        required
                        help="Tumia Omada Cloud API endpoint, si device inform URL."
                    />

                    <FormField
                        label="Controller Version"
                        name="controller_version"
                        value={form.data.controller_version}
                        onChange={(value) => form.setData('controller_version', value)}
                        error={form.errors.controller_version}
                        placeholder="6.3.0.200"
                    />

                    <FormField
                        label="Controller ID"
                        name="controller_id"
                        value={form.data.controller_id}
                        onChange={(value) => form.setData('controller_id', value)}
                        error={form.errors.controller_id}
                        placeholder="Omada Controller ID"
                        required
                    />

                    <FormField
                        label="API Region"
                        name="api_region"
                        value={form.data.api_region}
                        onChange={(value) => form.setData('api_region', value)}
                        error={form.errors.api_region}
                        placeholder="euw1"
                        required
                    />

                    <FormField
                        label="Omada Controller Site ID"
                        name="controller_site_id"
                        value={form.data.controller_site_id}
                        onChange={(value) => form.setData('controller_site_id', value)}
                        error={form.errors.controller_site_id}
                        placeholder="Leave blank until the actual site ID is known"
                        help="Hii si NACPASI Site ID. Usibuni thamani yake."
                    />

                    <FormField
                        label="Hotspot Operator Username"
                        name="hotspot_username"
                        value={form.data.hotspot_username}
                        onChange={(value) => form.setData('hotspot_username', value)}
                        error={form.errors.hotspot_username}
                        placeholder="Omada Hotspot Operator username"
                        required
                    />

                    <FormField
                        label="Hotspot Operator Password"
                        name="hotspot_password"
                        type="password"
                        value={form.data.hotspot_password}
                        onChange={(value) => form.setData('hotspot_password', value)}
                        error={form.errors.hotspot_password}
                        placeholder="Enter new password or leave blank"
                        help="Password ya zamani itabaki ikiwa utaacha sehemu hii tupu."
                    />
                </div>

                <div className="nc-settings-footer">
                    <p>
                        <strong>Security:</strong> Password inasimbwa
                        (encrypted) kabla ya kuhifadhiwa. Haitarudishwa
                        kwenye frontend.
                    </p>

                    <button
                        type="submit"
                        className="nc-save-button"
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <>
                                <span className="nc-spinner" />
                                Saving Configuration...
                            </>
                        ) : (
                            'Save Omada Configuration'
                        )}
                    </button>
                </div>
            </form>
        </section>
    );
}

function IntegrationCard({
    integration,
    lastTest,
    onTest,
    testing,
    canManageCredentials,
}) {
    const isOmada = integration.provider === 'omada';

    const testResult =
        lastTest?.integration_id === integration.id
            ? lastTest
            : null;

    return (
        <section className="nc-card">
            <div className="nc-card-top">
                <div className="nc-provider">
                    <div className="nc-provider-logo">
                        {isOmada ? 'O' : 'N'}
                    </div>

                    <div>
                        <span className="nc-eyebrow">
                            {integration.provider.toUpperCase()} CONTROLLER
                        </span>

                        <h2>{integration.name}</h2>

                        <p>
                            {integration.organization?.name || 'Organization'}
                            {' / '}
                            {integration.site?.name || 'Site'}
                        </p>
                    </div>
                </div>

                <StatusBadge status={integration.status} />
            </div>

            <div className="nc-card-divider" />

            <div className="nc-information-grid">
                <div className="nc-information">
                    <span>Controller API URL</span>
                    <strong className="nc-url">
                        {integration.controller_url}
                    </strong>
                </div>

                <div className="nc-information">
                    <span>Configured Version</span>
                    <strong>
                        {integration.controller_version || 'Not configured'}
                    </strong>
                </div>

                <div className="nc-information">
                    <span>NACPASI Site</span>
                    <strong>
                        {integration.site?.name || 'Not assigned'}
                    </strong>
                </div>

                <div className="nc-information">
                    <span>Omada Controller Site ID</span>
                    <strong>
                        {integration.controller_site_id || 'Not mapped'}
                    </strong>
                </div>

                <div className="nc-information">
                    <span>Controller ID Configuration</span>
                    <strong>
                        {integration.controller_id_configured
                            ? 'Configured'
                            : 'Missing'}
                    </strong>
                </div>

                <div className="nc-information">
                    <span>Last Successful Authentication</span>
                    <strong>
                        {formatDate(integration.last_connected_at)}
                    </strong>
                </div>
            </div>

            {canManageCredentials && isOmada && (
                <OmadaSettingsForm integration={integration} />
            )}

            <div className="nc-test-section">
                <div className="nc-test-heading">
                    <div>
                        <h3>API Authentication Test</h3>
                        <p>
                            Sends a fresh login request to the controller API.
                        </p>
                    </div>

                    <button
                        type="button"
                        className="nc-test-button"
                        onClick={() => onTest(integration.id)}
                        disabled={testing}
                    >
                        {testing ? (
                            <>
                                <span className="nc-spinner" />
                                Testing...
                            </>
                        ) : (
                            <>
                                <span>↻</span>
                                Test Connection
                            </>
                        )}
                    </button>
                </div>

                {testResult && (
                    <div className={`nc-test-result ${
                        testResult.success ? 'success' : 'failure'
                    }`}>
                        <div className="nc-result-icon">
                            {testResult.success ? '✓' : '!'}
                        </div>

                        <div className="nc-result-content">
                            <strong>
                                {testResult.success
                                    ? 'Authentication Successful'
                                    : 'Authentication Failed'}
                            </strong>

                            <p>{testResult.message}</p>

                            <div className="nc-result-meta">
                                <span>
                                    Response time: {testResult.latency_ms} ms
                                </span>

                                <span>
                                    Tested: {formatDate(testResult.tested_at)}
                                </span>
                            </div>
                        </div>
                    </div>
                )}

                {!testResult && integration.last_error && (
                    <div className="nc-previous-error">
                        <strong>Last recorded error</strong>
                        <p>{integration.last_error}</p>
                    </div>
                )}
            </div>

            <div className="nc-capability-section">
                <h3>Integration Capabilities</h3>

                <div className="nc-capability-grid">
                    <div className="nc-capability">
                        <span className="nc-capability-indicator available" />
                        <div>
                            <strong>API Login</strong>
                            <small>Authentication test available</small>
                        </div>
                    </div>

                    <div className="nc-capability">
                        <span className="nc-capability-indicator unavailable" />
                        <div>
                            <strong>Client Authorization</strong>
                            <small>Not implemented yet</small>
                        </div>
                    </div>

                    <div className="nc-capability">
                        <span className="nc-capability-indicator unavailable" />
                        <div>
                            <strong>Client Disconnection</strong>
                            <small>Not implemented yet</small>
                        </div>
                    </div>

                    <div className="nc-capability">
                        <span className="nc-capability-indicator unavailable" />
                        <div>
                            <strong>Live Device Discovery</strong>
                            <small>Not implemented yet</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default function Index({
    integrations = [],
    canManageCredentials = false,
    records = {},
}) {
    const { flash = {} } = usePage().props;
    const [testingId, setTestingId] = useState(null);

    const lastTest = flash.connection_test || null;

    const testConnection = (integrationId) => {
        if (testingId !== null) return;

        setTestingId(integrationId);

        router.post(
            `/network-connections/${integrationId}/test`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setTestingId(null),
            }
        );
    };

    const successfulTests = lastTest?.success ? 1 : 0;

    return (
        <AuthenticatedLayout>
            <Head title="Network Connections" />

            <div className="nc-page">
                <div className="nc-breadcrumb">
                    <Link href="/dashboard">Dashboard</Link>
                    <span>/</span>
                    <span>Network Connections</span>
                </div>

                <div className="nc-page-heading">
                    <div>
                        <span className="nc-eyebrow">
                            NETWORK INFRASTRUCTURE
                        </span>

                        <h1>Network Connection Monitor</h1>

                        <p>
                            Monitor the connection and API authentication
                            between NACPASI and external network controllers.
                        </p>
                    </div>

                    <div className="nc-heading-icon">⌁</div>
                </div>

                <div className="nc-notice">
                    <span className="nc-notice-icon">ⓘ</span>

                    <p>
                        <strong>Important:</strong> A successful API login
                        confirms authentication only. It does not prove that
                        access points or switches are online, nor that remote
                        network commands are working.
                    </p>
                </div>

                {flash.success && (
                    <div className="nc-flash-success">
                        {flash.success}
                    </div>
                )}

                <div className="nc-summary">
                    <div className="nc-summary-card">
                        <span>Configured Integrations</span>
                        <strong>{integrations.length}</strong>
                        <small>Controllers registered in NACPASI</small>
                    </div>

                    <div className="nc-summary-card">
                        <span>Successful Latest Test</span>
                        <strong>{successfulTests}</strong>
                        <small>
                            {lastTest
                                ? 'Latest test result in this session'
                                : 'No test result in this session'}
                        </small>
                    </div>

                    <div className="nc-summary-card">
                        <span>Management Providers</span>
                        <strong>
                            {new Set(integrations.map((item) => item.provider)).size}
                        </strong>
                        <small>Distinct controller providers</small>
                    </div>
                </div>

                <div className="nc-section-heading">
                    <div>
                        <h2>Registered Controllers</h2>
                        <p>
                            Select a controller to perform a fresh
                            authentication test.
                        </p>
                    </div>

                    <span className="nc-count">
                        {integrations.length} integration(s)
                    </span>
                </div>

                {integrations.length === 0 ? (
                    <div className="nc-empty">
                        <div>⌁</div>
                        <h3>No network controllers registered</h3>
                        <p>
                            Network controller integrations will appear here
                            after they are configured for a site.
                        </p>
                    </div>
                ) : (
                    <div className="nc-integrations">
                        {integrations.map((integration) => (
                            <IntegrationCard
                                key={integration.id}
                                integration={integration}
                                lastTest={lastTest}
                                onTest={testConnection}
                                testing={testingId === integration.id}
                                canManageCredentials={canManageCredentials}
                            />
                        ))}
                    </div>
                )}

                <NetworkRecords records={records} />

                <div className="nc-footer-note">
                    <strong>Security</strong>
                    <p>
                        Controller credentials, API tokens and session cookies
                        are handled on the server. They are never displayed
                        on this page.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
