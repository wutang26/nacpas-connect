import './NetworkRecords.css';

function date(value) {
    if (!value) return '—';

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime())
        ? '—'
        : parsed.toLocaleString();
}

function Status({ value }) {
    const status = String(value || 'unknown').toLowerCase();

    return (
        <span className={`ncr-status ncr-status-${status}`}>
            {status.replaceAll('_', ' ')}
        </span>
    );
}

function Section({ title, description, count, children, empty }) {
    return (
        <section className="ncr-section">
            <div className="ncr-heading">
                <div>
                    <h3>{title}</h3>
                    <p>{description}</p>
                </div>
                <span className="ncr-count">{count} total</span>
            </div>

            {empty ? (
                <div className="ncr-empty">
                    <span>▤</span>
                    <strong>No records available</strong>
                    <p>Records zitaonekana hapa baada ya mfumo kuzihifadhi.</p>
                </div>
            ) : (
                <div className="ncr-table-wrap">
                    {children}
                </div>
            )}
        </section>
    );
}

export default function NetworkRecords({ records = {} }) {
    const counts = records.counts || {};

    const integrations = records.integrations || [];
    const credentials = records.credentials || [];
    const commands = records.commands || [];
    const sessions = records.sessions || [];
    const logs = records.logs || [];

    return (
        <div className="ncr-container">
            <div className="ncr-title">
                <div>
                    <span className="ncr-eyebrow">DATABASE MONITOR</span>
                    <h2>Network Database Records</h2>
                    <p>
                        View stored network integration, credential,
                        command, session and event records.
                    </p>
                </div>
            </div>

            <div className="ncr-summary">
                {[
                    ['Integrations', counts.integrations ?? 0],
                    ['Credentials', counts.credentials ?? 0],
                    ['Commands', counts.commands ?? 0],
                    ['WiFi Sessions', counts.sessions ?? 0],
                    ['Network Logs', counts.logs ?? 0],
                ].map(([label, value]) => (
                    <div className="ncr-summary-card" key={label}>
                        <span>{label}</span>
                        <strong>{value}</strong>
                    </div>
                ))}
            </div>

            <Section
                title="Network Integrations"
                description="Registered external network controllers."
                count={counts.integrations || 0}
                empty={integrations.length === 0}
            >
                <table>
                    <thead>
                        <tr>
                            <th>Controller</th>
                            <th>Provider</th>
                            <th>Site</th>
                            <th>API URL</th>
                            <th>Version</th>
                            <th>Controller Site ID</th>
                            <th>Status</th>
                            <th>Last Authentication</th>
                        </tr>
                    </thead>
                    <tbody>
                        {integrations.map((item) => (
                            <tr key={item.id}>
                                <td>
                                    <strong>{item.name}</strong>
                                    <small>Record #{item.id}</small>
                                </td>
                                <td>{item.provider}</td>
                                <td>{item.site?.name || '—'}</td>
                                <td className="ncr-url">{item.controller_url || '—'}</td>
                                <td>{item.controller_version || '—'}</td>
                                <td>{item.controller_site_id || 'Not mapped'}</td>
                                <td><Status value={item.status} /></td>
                                <td>{date(item.last_connected_at)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Section>

            <Section
                title="Network Credentials"
                description="Credential metadata only. Passwords and encrypted secrets are never displayed."
                count={counts.credentials || 0}
                empty={credentials.length === 0}
            >
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>Credential Type</th>
                            <th>Username</th>
                            <th>Site</th>
                            <th>Device ID</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        {credentials.map((item) => (
                            <tr key={item.id}>
                                <td>#{item.id}</td>
                                <td>{item.provider}</td>
                                <td>{item.credential_type}</td>
                                <td>{item.username || '—'}</td>
                                <td>{item.site || '—'}</td>
                                <td>{item.device_id ?? '—'}</td>
                                <td><Status value={item.status} /></td>
                                <td>{date(item.created_at)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Section>

            <Section
                title="Network Commands"
                description="Remote network commands and their execution states."
                count={counts.commands || 0}
                empty={commands.length === 0}
            >
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Command UUID</th>
                            <th>Command Type</th>
                            <th>Target MAC</th>
                            <th>Integration</th>
                            <th>Site</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th>Last Attempt</th>
                            <th>Executed</th>
                        </tr>
                    </thead>
                    <tbody>
                        {commands.map((item) => (
                            <tr key={item.id}>
                                <td>#{item.id}</td>
                                <td className="ncr-mono">
                                    {item.command_uuid || '—'}
                                </td>
                                <td>{item.command_type}</td>
                                <td className="ncr-mono">{item.target_mac || '—'}</td>
                                <td>{item.integration || '—'}</td>
                                <td>{item.site || '—'}</td>
                                <td>{item.attempts}</td>
                                <td><Status value={item.status} /></td>
                                <td>{date(item.last_attempt_at)}</td>
                                <td>{date(item.executed_at)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Section>

            <Section
                title="WiFi Sessions"
                description="Client connection sessions recorded by NACPASI."
                count={counts.sessions || 0}
                empty={sessions.length === 0}
            >
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Site</th>
                            <th>Client MAC</th>
                            <th>Client IP</th>
                            <th>Subscription ID</th>
                            <th>Device ID</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Expires</th>
                            <th>Ended</th>
                            <th>Bytes In</th>
                            <th>Bytes Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sessions.map((item) => (
                            <tr key={item.id}>
                                <td>#{item.id}</td>
                                <td>{item.site || '—'}</td>
                                <td className="ncr-mono">{item.client_mac || '—'}</td>
                                <td className="ncr-mono">{item.client_ip || '—'}</td>
                                <td>{item.subscription_id ?? '—'}</td>
                                <td>{item.device_id ?? '—'}</td>
                                <td><Status value={item.status} /></td>
                                <td>{date(item.started_at)}</td>
                                <td>{date(item.expires_at)}</td>
                                <td>{date(item.ended_at)}</td>
                                <td>{item.bytes_in ?? 0}</td>
                                <td>{item.bytes_out ?? 0}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Section>

            <Section
                title="Network Logs"
                description="Recorded network events, warnings and errors."
                count={counts.logs || 0}
                empty={logs.length === 0}
            >
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Level</th>
                            <th>Event</th>
                            <th>Message</th>
                            <th>Site</th>
                            <th>Device ID</th>
                            <th>Occurred At</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.map((item) => (
                            <tr key={item.id}>
                                <td>#{item.id}</td>
                                <td><Status value={item.level} /></td>
                                <td>{item.event}</td>
                                <td className="ncr-message">{item.message || '—'}</td>
                                <td>{item.site || '—'}</td>
                                <td>{item.device_id ?? '—'}</td>
                                <td>{date(item.occurred_at)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Section>

            <div className="ncr-security-note">
                <strong>Data protection</strong>
                <p>
                    Passwords, encrypted credential secrets, API tokens,
                    raw command payloads, raw API responses and log context
                    JSON are not exposed by this viewer.
                </p>
            </div>
        </div>
    );
}
