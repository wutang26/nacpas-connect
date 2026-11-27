import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Alert, Empty, money, date } from '../Vouchers/VoucherUI';
import '../Vouchers/Vouchers.css';

export default function Batches({ agent, batches = [] }) {
    const { flash = {} } = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title="Available Voucher Stock" />

            <main className="v-page">
                <Breadcrumb items={[
                    { label: 'Agent Portal', href: '/voucher-agent' },
                    { label: 'Available Stock' },
                ]} />

                <Heading
                    eyebrow="AGENT PORTAL"
                    title="Available Voucher Stock"
                    description={`Available stock for ${agent.site?.name || 'your site'}.`}
                    action={
                        <Link href="/voucher-agent" className="v-button v-button-light">
                            ← Dashboard
                        </Link>
                    }
                />

                <Alert flash={flash} />

                {batches.length ? (
                    <div className="v-card-grid">
                        {batches.map(batch => (
                            <BatchCard key={batch.id} batch={batch} />
                        ))}
                    </div>
                ) : (
                    <section className="v-panel">
                        <Empty>
                            Hakuna voucher stock inayopatikana kwa site yako kwa sasa.
                        </Empty>
                    </section>
                )}
            </main>
        </AuthenticatedLayout>
    );
}

function BatchCard({ batch }) {
    const form = useForm({
        batch_id: batch.id,
        quantity: 1,
    });

    const request = (event) => {
        event.preventDefault();

        if (window.confirm(`Omba voucher kutoka batch ${batch.batch_number}?`)) {
            form.post('/voucher-agent/purchases');
        }
    };

    return (
        <div className="v-card">
            <span className="v-status v-status-active">Available</span>

            <h3 style={{ marginTop: 12 }}>{batch.batch_number}</h3>

            <p>Package: <strong>{batch.package?.name || '—'}</strong></p>
            <p>Available stock: <strong>{batch.available_stock ?? 0}</strong></p>

            <p>
                Wholesale per voucher:{' '}
                <strong>{money(batch.wholesale_unit_price, batch.currency)}</strong>
            </p>

            <p>
                Retail per voucher:{' '}
                <strong>{money(batch.retail_unit_price, batch.currency)}</strong>
            </p>

            <p>
                Potential margin per voucher:{' '}
                <strong>
                    {money(
                        Number(batch.retail_unit_price) -
                        Number(batch.wholesale_unit_price),
                        batch.currency
                    )}
                </strong>
            </p>

            <p>Batch expiry: {date(batch.expires_at)}</p>

            <form onSubmit={request}>
                <div className="v-field" style={{ marginTop: 13 }}>
                    <label>Quantity to request</label>
                    <input
                        type="number"
                        min="1"
                        max={batch.available_stock || 1}
                        required
                        value={form.data.quantity}
                        onChange={e => form.setData('quantity', e.target.value)}
                    />
                </div>

                <button
                    type="submit"
                    disabled={form.processing || !(batch.available_stock > 0)}
                    className="v-button v-button-primary"
                    style={{ width: '100%', marginTop: 12 }}
                >
                    {form.processing ? 'Submitting...' : 'Request Purchase'}
                </button>
            </form>
        </div>
    );
}