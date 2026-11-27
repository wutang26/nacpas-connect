import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Breadcrumb, Heading, Stats, Alert, Pager, Empty, Status,
    CopyButton, money, date
} from '../Vouchers/VoucherUI';
import '../Vouchers/Vouchers.css';

export default function Dashboard({ agent, purchases, vouchers = [], statistics = {} }) {
    const { flash = {} } = usePage().props;
    const [selectedIds, setSelectedIds] = useState([]);
    const [paperWidth, setPaperWidth] = useState('58');

    const printableVouchers = vouchers.filter(v => v.status === 'assigned');

    const toggleVoucher = (id) => {
        setSelectedIds(current =>
            current.includes(id)
                ? current.filter(item => item !== id)
                : [...current, id]
        );
    };

    const printVouchers = (ids = null) => {
        const params = new URLSearchParams();
        params.set('width', paperWidth);

        if (ids) {
            ids.forEach(id => params.append('ids[]', id));
        }

        window.open(
            `/voucher-agent/print?${params.toString()}`,
            '_blank',
            'noopener,noreferrer'
        );
    };

    return <AuthenticatedLayout>
        <Head title="My Voucher Dashboard" />

        <main className="v-page">
            <Breadcrumb items={[
                { label: 'Agent Portal' },
                { label: 'Dashboard' }
            ]} />

            <Heading
                eyebrow="VOUCHER AGENT PORTAL"
                title={`Welcome, ${agent.user?.name || 'Agent'}`}
                description={`${agent.site?.name || 'Your assigned site'} • Agent code: ${agent.agent_code}`}
                action={<div className="v-actions">
                    <Link href="/voucher-agent/batches" className="v-button v-button-primary">
                        Browse Voucher Stock
                    </Link>
                    <button
                        type="button"
                        className="v-button v-button-light"
                        disabled={!printableVouchers.length}
                        onClick={() => printVouchers()}
                    >
                        Print My Available Vouchers
                    </button>
                </div>}
            />

            <Alert flash={flash} />

            <Stats items={[
                { label: 'My Purchases', value: statistics.purchases },
                { label: 'Pending Payments', value: statistics.pending },
                { label: 'Assigned Vouchers', value: statistics.assigned },
                { label: 'Redeemed Vouchers', value: statistics.redeemed },
                { label: 'Potential Retail Value', value: money(statistics.potentialRetailValue) },
            ]} />

            <div className="v-note">
                Potential retail value ni thamani inayoweza kupatikana kwa kuuza inventory
                iliyobaki. Si mapato yaliyopatikana wala faida iliyothibitishwa.
            </div>

            <section className="v-panel">
                <div className="v-panel-head">
                    <div>
                        <h2>My Voucher Inventory</h2>
                        <p>Codes allocated to your paid purchases only.</p>
                    </div>
                    <span className="v-muted">{vouchers.length} vouchers</span>
                </div>

                <div className="v-panel-body">
                    <div className="v-actions" style={{ justifyContent: 'space-between', flexWrap: 'wrap' }}>
                        <div className="v-actions">
                            <label>
                                Paper size{' '}
                                <select
                                    value={paperWidth}
                                    onChange={e => setPaperWidth(e.target.value)}
                                    className="v-input"
                                >
                                    <option value="58">58mm</option>
                                    <option value="80">80mm</option>
                                </select>
                            </label>

                            <button
                                type="button"
                                className="v-button v-button-primary"
                                disabled={!printableVouchers.length}
                                onClick={() => printVouchers()}
                            >
                                Print All Unused ({printableVouchers.length})
                            </button>

                            <button
                                type="button"
                                className="v-button v-button-light"
                                disabled={!selectedIds.length}
                                onClick={() => printVouchers(selectedIds)}
                            >
                                Print Selected ({selectedIds.length})
                            </button>
                        </div>
                        <span className="v-muted">
                            Redeemed vouchers cannot be printed again.
                        </span>
                    </div>

                    {vouchers.length ? (
                        <div className="v-card-grid">
                            {vouchers.map(v => (
                                <div className="v-card" key={v.id}>
                                    <div className="v-actions" style={{ justifyContent: 'space-between' }}>
                                        <Status value={v.status} />
                                        <span className="v-muted">{v.batch_number}</span>
                                    </div>

                                    {v.status === 'assigned' && (
                                        <label style={{ display: 'block', marginTop: 10 }}>
                                            <input
                                                type="checkbox"
                                                checked={selectedIds.includes(v.id)}
                                                onChange={() => toggleVoucher(v.id)}
                                            />{' '}
                                            Select for printing
                                        </label>
                                    )}

                                    <h3 style={{ fontFamily: 'monospace', marginTop: 12 }}>
                                        {v.code}
                                    </h3>

                                    <p>Retail: <strong>{money(v.retail_price, v.currency)}</strong></p>
                                    <p>Expires: {date(v.expires_at)}</p>

                                    <div className="v-actions">
                                        <CopyButton value={v.code} label="Copy Code" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Empty>
                            Huna voucher zilizogawiwa bado. Omba stock kwenye Browse Voucher Stock.
                        </Empty>
                    )}
                </div>
            </section>

            <section className="v-panel">
                <div className="v-panel-head">
                    <div>
                        <h2>My Purchase Requests</h2>
                        <p>Track payment confirmation and voucher allocation.</p>
                    </div>
                </div>

                <div className="v-table-wrap">
                    <table className="v-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Batch</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Paid At</th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchases?.data?.length ? purchases.data.map(p => (
                                <tr key={p.id}>
                                    <td><strong>{p.reference}</strong></td>
                                    <td>{p.batch?.batch_number || '—'}</td>
                                    <td>{p.quantity}</td>
                                    <td>{money(p.total_amount, p.currency)}</td>
                                    <td><Status value={p.status} /></td>
                                    <td>{date(p.created_at)}</td>
                                    <td>{date(p.paid_at)}</td>
                                </tr>
                            )) : (
                                <tr><td colSpan="7"><Empty>No purchase requests yet.</Empty></td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pager links={purchases?.links || []} />
            </section>
        </main>
    </AuthenticatedLayout>;
}
