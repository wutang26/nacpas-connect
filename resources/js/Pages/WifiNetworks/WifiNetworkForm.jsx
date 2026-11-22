
import { useForm, Link } from '@inertiajs/react';

export default function WifiNetworkForm({
    sites = [],
    selectedSiteId = null,
    network = null,
    mode = 'create',
}) {
    const editing = mode === 'edit';

    const { data, setData, post, put, processing, errors } = useForm({
        site_id: network?.site_id ?? selectedSiteId ?? sites[0]?.id ?? '',
        name: network?.name ?? '',
        ssid: network?.ssid ?? '',
        security_type: network?.security_type ?? 'wpa2',
        password: '',
        is_active: network?.is_active ?? true,
    });

    const handleSecurityChange = (event) => {
        const security = event.target.value;

        setData((previous) => ({
            ...previous,
            security_type: security,
            password: security === 'open' ? '' : previous.password,
        }));
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (editing) {
            put(`/wifi-networks/${network.id}`);
        } else {
            post('/wifi-networks');
        }
    };

    return (
        <form className="wifi-form" onSubmit={handleSubmit}>
            <div className="wifi-form-section">
                <div className="wifi-form-section-heading">
                    <span>📡</span>
                    <div>
                        <h3>Network Information</h3>
                        <p>Taarifa za msingi za WiFi Network.</p>
                    </div>
                </div>

                <div className="wifi-form-grid">
                    <div className="wifi-form-group">
                        <label htmlFor="site_id">Hotspot Site *</label>
                        <select
                            id="site_id"
                            value={data.site_id}
                            onChange={(event) => setData('site_id', event.target.value)}
                            disabled={editing}
                            required
                        >
                            {sites.map((site) => (
                                <option key={site.id} value={site.id}>
                                    {site.name} {site.code ? `(${site.code})` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.site_id && <small className="wifi-field-error">{errors.site_id}</small>}
                    </div>

                    <div className="wifi-form-group">
                        <label htmlFor="name">Network Name *</label>
                        <input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            placeholder="Mfano: NACPAS Main WiFi"
                            maxLength="255"
                            required
                        />
                        {errors.name && <small className="wifi-field-error">{errors.name}</small>}
                    </div>

                    <div className="wifi-form-group full">
                        <label htmlFor="ssid">WiFi SSID *</label>
                        <input
                            id="ssid"
                            type="text"
                            value={data.ssid}
                            onChange={(event) => setData('ssid', event.target.value)}
                            placeholder="Mfano: NACPAS_WIFI"
                            maxLength="32"
                            required
                        />
                        <small>Jina hili ndilo litaonekana kwa wateja wanapotafuta WiFi.</small>
                        {errors.ssid && <small className="wifi-field-error">{errors.ssid}</small>}
                    </div>
                </div>
            </div>

            <div className="wifi-form-section">
                <div className="wifi-form-section-heading">
                    <span>🔐</span>
                    <div>
                        <h3>Security Configuration</h3>
                        <p>Chagua aina ya usalama wa WiFi.</p>
                    </div>
                </div>

                <div className="wifi-form-grid">
                    <div className="wifi-form-group full">
                        <label htmlFor="security_type">Security Type *</label>
                        <select
                            id="security_type"
                            value={data.security_type}
                            onChange={handleSecurityChange}
                            required
                        >
                            <option value="wpa2">WPA2 Personal (Password Protected)</option>
                            <option value="open">Open Network (No Password)</option>
                        </select>
                        {errors.security_type && <small className="wifi-field-error">{errors.security_type}</small>}
                    </div>

                    {data.security_type === 'wpa2' && (
                        <div className="wifi-form-group full">
                            <label htmlFor="password">
                                {editing ? 'Password Mpya (Hiari)' : 'WiFi Password *'}
                            </label>

                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                placeholder={
                                    editing && network?.password_configured
                                        ? 'Acha wazi ili kubakiza password ya sasa'
                                        : 'Weka password ya WiFi'
                                }
                                minLength="8"
                                maxLength="63"
                                autoComplete="new-password"
                                required={!editing || !network?.password_configured}
                            />

                            <small>
                                Password itahifadhiwa ikiwa encrypted. Inaruhusiwa herufi 8 hadi 63.
                            </small>

                            {errors.password && <small className="wifi-field-error">{errors.password}</small>}
                        </div>
                    )}
                </div>
            </div>

            <div className="wifi-form-section">
                <div className="wifi-form-section-heading">
                    <span>⚙️</span>
                    <div>
                        <h3>Network Status</h3>
                        <p>Chagua kama network itakuwa active.</p>
                    </div>
                </div>

                <label className="wifi-toggle-row">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(event) => setData('is_active', event.target.checked)}
                    />
                    <span>
                        <strong>Active Network</strong>
                        <small>Network hii inaweza kutumika kwenye mipangilio ya NACPAS.</small>
                    </span>
                </label>

                {errors.is_active && <small className="wifi-field-error">{errors.is_active}</small>}
            </div>

            <div className="wifi-form-actions">
                <Link href="/wifi-networks" className="wifi-secondary-button">
                    Ghairi
                </Link>

                <button
                    type="submit"
                    className="wifi-primary-button"
                    disabled={processing}
                >
                    {processing
                        ? 'Inahifadhi...'
                        : editing
                            ? 'Hifadhi Mabadiliko'
                            : 'Sajili WiFi Network'}
                </button>
            </div>
        </form>
    );
}
