
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import './Index.css';

const formatMoney = (amount) =>
    new Intl.NumberFormat('en-TZ', {
        maximumFractionDigits: 0,
    }).format(Number(amount));

const formatDuration = (minutes) => {
    if (minutes < 60) {
        return `${minutes} dakika`;
    }

    if (minutes % 1440 === 0) {
        return `${minutes / 1440} siku`;
    }

    if (minutes % 60 === 0) {
        return `${minutes / 60} saa`;
    }

    return `${Math.floor(minutes / 60)} saa ${minutes % 60} dakika`;
};

const formatSpeed = (speed) => {
    if (!speed) return 'Haijawekwa';

    return speed >= 1024
        ? `${(speed / 1024).toFixed(1)} Mbps`
        : `${speed} Kbps`;
};

export default function Index({ site, packages = [] }) {
    const [selectedPackage, setSelectedPackage] = useState(null);
    const [phone, setPhone] = useState('');

    const selected = packages.find(
        (item) => item.id === selectedPackage
    );

    const normalizedPhone = phone.replace(/[\s-]/g, '');

    const validPhone =
        /^(0[67]\d{8}|\+255[67]\d{8}|255[67]\d{8})$/.test(
            normalizedPhone
        );

    return (
        <>
            <Head title={`WiFi Access - ${site.name}`} />

            <div className="customer-portal">

                {/* HEADER */}
                <header className="portal-header">
                    <a href="#" className="portal-logo">
                        <div className="portal-logo-icon">N</div>

                        <div className="portal-logo-text">
                            <strong>NACPAS CONNECT</strong>
                            <span>SMART WIFI ACCESS</span>
                        </div>
                    </a>

                    <div className="portal-security">
                        <span className="security-dot"></span>
                        Secure Portal
                    </div>
                </header>

                {/* HERO */}
                <main className="portal-container">

                    <section className="portal-hero">
                        {/* <div className="wifi-icon">
                            <svg viewBox="0 0 64 64" aria-hidden="true">
                                <path d="M8 24C21 12 43 12 56 24" />
                                <path d="M16 33C25 25 39 25 48 33" />
                                <path d="M24 42C29 38 35 38 40 42" />
                                <circle cx="32" cy="51" r="3" />
                            </svg>
                        </div> */}
                        <div className="wifi-icon" aria-label="WiFi inapatikana">
                            <svg viewBox="0 0 64 64" aria-hidden="true">
                                <path className="wifi-wave wifi-wave-one"
                                    d="M8 24C21 12 43 12 56 24" />

                                <path className="wifi-wave wifi-wave-two"
                                    d="M16 33C25 25 39 25 48 33" />

                                <path className="wifi-wave wifi-wave-three"
                                    d="M24 42C29 38 35 38 40 42" />

                                <circle className="wifi-dot" cx="32" cy="51" r="3" />
                            </svg>
                        </div>
                        <span className="portal-eyebrow">
                            KARIBU KWENYE WIFI
                        </span>

                        <h1>
                            Internet yako,
                            <span> kwa urahisi zaidi.</span>
                        </h1>

                        <p>
                            Chagua kifurushi unachokipenda na uunganishe
                            kifaa chako kwenye huduma yetu ya internet.
                        </p>

                        <div className="site-location">
                            <span>⌖</span>
                            <strong>{site.name}</strong>
                            {site.region && <span>· {site.region}</span>}
                        </div>
                    </section>


                    {/* SUPPORTED PAYMENT NETWORKS */}
                    <section className="payment-networks">
                        <p className="payment-networks-title">
                            MALIPO KUPITIA MITANDAO YOTE
                        </p>

                        <div className="payment-logos">
                            <img src="/images/vodacom.png" alt="Vodacom" />
                            <img src="/images/airtel.png" alt="Airtel Money" />
                            <img src="/images/yas.png" alt="Mixx by Yas" />
                            <img src="/images/halotel.png" alt="HaloPesa" />
                            <img src="/images/ttcl.png" alt="TTCL" />
                        </div>
                    </section>


                    {/* PACKAGES */}
                    <section className="portal-panel">
                        <div className="panel-heading">
                            <div>
                                <h2>Vifurushi vya Internet</h2>
                                <p>Chagua kifurushi kinachokufaa</p>
                            </div>

                            <span className="package-count">
                                {packages.length} Vifurushi
                            </span>
                        </div>

                        {packages.length > 0 ? (
                            <div className="package-grid">
                                {packages.map((item) => {
                                    const active =
                                        selectedPackage === item.id;

                                    return (
                                        <button
                                            type="button"
                                            key={item.id}
                                            onClick={() =>
                                                setSelectedPackage(item.id)
                                            }
                                            className={`package-card ${
                                                active ? 'active' : ''
                                            }`}
                                            aria-pressed={active}
                                        >
                                            <div className="package-top">
                                                <span className="package-radio">
                                                    {active && <i />}
                                                </span>

                                                <span className="package-name">
                                                    {item.name}
                                                </span>
                                            </div>

                                            <div className="package-price">
                                                <strong>
                                                    {formatMoney(item.price)}
                                                </strong>
                                                <span>TZS</span>
                                            </div>

                                            <div className="package-duration">
                                                ◷ {formatDuration(
                                                    item.duration_minutes
                                                )}
                                            </div>

                                            <div className="package-divider" />

                                            <div className="package-speeds">
                                                <div>
                                                    <span>Download</span>
                                                    <strong>
                                                        {formatSpeed(
                                                            item.download_speed_kbps
                                                        )}
                                                    </strong>
                                                </div>

                                                <div>
                                                    <span>Upload</span>
                                                    <strong>
                                                        {formatSpeed(
                                                            item.upload_speed_kbps
                                                        )}
                                                    </strong>
                                                </div>
                                            </div>

                                            {item.description && (
                                                <p className="package-description">
                                                    {item.description}
                                                </p>
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="portal-empty">
                                <strong>Hakuna vifurushi kwa sasa</strong>
                                <p>Tafadhali wasiliana na msimamizi.</p>
                            </div>
                        )}
                    </section>

                    {/* CUSTOMER DETAILS */}
                    <section className="portal-panel checkout-panel">
                        <div className="panel-heading">
                            <div>
                                <h2>Maelezo yako</h2>
                                <p>Ingiza namba yako ya simu</p>
                            </div>
                        </div>

                        <label htmlFor="customer-phone">
                            Namba ya simu
                        </label>

                        <div className="phone-field">
                            <span>🇹🇿 +255</span>

                            <input
                                id="customer-phone"
                                type="tel"
                                inputMode="tel"
                                autoComplete="tel"
                                placeholder="0712 345 678"
                                value={phone}
                                onChange={(event) =>
                                    setPhone(event.target.value)
                                }
                            />
                        </div>

                        {phone.length > 0 && !validPhone && (
                            <p className="phone-error">
                                Tafadhali ingiza namba sahihi ya Tanzania.
                            </p>
                        )}

                        <div className="order-summary">
                            <div>
                                <span>Kifurushi</span>
                                <strong>
                                    {selected?.name || 'Hakuna kilichochaguliwa'}
                                </strong>
                            </div>

                            <div>
                                <span>Kiasi cha kulipa</span>
                                <strong>
                                    {selected
                                        ? `TZS ${formatMoney(selected.price)}`
                                        : 'TZS 0'}
                                </strong>
                            </div>
                        </div>

                        <button
                            type="button"
                            className="continue-button"
                            disabled={!selected || !validPhone}
                        >
                            Endelea
                            <span>→</span>
                        </button>

                        <p className="demo-notice">
                            <strong>Angalizo:</strong> Malipo ya simu
                            na kuidhinisha internet bado havijaunganishwa.
                            Hakuna malipo yatakayofanyika kwa sasa.
                        </p>
                    </section>

                </main>

                {/* FOOTER */}
                <footer className="portal-footer">
                    <span>
                        Powered by <strong>NAC Technologies</strong>
                    </span>

                    <span>
                        © {new Date().getFullYear()} NACPAS Connect
                    </span>
                </footer>
            </div>
        </>
    );
}
