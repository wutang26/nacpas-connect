import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Stats, Alert, Pager, Empty, Status, CopyButton, money, date } from '../VoucherUI';
import '../Vouchers.css';

export default function Show({ batch, vouchers, generatedCodes = [], statistics = {} }) {
    const { flash = {} } = usePage().props;

    const closeBatch = () => {
        if (window.confirm('Una uhakika unataka kufunga batch hii?')) {
            router.patch(`/vouchers/batches/${batch.id}/close`);
        }
    };

    return <AuthenticatedLayout>
        <Head title={`Batch ${batch.batch_number}`} />
        <main className="v-page">
            <Breadcrumb items={[{ label: 'Voucher Batches', href: '/vouchers/batches' }, { label: batch.batch_number }]} />
            <Heading title={batch.batch_number} description={`${batch.site?.name || ''} • ${batch.package?.name || ''}`}
                action={<div className="v-actions">
                    <Link href="/vouchers/batches" className="v-button v-button-light">← Back</Link>
                    {batch.status === 'active' && <button onClick={closeBatch} className="v-button v-button-danger">Close Batch</button>}
                </div>} />
            <Alert flash={flash} />
            <Stats items={[
                { label: 'Available', value: statistics.available },
                { label: 'Assigned', value: statistics.assigned },
                { label: 'Redeemed', value: statistics.redeemed },
                { label: 'Voided', value: statistics.void },
            ]} />
            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Batch Information</h2><p>Pricing and batch configuration</p></div><Status value={batch.status} /></div>
                <div className="v-panel-body v-card-grid">
                    <div><span className="v-muted">Quantity generated</span><h3>{batch.generated_count ?? batch.quantity}</h3></div>
                    <div><span className="v-muted">Wholesale price</span><h3>{money(batch.wholesale_unit_price, batch.currency)}</h3></div>
                    <div><span className="v-muted">Retail price</span><h3>{money(batch.retail_unit_price, batch.currency)}</h3></div>
                    <div><span className="v-muted">Created</span><h3>{date(batch.created_at)}</h3></div>
                    <div><span className="v-muted">Expires</span><h3>{date(batch.expires_at)}</h3></div>
                    <div><span className="v-muted">Created by</span><h3>{batch.creator?.name || '—'}</h3></div>
                </div>
            </section>

            {generatedCodes.length > 0 && <section className="v-panel">
                <div className="v-panel-head">
                    <div><h2>Newly Generated Voucher Codes</h2><p>These codes are displayed once. Copy or save them securely now.</p></div>
                    <CopyButton value={generatedCodes.join('\n')} label="Copy All Codes" />
                </div>
                <div className="v-panel-body">
                    <div className="v-code-list">
                        {generatedCodes.map((code, i) => <div className="v-code" key={`${code}-${i}`}><span>{code}</span><CopyButton value={code} /></div>)}
                    </div>
                    <div className="v-note">Hifadhi codes hizi kwa usalama. Baada ya kuondoka kwenye ukurasa huu, hazitaonyeshwa tena na mfumo hautaweza kuzitoa kwa admin kutoka hash pekee.</div>
                </div>
            </section>}

            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Voucher Inventory</h2><p>Codes zinaonyeshwa kwa suffix tu kwa usalama.</p></div><span className="v-muted">{vouchers?.total || 0} vouchers</span></div>
                <div className="v-table-wrap">
                    <table className="v-table">
                        <thead><tr><th>Code Suffix</th><th>Retail Price</th><th>Agent</th><th>Status</th><th>Assigned</th><th>Redeemed</th><th>Expires</th></tr></thead>
                        <tbody>
                            {vouchers?.data?.length ? vouchers.data.map(v => <tr key={v.id}>
                                <td><strong>•••• {v.code_suffix}</strong></td>
                                <td>{money(v.retail_price, v.currency)}</td>
                                <td>{v.agent?.user?.name || '—'}</td>
                                <td><Status value={v.status} /></td>
                                <td>{date(v.assigned_at)}</td><td>{date(v.redeemed_at)}</td><td>{date(v.expires_at)}</td>
                            </tr>) : <tr><td colSpan="7"><Empty>No vouchers found.</Empty></td></tr>}
                        </tbody>
                    </table>
                </div>
                <Pager links={vouchers?.links || []} />
            </section>
        </main>
    </AuthenticatedLayout>;
}
