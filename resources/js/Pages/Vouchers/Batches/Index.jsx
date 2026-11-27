import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Stats, Alert, Pager, Empty, Status, money, date } from '../VoucherUI';
import '../Vouchers.css';

export default function Index({ sites = [], selectedSiteId, batches, statistics = {} }) {
    const { flash = {} } = usePage().props;

    const changeSite = (e) => router.get('/vouchers/batches',
        { site_id: e.target.value }, { preserveState: true, replace: true });

    return <AuthenticatedLayout>
        <Head title="Voucher Batches" />
        <main className="v-page">
            <Breadcrumb items={[{ label: 'Voucher Management' }, { label: 'Batches' }]} />
            <Heading title="Voucher Batches"
                description="Tengeneza na simamia voucher stock kwa kila site."
                action={<Link className="v-button v-button-primary" href={`/vouchers/batches/create${selectedSiteId ? `?site_id=${selectedSiteId}` : ''}`}>+ Create Batch</Link>} />
            <Alert flash={flash} />
            <Stats items={[
                { label: 'Total Batches', value: statistics.batches },
                { label: 'Available Vouchers', value: statistics.available },
                { label: 'Assigned to Agents', value: statistics.assigned },
                { label: 'Redeemed Vouchers', value: statistics.redeemed },
            ]} />
            <div className="v-toolbar">
                <div className="v-field">
                    <label>Filter by Site</label>
                    <select value={selectedSiteId || ''} onChange={changeSite}>
                        {sites.map(s => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
                    </select>
                </div>
            </div>
            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Generated Batches</h2><p>Voucher stock and distribution status</p></div><span className="v-muted">{batches?.total || 0} batches</span></div>
                <div className="v-table-wrap">
                    <table className="v-table">
                        <thead><tr><th>Batch Number</th><th>Site</th><th>Package</th><th>Quantity</th><th>Wholesale</th><th>Retail</th><th>Voucher Status</th><th>Created</th><th>Status</th><th /></tr></thead>
                        <tbody>
                            {batches?.data?.length ? batches.data.map(b => <tr key={b.id}>
                                <td><strong>{b.batch_number}</strong></td>
                                <td>{b.site?.name || '—'}</td><td>{b.package?.name || '—'}</td>
                                <td>{b.generated_count ?? b.quantity}</td>
                                <td>{money(b.wholesale_unit_price, b.currency)}</td>
                                <td>{money(b.retail_unit_price, b.currency)}</td>
                                <td>{b.vouchers_count ?? 0}</td>
                                <td>{date(b.created_at)}</td>
                                <td><Status value={b.status} /></td>
                                <td><Link className="v-button v-button-light" href={`/vouchers/batches/${b.id}`}>View</Link></td>
                            </tr>) : <tr><td colSpan="10"><Empty>Hakuna voucher batches kwa site hii.</Empty></td></tr>}
                        </tbody>
                    </table>
                </div>
                <Pager links={batches?.links || []} />
            </section>
        </main>
    </AuthenticatedLayout>;
}
