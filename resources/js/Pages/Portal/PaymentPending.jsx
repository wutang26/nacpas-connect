
import { Head, Link } from '@inertiajs/react';
import './PaymentPending.css';

const formatMoney = (amount) =>
    new Intl.NumberFormat('en-TZ', {
        maximumFractionDigits: 0,
    }).format(Number(amount));

export default function PaymentPending({ site, payment }) {
    return (
        <>
            <Head title={`Checkout - ${site.name}`} />

            <div className="payment-status-page">
                <div className="payment-status-card">

                    <div className="payment-status-icon">
                        <span>!</span>
                    </div>

                    <span className="payment-status-label">
                        CHECKOUT IMETENGENEZWA
                    </span>

                    <h1>
                        Ombi lako limepokelewa
                    </h1>

                    <p className="payment-status-description">
                        Mfumo umehifadhi maelezo ya kifurushi chako.
                        Hata hivyo, malipo halisi bado hayajaanzishwa.
                    </p>

                    <div className="payment-status-details">

                        <div>
                            <span>Kituo</span>
                            <strong>{site.name}</strong>
                        </div>

                        <div>
                            <span>Kifurushi</span>
                            <strong>{payment.package_name}</strong>
                        </div>

                        <div>
                            <span>Namba ya simu</span>
                            <strong>{payment.phone}</strong>
                        </div>

                        <div>
                            <span>Kiasi</span>
                            <strong>
                                {payment.currency} {formatMoney(payment.amount)}
                            </strong>
                        </div>

                        <div>
                            <span>Payment Reference</span>
                            <strong>{payment.reference}</strong>
                        </div>

                        <div>
                            <span>Subscription Status</span>
                            <strong className="status-pending">
                                {payment.subscription_status.toUpperCase()}
                            </strong>
                        </div>

                    </div>

                    <div className="payment-test-warning">
                        <strong>Hii ni checkout ya majaribio.</strong>

                        <p>
                            Hakuna fedha zilizokatwa kwenye simu yako.
                            Usichukulie ukurasa huu kama uthibitisho wa malipo.
                            Internet bado haijawezeshwa.
                        </p>
                    </div>

                    <Link
                        href={`/portal/${site.slug}`}
                        className="payment-back-button"
                    >
                        Rudi kwenye vifurushi
                    </Link>

                    <div className="payment-status-footer">
                        Powered by <strong>NAC Technologies</strong>
                    </div>

                </div>
            </div>
        </>
    );
}
