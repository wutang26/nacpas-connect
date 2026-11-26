
import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import './Index.css';

const formatMoney = (amount, locale = 'en-TZ') =>
    new Intl.NumberFormat(locale, {
        maximumFractionDigits: 0,
    }).format(Number(amount || 0));

const getNumberLocale = (country) => {
    const locales = {
        Tanzania: 'en-TZ',
        Kenya: 'en-KE',
        Uganda: 'en-UG',
        Rwanda: 'en-RW',
        Burundi: 'fr-BI',
        'United States': 'en-US',
    };

    return locales[country] || 'en-TZ';
};

const formatDuration = (minutes) => {
    const duration = Number(minutes || 0);

    if (duration < 60) {
        return `${duration} dakika`;
    }

    if (duration % 1440 === 0) {
        return `${duration / 1440} siku`;
    }

    if (duration % 60 === 0) {
        return `${duration / 60} saa`;
    }

    return `${Math.floor(duration / 60)} saa ${duration % 60} dakika`;
};

const formatSpeed = (speed) => {
    if (!speed) return 'Haijawekwa';

    return Number(speed) >= 1024
        ? `${(Number(speed) / 1024).toFixed(1)} Mbps`
        : `${speed} Kbps`;
};

// Normalize Tanzanian mobile numbers for validation.
const normalizePhone = (value) => {
    return value.replace(/[\s\-()]/g, '');
};

const isValidTanzanianPhone = (value) => {
    const phone = normalizePhone(value);

    return /^(0[67]\d{8}|\+255[67]\d{8}|255[67]\d{8})$/.test(phone);
};

export default function Index({ site, packages = [],  contextToken = null, }) {
    const { platformSettings = {} } = usePage().props;

    const platformName = platformSettings.platform_name || 'NACPASI';
    const platformTagline =
        platformSettings.platform_tagline || 'SMART WIFI ACCESS';

    const platformCurrency = String(
        platformSettings.currency || 'TZS'
    ).toUpperCase();

    const numberLocale = getNumberLocale(platformSettings.country);

    const [selectedPackage, setSelectedPackage] = useState(null);

    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        package_id: '',
        phone: '',
        context_token: contextToken || '',
    });

    const phone = data.phone;

    const selected = packages.find(
        (item) => Number(item.id) === Number(selectedPackage)
    );

    const validPhone = isValidTanzanianPhone(phone);

    const handlePackageSelection = (packageId) => {
        setSelectedPackage(packageId);
        setData('package_id', packageId);
    };

    const handlePhoneChange = (event) => {
        setData('phone', event.target.value);
    };

    const handleCheckout = (event) => {
        event.preventDefault();

        if (!selected) {
            return;
        }

        if (!validPhone) {
            return;
        }

        post(`/portal/${site.slug}/checkout`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`${platformName} - WiFi Access - ${site.name}`} />

            <div className="customer-portal">

                {/* HEADER */}
                <header className="portal-header">
                    <a href="/" className="portal-logo">
                        <div className="portal-logo-icon">N</div>

                        <div className="portal-logo-text">
                            <strong>{platformName}</strong>
                            <span>{platformTagline}</span>
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

                        <div
                            className="wifi-icon"
                            aria-label="WiFi inapatikana"
                        >
                            <svg viewBox="0 0 64 64" aria-hidden="true">
                                <path
                                    className="wifi-wave wifi-wave-one"
                                    d="M8 24C21 12 43 12 56 24"
                                />

                                <path
                                    className="wifi-wave wifi-wave-two"
                                    d="M16 33C25 25 39 25 48 33"
                                />

                                <path
                                    className="wifi-wave wifi-wave-three"
                                    d="M24 42C29 38 35 38 40 42"
                                />

                                <circle
                                    className="wifi-dot"
                                    cx="32"
                                    cy="51"
                                    r="3"
                                />
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

                            {site.region && (
                                <span>· {site.region}</span>
                            )}
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
                                        Number(selectedPackage) === Number(item.id);

                                    return (
                                        <button
                                            type="button"
                                            key={item.id}
                                            onClick={() =>
                                                handlePackageSelection(item.id)
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
                                                    {formatMoney(item.price, numberLocale)}
                                                </strong>

                                                <span>
                                                    {item.currency || platformCurrency}
                                                </span>
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

                        {errors.package_id && (
                            <p className="phone-error">
                                {errors.package_id}
                            </p>
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

                        <form onSubmit={handleCheckout}>

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
                                    onChange={handlePhoneChange}
                                    disabled={processing}
                                    aria-invalid={
                                        Boolean(errors.phone) ||
                                        (phone.length > 0 && !validPhone)
                                    }
                                />
                            </div>

                            {phone.length > 0 && !validPhone && (
                                <p className="phone-error">
                                    Tafadhali ingiza namba sahihi ya Tanzania.
                                    Mfano: 0712345678.
                                </p>
                            )}

                            {errors.phone && (
                                <p className="phone-error">
                                    {errors.phone}
                                </p>
                            )}

                            <div className="order-summary">

                                <div>
                                    <span>Kifurushi</span>

                                    <strong>
                                        {selected?.name ||
                                            'Hakuna kilichochaguliwa'}
                                    </strong>
                                </div>

                                <div>
                                    <span>Kiasi cha kulipa</span>

                                    <strong>
                                        {selected
                                            ? `${selected.currency || platformCurrency} ${formatMoney(selected.price, numberLocale)}`
                                            : `${platformCurrency} 0`}
                                    </strong>
                                </div>

                            </div>

                            <button
                                type="submit"
                                className="continue-button"
                                disabled={
                                    !selected ||
                                    !validPhone ||
                                    processing
                                }
                            >
                                {processing
                                    ? 'Inatengeneza ombi...'
                                    : 'Endelea'}

                                {!processing && <span>→</span>}
                            </button>

                        </form>

                        <p className="demo-notice">
                            <strong>Angalizo:</strong> Kwa sasa mfumo
                            unatumia checkout ya majaribio. Hakuna fedha
                            zitakazokatwa na internet haitawashwa mpaka
                            malipo halisi na Omada viunganishwe.
                        </p>

                    </section>

                </main>

                {/* FOOTER */}
                <footer className="portal-footer">
                    <span>
                        Powered by <strong>NAC Technologies</strong>
                    </span>

                    <span>
                        © {new Date().getFullYear()} {platformName}
                    </span>
                </footer>

            </div>
        </>
    );
}
