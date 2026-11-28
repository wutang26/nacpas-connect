
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
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

const normalizePhone = (value) => {
    return value.replace(/[\s\-()]/g, '');
};

const isValidTanzanianPhone = (value) => {
    const phone = normalizePhone(value);

    return /^(0[67]\d{8}|\+255[67]\d{8}|255[67]\d{8})$/.test(phone);
};

const isValidVoucherCode = (value) => {
    const raw = value
        .toUpperCase()
        .replace(/[\s-]/g, '')
        .replace(/^NCP/, '');

    return /^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{16}$/.test(raw);
};

export default function Index({
    site,
    packages = [],
    contextToken = null,
}) {
    const {
        platformSettings = {},
        flash = {},
    } = usePage().props;

    const platformName = platformSettings.platform_name || 'NACPASI';
    const platformTagline =
        platformSettings.platform_tagline || 'SMART WIFI ACCESS';

    const platformCurrency = String(
        platformSettings.currency || 'TZS'
    ).toUpperCase();

    const numberLocale = getNumberLocale(platformSettings.country);

    const [selectedPackage, setSelectedPackage] = useState(null);

    // Existing mobile-money checkout form. Keep this separate from vouchers.
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

    // Independent voucher redemption form.
    const {
        data: voucherData,
        setData: setVoucherData,
        post: postVoucher,
        processing: voucherProcessing,
        errors: voucherErrors,
    } = useForm({
        voucher_code: '',
        phone: '',
        context_token: contextToken || '',
    });

    // Read a voucher code embedded in the QR URL fragment.
    // Fragments are not sent to the server.
    useEffect(() => {
        const fragment = window.location.hash;

        if (!fragment) {
            return;
        }

        const params = new URLSearchParams(fragment.slice(1));
        const voucherCode = params.get('voucher');

        if (!voucherCode) {
            return;
        }

        setVoucherData('voucher_code', voucherCode.toUpperCase());

        // Remove the fragment while preserving Omada query parameters.
        window.history.replaceState(
            window.history.state,
            '',
            `${window.location.pathname}${window.location.search}`
        );
    }, [setVoucherData]);

    const phone = data.phone;

    const selected = packages.find(
        (item) => Number(item.id) === Number(selectedPackage)
    );

    const validPhone = isValidTanzanianPhone(phone);

    const voucherPhoneValid = isValidTanzanianPhone(
        voucherData.phone
    );

    const voucherCodeValid = isValidVoucherCode(
        voucherData.voucher_code
    );

    const handlePackageSelection = (packageId) => {
        setSelectedPackage(packageId);
        setData('package_id', packageId);
    };

    const handlePhoneChange = (event) => {
        setData('phone', event.target.value);
    };

    const handleCheckout = (event) => {
        event.preventDefault();

        if (!selected || !validPhone) {
            return;
        }

        post(`/portal/${site.slug}/checkout`, {
            preserveScroll: true,
        });
    };

    const handleVoucherCodeChange = (event) => {
        setVoucherData(
            'voucher_code',
            event.target.value.toUpperCase()
        );
    };

    const handleVoucherPhoneChange = (event) => {
        setVoucherData('phone', event.target.value);
    };

    const handleVoucherRedemption = (event) => {
        event.preventDefault();

        if (
            !contextToken ||
            !voucherCodeValid ||
            !voucherPhoneValid ||
            voucherProcessing
        ) {
            return;
        }

        postVoucher(`/portal/${site.slug}/redeem-voucher`, {
            preserveScroll: true,
            onSuccess: () => {
                setVoucherData('voucher_code', '');
                setVoucherData('phone', '');
            },
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

                    {/* MOBILE MONEY CHECKOUT */}
                    <section className="portal-panel checkout-panel">

                        <div className="panel-heading">
                            <div>
                                <h2>Nunua kifurushi</h2>
                                <p>Lipa kwa simu kupitia mobile money</p>
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

                    {/* VOUCHER REDEMPTION */}
                    <section className="portal-panel voucher-panel">

                        <div className="voucher-heading">
                            <div className="voucher-heading-icon">
                                <span>✓</span>
                            </div>

                            <div>
                                <h2>Una Voucher?</h2>
                                <p>
                                    Ingiza code yako ili kutumia kifurushi
                                    ulicho nacho.
                                </p>
                            </div>
                        </div>

                        {flash?.success && (
                            <div className="voucher-alert voucher-alert-success" role="status">
                                <span className="voucher-alert-icon">✓</span>
                                <p>{flash.success}</p>
                            </div>
                        )}

                        {!contextToken && (
                            <div className="voucher-alert voucher-alert-warning" role="alert">
                                <span className="voucher-alert-icon">!</span>
                                <p>
                                    Taarifa za kifaa hazijapatikana. Tafadhali
                                    fungua upya ukurasa huu kupitia mtandao wa WiFi.
                                </p>
                            </div>
                        )}

                        <form
                            className="voucher-form"
                            onSubmit={handleVoucherRedemption}
                        >

                            <div className="voucher-field-group">
                                <label htmlFor="voucher-code">
                                    Voucher Code
                                </label>

                                <input
                                    id="voucher-code"
                                    type="text"
                                    name="voucher_code"
                                    placeholder="NCP-XXXX-XXXX-XXXX-XXXX"
                                    autoComplete="off"
                                    spellCheck="false"
                                    maxLength={40}
                                    value={voucherData.voucher_code}
                                    onChange={handleVoucherCodeChange}
                                    disabled={voucherProcessing}
                                    aria-invalid={Boolean(voucherErrors.voucher_code)}
                                />

                                {voucherData.voucher_code.length > 0 &&
                                    !voucherCodeValid && (
                                        <p className="voucher-field-error">
                                            Ingiza voucher code yenye herufi na
                                            namba 16 baada ya NCP.
                                        </p>
                                    )}

                                {voucherErrors.voucher_code && (
                                    <p className="voucher-field-error">
                                        {voucherErrors.voucher_code}
                                    </p>
                                )}
                            </div>

                            <div className="voucher-field-group">
                                <label htmlFor="voucher-phone">
                                    Namba ya simu
                                </label>

                                <input
                                    id="voucher-phone"
                                    type="tel"
                                    name="phone"
                                    inputMode="tel"
                                    autoComplete="tel"
                                    placeholder="0712 345 678"
                                    value={voucherData.phone}
                                    onChange={handleVoucherPhoneChange}
                                    disabled={voucherProcessing}
                                    aria-invalid={Boolean(voucherErrors.phone)}
                                />

                                {voucherData.phone.length > 0 &&
                                    !voucherPhoneValid && (
                                        <p className="voucher-field-error">
                                            Ingiza namba sahihi ya Tanzania.
                                            Mfano: 0712345678.
                                        </p>
                                    )}

                                {voucherErrors.phone && (
                                    <p className="voucher-field-error">
                                        {voucherErrors.phone}
                                    </p>
                                )}
                            </div>

                            <input
                                type="hidden"
                                name="context_token"
                                value={voucherData.context_token}
                            />

                            <button
                                type="submit"
                                className="voucher-submit-button"
                                disabled={
                                    !contextToken ||
                                    !voucherCodeValid ||
                                    !voucherPhoneValid ||
                                    voucherProcessing
                                }
                            >
                                {voucherProcessing
                                    ? 'Inathibitisha Voucher...'
                                    : 'Tumia Voucher'}

                                {!voucherProcessing && <span>→</span>}
                            </button>

                        </form>

                        <div className="voucher-security-note">
                            <span>🔒</span>
                            <p>
                                Voucher inathibitishwa na mfumo kabla ya
                                kifurushi kuanzishwa. Usimpe mtu mwingine
                                voucher code yako.
                            </p>
                        </div>

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
