import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Alert } from '../VoucherUI';
import '../Vouchers.css';

export default function Create({ sites = [], selectedSiteId, packages = [] }) {
    const { flash = {} } = usePage().props;
    const form = useForm({
        site_id: String(selectedSiteId || ''),
        package_id: '',
        quantity: 10,
        wholesale_unit_price: '',
        retail_unit_price: '',
        expires_at: '',
        notes: '',
    });

    const changeSite = (e) => router.get('/vouchers/batches/create',
        { site_id: e.target.value }, { preserveState: false });

    const submit = (e) => {
        e.preventDefault();
        form.post('/vouchers/batches');
    };

    return <AuthenticatedLayout>
        <Head title="Create Voucher Batch" />
        <main className="v-page">
            <Breadcrumb items={[{ label: 'Voucher Batches', href: '/vouchers/batches' }, { label: 'Create Batch' }]} />
            <Heading title="Create Voucher Batch" description="Generate a new batch of vouchers for a selected WiFi package." />
            <Alert flash={flash} errors={form.errors} />
            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Batch Configuration</h2><p>Set stock quantity and agent/customer pricing.</p></div></div>
                <form className="v-panel-body" onSubmit={submit}>
                    <div className="v-form-grid">
                        <div className="v-field">
                            <label>Site *</label>
                            <select value={form.data.site_id} onChange={changeSite} required>
                                {sites.map(s => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
                            </select>
                        </div>
                        <div className="v-field">
                            <label>WiFi Package *</label>
                            <select required value={form.data.package_id} onChange={e => form.setData('package_id', e.target.value)}>
                                <option value="">Select package</option>
                                {packages.map(p => <option key={p.id} value={p.id}>{p.name} — {p.currency} {p.price} / {p.duration_minutes} minutes</option>)}
                            </select>
                        </div>
                        <div className="v-field">
                            <label>Voucher Quantity *</label>
                            <input type="number" min="1" max="5000" required value={form.data.quantity} onChange={e => form.setData('quantity', e.target.value)} />
                        </div>
                        <div className="v-field">
                            <label>Wholesale Price per Voucher *</label>
                            <input type="number" min="0.01" step="0.01" required value={form.data.wholesale_unit_price} onChange={e => form.setData('wholesale_unit_price', e.target.value)} placeholder="Agent purchase price" />
                        </div>
                        <div className="v-field">
                            <label>Retail Price per Voucher *</label>
                            <input type="number" min="0.01" step="0.01" required value={form.data.retail_unit_price} onChange={e => form.setData('retail_unit_price', e.target.value)} placeholder="Customer selling price" />
                        </div>
                        <div className="v-field">
                            <label>Batch Expiry (Optional)</label>
                            <input type="datetime-local" value={form.data.expires_at} onChange={e => form.setData('expires_at', e.target.value)} />
                        </div>
                        <div className="v-field full">
                            <label>Internal Notes</label>
                            <textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} placeholder="Optional notes about this batch..." />
                        </div>
                    </div>
                    <div className="v-note">Wholesale price ndiyo bei ya agent. Retail price ndiyo bei ya mteja. Tofauti ni margin inayoweza kupatikana agent akiuza voucher.</div>
                    <div className="v-actions">
                        <button disabled={form.processing} className="v-button v-button-primary">{form.processing ? 'Generating...' : 'Generate Voucher Batch'}</button>
                        <Link href="/vouchers/batches" className="v-button v-button-light">Cancel</Link>
                    </div>
                </form>
            </section>
        </main>
    </AuthenticatedLayout>;
}
