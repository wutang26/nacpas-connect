import { useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import './EmailConfiguration.css';

export default function EmailConfiguration({
    emailSettings,
    canManageEmailSettings,
}) {
    const { flash = {} } = usePage().props;

    const form = useForm({
        enabled: emailSettings?.enabled ?? false,
        host: emailSettings?.host ?? '',
        port: emailSettings?.port ?? '587',
        username: emailSettings?.username ?? '',
        password: '',
        encryption: emailSettings?.encryption ?? 'tls',
        from_address: emailSettings?.from_address ?? '',
        from_name: emailSettings?.from_name ?? 'NACPASI',
    });

    const testForm = useForm({
        email: '',
    });

    const updateField = (field, value) => {
        form.setData(field, value);
    };

    const saveSettings = (event) => {
        event.preventDefault();

        form.put('/settings/email', {
            preserveScroll: true,
            onSuccess: () => form.reset('password'),
        });
    };

    const sendTestEmail = (event) => {
        event.preventDefault();

        testForm.post('/settings/email/test', {
            preserveScroll: true,
        });
    };

    const fieldError = (field) => {
        if (!form.errors[field]) return null;

        return (
            <span className="email-field-error">
                {form.errors[field]}
            </span>
        );
    };

    return (
        <AuthenticatedLayout>
            <div className="email-configuration-page">
                <div className="email-breadcrumb">
                    <span>Dashboard</span>
                    <span>/</span>
                    <span>Settings</span>
                    <span>/</span>
                    <strong>Email Configuration</strong>
                </div>

                <div className="email-page-heading">
                    <div>
                        <h1>Email Configuration</h1>
                        <p>
                            Configure SMTP to send NACPASI emails such as
                            account verification and password reset messages.
                        </p>
                    </div>

                    <div className="email-heading-icon">✉️</div>
                </div>

                {flash.success && (
                    <div className="email-alert email-alert-success">
                        ✓ {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="email-alert email-alert-error">
                        {flash.error}
                    </div>
                )}

                {!canManageEmailSettings && (
                    <div className="email-alert email-alert-info">
                        Una ruhusa ya kuangalia Email Configuration tu.
                    </div>
                )}

                <div className="email-status-card">
                    <div>
                        <strong>SMTP Service</strong>
                        <p>
                            {emailSettings?.enabled
                                ? 'SMTP imewashwa kwenye platform.'
                                : 'SMTP haijawashwa. Mfumo unatumia mail configuration ya .env.'}
                        </p>
                    </div>

                    <span
                        className={`email-status ${
                            emailSettings?.enabled ? 'enabled' : 'disabled'
                        }`}
                    >
                        {emailSettings?.enabled ? 'Enabled' : 'Disabled'}
                    </span>
                </div>

                <form onSubmit={saveSettings} className="email-config-card">
                    <div className="email-section-heading">
                        <div>
                            <h2>SMTP Server Settings</h2>
                            <p>
                                Enter the SMTP details provided by your email
                                hosting provider.
                            </p>
                        </div>
                    </div>

                    <div className="email-enable-row">
                        <div>
                            <strong>Enable SMTP</strong>
                            <p>
                                Use this SMTP server for outgoing platform
                                emails.
                            </p>
                        </div>

                        <label className="email-switch">
                            <input
                                type="checkbox"
                                checked={form.data.enabled}
                                onChange={(event) =>
                                    updateField('enabled', event.target.checked)
                                }
                                disabled={!canManageEmailSettings}
                            />
                            <span className="email-switch-slider" />
                        </label>
                    </div>

                    <div className="email-fields-grid">
                        <div className="email-field">
                            <label htmlFor="smtp_host">SMTP Host</label>
                            <input
                                id="smtp_host"
                                type="text"
                                value={form.data.host}
                                onChange={(event) =>
                                    updateField('host', event.target.value)
                                }
                                placeholder="smtp.example.com"
                                disabled={!canManageEmailSettings}
                            />
                            {fieldError('host')}
                        </div>

                        <div className="email-field">
                            <label htmlFor="smtp_port">SMTP Port</label>
                            <input
                                id="smtp_port"
                                type="number"
                                min="1"
                                max="65535"
                                value={form.data.port}
                                onChange={(event) =>
                                    updateField('port', event.target.value)
                                }
                                placeholder="587"
                                disabled={!canManageEmailSettings}
                            />
                            {fieldError('port')}
                        </div>

                        <div className="email-field">
                            <label htmlFor="smtp_username">SMTP Username</label>
                            <input
                                id="smtp_username"
                                type="text"
                                autoComplete="username"
                                value={form.data.username}
                                onChange={(event) =>
                                    updateField('username', event.target.value)
                                }
                                placeholder="your-email@example.com"
                                disabled={!canManageEmailSettings}
                            />
                            {fieldError('username')}
                        </div>

                        <div className="email-field">
                            <label htmlFor="smtp_password">SMTP Password</label>
                            <input
                                id="smtp_password"
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) =>
                                    updateField('password', event.target.value)
                                }
                                placeholder={
                                    emailSettings?.password_configured
                                        ? 'Saved password — leave blank to keep it'
                                        : 'Enter SMTP password'
                                }
                                disabled={!canManageEmailSettings}
                            />
                            {fieldError('password')}
                            <small>
                                {emailSettings?.password_configured
                                    ? 'Password tayari imehifadhiwa kwa encryption. Iache wazi kama hutaki kuibadilisha.'
                                    : 'Password itahifadhiwa ikiwa encrypted.'}
                            </small>
                        </div>

                        <div className="email-field">
                            <label htmlFor="smtp_encryption">Encryption</label>
                            <select
                                id="smtp_encryption"
                                value={form.data.encryption}
                                onChange={(event) =>
                                    updateField('encryption', event.target.value)
                                }
                                disabled={!canManageEmailSettings}
                            >
                                <option value="tls">TLS / STARTTLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                            {fieldError('encryption')}
                        </div>
                    </div>

                    <div className="email-section-heading email-sender-heading">
                        <div>
                            <h2>Sender Information</h2>
                            <p>
                                These details appear as the sender of outgoing
                                NACPASI emails.
                            </p>
                        </div>
                    </div>

                    <div className="email-fields-grid">
                        <div className="email-field">
                            <label htmlFor="from_address">Sender Email</label>
                            <input
                                id="from_address"
                                type="email"
                                value={form.data.from_address}
                                onChange={(event) =>
                                    updateField('from_address', event.target.value)
                                }
                                placeholder="noreply@nacpasi.co.tz"
                                disabled={!canManageEmailSettings}
                            />
                            {fieldError('from_address')}
                        </div>

                        <div className="email-field">
                            <label htmlFor="from_name">Sender Name</label>
                            <input
                                id="from_name"
                                type="text"
                                value={form.data.from_name}
                                onChange={(event) =>
                                    updateField('from_name', event.target.value)
                                }
                                placeholder="NACPASI"
                                disabled={!canManageEmailSettings}
                            />
                            {fieldError('from_name')}
                        </div>
                    </div>

                    {canManageEmailSettings && (
                        <div className="email-form-footer">
                            <span>
                                SMTP password haitawahi kuonyeshwa kwenye ukurasa
                                huu baada ya kuhifadhiwa.
                            </span>

                            <button
                                type="submit"
                                disabled={form.processing}
                            >
                                {form.processing
                                    ? 'Saving...'
                                    : 'Save Email Configuration'}
                            </button>
                        </div>
                    )}
                </form>

                <form
                    onSubmit={sendTestEmail}
                    className="email-config-card email-test-card"
                >
                    <div className="email-section-heading">
                        <div>
                            <h2>Send Test Email</h2>
                            <p>
                                Test whether the saved SMTP configuration can
                                successfully deliver an email.
                            </p>
                        </div>
                        <span className="email-test-icon">➤</span>
                    </div>

                    <div className="email-test-row">
                        <div className="email-field">
                            <label htmlFor="test_email">
                                Recipient Email Address
                            </label>
                            <input
                                id="test_email"
                                type="email"
                                value={testForm.data.email}
                                onChange={(event) =>
                                    testForm.setData('email', event.target.value)
                                }
                                placeholder="your-email@example.com"
                                required
                                disabled={!canManageEmailSettings}
                            />
                            {testForm.errors.email && (
                                <span className="email-field-error">
                                    {testForm.errors.email}
                                </span>
                            )}
                        </div>

                        {canManageEmailSettings && (
                            <button
                                type="submit"
                                disabled={
                                    testForm.processing
                                    || !emailSettings?.enabled
                                }
                            >
                                {testForm.processing
                                    ? 'Sending...'
                                    : 'Send Test Email'}
                            </button>
                        )}
                    </div>

                    {!emailSettings?.enabled && (
                        <small className="email-test-hint">
                            Hifadhi na washa SMTP kwanza ili uweze kutuma test email.
                        </small>
                    )}
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
