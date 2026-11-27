import { Head, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Alert, Pager, Empty, Status, money, date } from '../VoucherUI';
import '../Vouchers.css';

export default function Index({ purchases, filters = {} }) {
    const { flash = {} } = usePage().props;
    const search = useForm({ q: filters.q || '', status: filters.status || '' });

    const submit = e => { e.preventDefault(); router.get('/vouchers/purchases', search.data, { preserveState: true, replace: true }); };
    const clear = () => { search.reset(); router.get('/vouchers/purchases'); };

    return <AuthenticatedLayout>
        <Head title="Agent Purchases" />
        <main className="v-page">
            <Breadcrumb items={[{ label: 'Voucher Management' }, { label: 'Agent Purchases' }]} />
            <Heading title="Agent Purchases" description="Kagua maombi ya stock, thibitisha malipo na gawa voucher kwa mawakala." />
            <Alert flash={flash} />
            <form className="v-toolbar" onSubmit={submit}>
                <div className="v-field"><label>Search Reference</label><input value={search.data.q} onChange={e => search.setData('q', e.target.value)} placeholder="Purchase or payment reference" /></div>
                <div className="v-field"><label>Payment Status</label><select value={search.data.status} onChange={e => search.setData('status', e.target.value)}><option value="">All statuses</option><option value="pending">Pending</option><option value="paid">Paid</option><option value="cancelled">Cancelled</option></select></div>
                <button className="v-button v-button-primary">Search</button><button type="button" onClick={clear} className="v-button v-button-light">Clear</button>
            </form>
            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Purchase Requests</h2><p>Manual payment verification and voucher allocation</p></div><span className="v-muted">{purchases?.total || 0} records</span></div>
                <div className="v-table-wrap"><table className="v-table">
                    <thead><tr><th>Reference</th><th>Agent</th><th>Batch</th><th>Site</th><th>Quantity</th><th>Wholesale Total</th><th>Status</th><th>Requested</th><th>Payment Action</th></tr></thead>
                    <tbody>{purchases?.data?.length ? purchases.data.map(p => <PurchaseRow key={p.id} purchase={p} />) : <tr><td colSpan="9"><Empty>No purchase requests found.</Empty></td></tr>}</tbody>
                </table></div>
                <Pager links={purchases?.links || []} />
            </section>
        </main>
    </AuthenticatedLayout>;
}

function PurchaseRow({ purchase }) {
    const confirmForm = useForm({ payment_method: 'cash', payment_reference: '' });

    const confirm = e => {
        e.preventDefault();
        if (window.confirm('Thibitisha kuwa malipo haya yamepokelewa?')) {
            confirmForm.post(`/vouchers/purchases/${purchase.id}/confirm`);
        }
    };
    const cancel = () => {
        const reason = window.prompt('Sababu ya kufuta ombi (optional):') || '';
        router.post(`/vouchers/purchases/${purchase.id}/cancel`, { reason });
    };

    return <tr>
        <td><strong>{purchase.reference}</strong><div className="v-muted">{purchase.payment_reference || ''}</div></td>
        <td>{purchase.agent?.user?.name || '—'}</td><td>{purchase.batch?.batch_number || '—'}</td><td>{purchase.site?.name || '—'}</td>
        <td>{purchase.quantity}</td><td>{money(purchase.total_amount, purchase.currency)}</td>
        <td><Status value={purchase.status} /></td><td>{date(purchase.created_at)}</td>
        <td>{purchase.status === 'pending' ? <div>
            <form className="v-inline-form" onSubmit={confirm}>
                <select value={confirmForm.data.payment_method} onChange={e => confirmForm.setData('payment_method', e.target.value)}>
                    <option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile_money">Mobile Money</option><option value="other">Other</option>
                </select>
                <input placeholder="Payment ref (optional)" value={confirmForm.data.payment_reference} onChange={e => confirmForm.setData('payment_reference', e.target.value)} />
                <button disabled={confirmForm.processing} className="v-button v-button-primary">Confirm</button>
                <button type="button" onClick={cancel} className="v-button v-button-danger">Cancel</button>
            </form>
        </div> : <span className="v-muted">{purchase.confirmer?.name || purchase.payment_method || '—'}</span>}</td>
    </tr>;
}
