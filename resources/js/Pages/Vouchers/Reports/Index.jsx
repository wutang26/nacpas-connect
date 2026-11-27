import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Stats, Pager, Empty, money, date } from '../VoucherUI';
import '../Vouchers.css';

export default function Index({ redemptions, statistics = {} }) {
    return <AuthenticatedLayout>
        <Head title="Voucher Reports" />
        <main className="v-page">
            <Breadcrumb items={[{ label: 'Voucher Management' }, { label: 'Reports' }]} />
            <Heading title="Voucher Reports" description="Voucher inventory, agent sales and customer redemption overview." />
            <Stats items={[
                { label: 'Total Vouchers', value: statistics.total_vouchers },
                { label: 'Available', value: statistics.available },
                { label: 'Assigned', value: statistics.assigned },
                { label: 'Redeemed', value: statistics.redeemed },
                { label: 'Paid Agent Purchases', value: statistics.paid_purchases },
                { label: 'Active Agents', value: statistics.agents },
            ]} />
            <div className="v-card-grid">
                <div className="v-card"><p>Wholesale Revenue (Paid Purchases)</p><h3>{money(statistics.wholesale_revenue)}</h3><p>Thamani ya manunuzi ya agents yaliyothibitishwa.</p></div>
                <div className="v-card"><p>Retail Value of Redeemed Vouchers</p><h3>{money(statistics.redemption_retail_value)}</h3><p>Thamani ya retail ya vouchers zilizotumika; si faida halisi.</p></div>
            </div>
            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Voucher Redemptions</h2><p>Customer usage and agent attribution</p></div><span className="v-muted">{redemptions?.total || 0} redemptions</span></div>
                <div className="v-table-wrap"><table className="v-table">
                    <thead><tr><th>Redeemed At</th><th>Site</th><th>Package</th><th>Agent</th><th>Customer</th><th>Phone</th><th>Device MAC</th></tr></thead>
                    <tbody>{redemptions?.data?.length ? redemptions.data.map(r => <tr key={r.id}>
                        <td>{date(r.redeemed_at)}</td><td>{r.site?.name || '—'}</td><td>{r.package?.name || '—'}</td><td>{r.agent?.user?.name || 'Direct Sale'}</td><td>{r.customer?.name || '—'}</td><td>{r.customer?.phone || '—'}</td><td>{r.client_mac || '—'}</td>
                    </tr>) : <tr><td colSpan="7"><Empty>No redemptions recorded.</Empty></td></tr>}</tbody>
                </table></div>
                <Pager links={redemptions?.links || []} />
            </section>
        </main>
    </AuthenticatedLayout>;
}
