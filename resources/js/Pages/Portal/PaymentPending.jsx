import { useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import './PaymentPending.css';

const formatMoney = (amount, locale = 'en-TZ') =>
    new Intl.NumberFormat(locale, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
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

const getPaymentState = (payment) => {
    if (
        payment.status === 'successful' &&
        payment.subscription_status === 'active'
    ) {
        return {
            type: 'success',
            label: 'MALIPO YAMETHIBITISHWA',
            title: 'Malipo yamefanikiwa!',
            description:
                'FastLipa imethibitisha malipo yako na kifurushi chako kimeanzishwa.',
        };
    }

    if (payment.status === 'failed') {
        return {
            type: 'failed',
            label: 'MALIPO HAYAKUKAMILIKA',
            title: 'Malipo hayakukamilika',
            description:
                'Ombi la malipo halijakamilika. Tafadhali angalia ujumbe wa mtandao wako wa simu kabla ya kujaribu tena.',
        };
    }

    if (payment.initiation_state === 'uncertain') {
        return {
            type: 'uncertain',
            label: 'OMBI LINAHITAJI UHAKIKI',
            title: 'Tunathibitisha ombi lako',
            description:
                'Hatukupata majibu ya mwisho kutoka FastLipa wakati wa kuanzisha malipo. Angalia simu yako kama umepokea ombi la malipo. Usianzishe ombi jingine kwa sasa.',
        };
    }

    if (payment.initiation_state === 'rejected') {
        return {
            type: 'failed',
            label: 'OMBI LIMEKATALIWA',
            title: 'Ombi la malipo limekataliwa',
            description:
                'FastLipa haikukubali ombi la kuanzisha malipo. Hakikisha taarifa za simu ni sahihi kabla ya kujaribu tena.',
        };
    }

    return {
        type: 'pending',
        label: 'INASUBIRI MALIPO',
        title: 'Subiri ombi la malipo kwenye simu yako',
        description:
            'Ombi la malipo limepokelewa na mfumo. Angalia simu yako kwa USSD Push kutoka kwa mtandao wako wa simu.',
    };
};

export default function PaymentPending({ site, payment }) {
    const { platformSettings = {} } = usePage().props;

    const [refreshing, setRefreshing] = useState(false);

    const platformName = platformSettings.platform_name || 'NACPASI';

    const platformCurrency = String(
        platformSettings.currency || 'TZS'
    ).toUpperCase();

    const paymentCurrency = payment.currency || platformCurrency;

    const numberLocale = getNumberLocale(platformSettings.country);

    const state = getPaymentState(payment);

    const isPending = payment.status === 'pending';

    const refreshPayment = () => {
        if (refreshing) {
            return;
        }

        setRefreshing(true);

        router.reload({
            only: ['payment'],
            preserveScroll: true,
            preserveState: true,
            onFinish: () => setRefreshing(false),
        });
    };

    /*
     * Refresh the database-backed payment status every 8 seconds
     * while payment remains pending.
     *
     * This does not initiate another FastLipa transaction.
     */
    useEffect(() => {
        if (!isPending) {
            return undefined;
        }

        const interval = window.setInterval(() => {
            router.reload({
                only: ['payment'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 8000);

        return () => window.clearInterval(interval);
    }, [isPending]);

    return (
        <>
            <Head title={`${platformName} - Malipo - ${site.name}`} />

            <div className="payment-status-page">
                <div className="payment-status-card">

                    <div className={`payment-status-icon ${state.type}`}>
                        {state.type === 'success' ? '✓' :
                            state.type === 'failed' ? '×' :
                                state.type === 'uncertain' ? '?' : '…'}
                    </div>

                    <span className={`payment-status-label ${state.type}`}>
                        {state.label}
                    </span>

                    <h1>{state.title}</h1>

                    <p className="payment-status-description">
                        {state.description}
                    </p>

                    {isPending && payment.initiation_state === 'initiated' && (
                        <div className="payment-instruction">
                            <strong>Hatua za kufanya kwenye simu yako</strong>

                            <ol>
                                <li>
                                    Angalia simu yenye namba {payment.phone}.
                                </li>
                                <li>
                                    Kama umepokea USSD Push, fungua ombi hilo.
                                </li>
                                <li>
                                    Hakiki kiasi cha malipo na jina la huduma.
                                </li>
                                <li>
                                    Ingiza PIN yako ya mobile money kwenye
                                    prompt rasmi ya mtandao wako ili kuthibitisha.
                                </li>
                            </ol>

                            <p>
                                Usiandike au kumtumia mtu PIN yako. Kama
                                hujapokea prompt, subiri kidogo na angalia
                                hali ya malipo hapa chini.
                            </p>
                        </div>
                    )}

                    {isPending && payment.initiation_state === 'uncertain' && (
                        <div className="payment-warning">
                            <strong>Usirudie malipo kwa sasa.</strong>
                            <p>
                                Oda inaweza kuwa imefika FastLipa hata kama
                                mfumo haukupata majibu yake. Kwanza hakiki
                                simu yako na hali ya malipo.
                            </p>
                        </div>
                    )}

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
                                {paymentCurrency}{' '}
                                {formatMoney(payment.amount, numberLocale)}
                            </strong>
                        </div>

                        <div>
                            <span>Payment Reference</span>
                            <strong>{payment.reference}</strong>
                        </div>

                        <div>
                            <span>Hali ya malipo</span>
                            <strong className={`status-value ${state.type}`}>
                                {payment.status.toUpperCase()}
                            </strong>
                        </div>

                        <div>
                            <span>Hali ya kifurushi</span>
                            <strong className={`status-value ${
                                payment.subscription_status === 'active'
                                    ? 'success'
                                    : 'pending'
                            }`}>
                                {payment.subscription_status.toUpperCase()}
                            </strong>
                        </div>

                        {payment.provider_reference && (
                            <div>
                                <span>FastLipa Transaction</span>
                                <strong>{payment.provider_reference}</strong>
                            </div>
                        )}

                        {payment.paid_at && (
                            <div>
                                <span>Muda wa malipo</span>
                                <strong>
                                    {new Date(payment.paid_at).toLocaleString(
                                        'en-GB',
                                        { timeZone: platformSettings.timezone || 'Africa/Dar_es_Salaam' }
                                    )}
                                </strong>
                            </div>
                        )}
                    </div>

                    {payment.status === 'successful' && (
                        <div className="payment-success-note">
                            <strong>Malipo yamethibitishwa.</strong>
                            <p>
                                Kifurushi kimeanzishwa kwenye mfumo wa NACPASI.
                                Uwezeshaji wa internet kupitia kifaa cha Omada
                                bado unasubiri kukamilika kwa integration ya
                                kifaa hicho.
                            </p>
                        </div>
                    )}

                    {payment.status === 'pending' && (
                        <button
                            type="button"
                            className="payment-refresh-button"
                            onClick={refreshPayment}
                            disabled={refreshing}
                        >
                            {refreshing
                                ? 'Inaangalia...'
                                : 'Angalia Hali ya Malipo'}
                        </button>
                    )}

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