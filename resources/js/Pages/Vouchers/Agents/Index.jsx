import { Head, router, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Breadcrumb, Heading, Alert, Pager, Empty, Status } from '../VoucherUI';
import '../Vouchers.css';

export default function Index({ sites = [], selectedSiteId, agents }) {
    const { flash = {} } = usePage().props;
    const form = useForm({ site_id: selectedSiteId || '', name: '', email: '', password: '', notes: '' });

    const changeSite = e => router.get('/vouchers/agents', { site_id: e.target.value }, { preserveState: true });
    const submit = e => { e.preventDefault(); form.post('/vouchers/agents', { onSuccess: () => form.reset('name', 'email', 'password', 'notes') }); };

    return <AuthenticatedLayout>
        <Head title="Voucher Agents" />
        <main className="v-page">
            <Breadcrumb items={[{ label: 'Voucher Management' }, { label: 'Agents' }]} />
            <Heading title="Voucher Agents" description="Sajili mawakala na simamia akaunti zao za voucher." />
            <Alert flash={flash} errors={form.errors} />

            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Register New Agent</h2><p>Agent atapokea login credentials za kuingia kwenye portal yake.</p></div></div>
                <form className="v-panel-body" onSubmit={submit}>
                    <div className="v-form-grid">
                        <div className="v-field"><label>Site *</label><select required value={form.data.site_id} onChange={e => form.setData('site_id', e.target.value)}>{sites.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}</select></div>
                        <div className="v-field"><label>Agent Full Name *</label><input required value={form.data.name} onChange={e => form.setData('name', e.target.value)} /></div>
                        <div className="v-field"><label>Email Address *</label><input type="email" required value={form.data.email} onChange={e => form.setData('email', e.target.value)} /></div>
                        <div className="v-field"><label>Temporary Password *</label><input type="password" minLength="12" required value={form.data.password} onChange={e => form.setData('password', e.target.value)} placeholder="At least 12 characters" /></div>
                        <div className="v-field full"><label>Notes</label><textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></div>
                    </div>
                    <button disabled={form.processing || !sites.length} className="v-button v-button-primary">+ Register Agent</button>
                </form>
            </section>

            <div className="v-toolbar"><div className="v-field"><label>Filter by Site</label><select value={selectedSiteId || ''} onChange={changeSite}>{sites.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}</select></div></div>
            <section className="v-panel">
                <div className="v-panel-head"><div><h2>Registered Agents</h2><p>Agent accounts and activity</p></div><span className="v-muted">{agents?.total || 0} agents</span></div>
                <div className="v-table-wrap"><table className="v-table">
                    <thead><tr><th>Agent</th><th>Email</th><th>Site</th><th>Agent Code</th><th>Purchases</th><th>Vouchers</th><th>Redemptions</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>{agents?.data?.length ? agents.data.map(a => <AgentRow key={a.id} agent={a} />) : <tr><td colSpan="9"><Empty>No agents registered.</Empty></td></tr>}</tbody>
                </table></div>
                <Pager links={agents?.links || []} />
            </section>
        </main>
    </AuthenticatedLayout>;
}

function AgentRow({ agent }) {
    const form = useForm({ status: agent.status, notes: agent.notes || '' });
    const save = () => form.patch(`/vouchers/agents/${agent.id}`);
    return <tr>
        <td><strong>{agent.user?.name}</strong></td><td>{agent.user?.email}</td><td>{agent.site?.name}</td>
        <td>{agent.agent_code}</td><td>{agent.purchases_count ?? 0}</td><td>{agent.vouchers_count ?? 0}</td><td>{agent.redemptions_count ?? 0}</td>
        <td><Status value={agent.status} /></td>
        <td><div className="v-inline-form"><select value={form.data.status} onChange={e => form.setData('status', e.target.value)}><option value="active">Active</option><option value="suspended">Suspended</option></select><button disabled={form.processing} onClick={save} className="v-button v-button-light">Save</button></div></td>
    </tr>;
}
