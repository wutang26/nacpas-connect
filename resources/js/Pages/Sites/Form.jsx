import { Head, Link, useForm } from '@inertiajs/react';
import '../Management/Management.css';

export default function SiteForm({ site = null, organizations = [] }) {
    const editing = Boolean(site);
    const { data, setData, post, put, processing, errors } = useForm({
        organization_id: site?.organization_id || organizations[0]?.id || '',
        name: site?.name || '',
        description: site?.description || '',
        address: site?.address || '',
        region: site?.region || '',
        district: site?.district || '',
        latitude: site?.latitude || '',
        longitude: site?.longitude || '',
        timezone: site?.timezone || 'Africa/Dar_es_Salaam',
        status: site?.status || 'active',
    });

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/sites/${site.id}`) : post('/sites');
    };

    const field = (name, label, type = 'text', full = false, step = undefined) => (
        <div className={`management-field ${full ? 'full' : ''}`} key={name}>
            <label htmlFor={name}>{label}</label>
            <input id={name} type={type} step={step} value={data[name]}
                onChange={(e) => setData(name, e.target.value)} />
            {errors[name] && <small>{errors[name]}</small>}
        </div>
    );

    return (
        <>
            <Head title={editing ? 'Edit Site' : 'Register Site'} />
            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>{editing ? 'Edit Site' : 'Register New Site'}</h1>
                        <p>{editing ? 'Update site location and configuration.' : 'Register a physical WiFi service location under an organization.'}</p>
                    </div>
                    <Link href={editing ? `/sites/${site.id}` : '/sites'} className="management-secondary">← Back</Link>
                </div>

                <div className="management-panel">
                    <form className="management-form" onSubmit={submit}>
                        <div className="management-form-grid">
                            <div className="management-form-section">Organization & Site</div>

                            <div className="management-field full">
                                <label htmlFor="organization_id">Organization</label>
                                <select id="organization_id" value={data.organization_id}
                                    onChange={(e) => setData('organization_id', e.target.value)}>
                                    <option value="">Select organization</option>
                                    {organizations.map((org) => <option key={org.id} value={org.id}>{org.name}</option>)}
                                </select>
                                {errors.organization_id && <small>{errors.organization_id}</small>}
                            </div>

                            {field('name', 'Site Name')}
                            {field('region', 'Region')}
                            {field('district', 'District')}
                            {field('timezone', 'Timezone')}
                            {field('latitude', 'Latitude', 'number', false, 'any')}
                            {field('longitude', 'Longitude', 'number', false, 'any')}
                            {field('address', 'Physical Address', 'text', true)}

                            <div className="management-field full">
                                <label htmlFor="description">Description</label>
                                <textarea id="description" value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Optional site description..." />
                                {errors.description && <small>{errors.description}</small>}
                            </div>

                            {editing && (
                                <div className="management-field">
                                    <label htmlFor="status">Site Status</label>
                                    <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)}>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                    {errors.status && <small>{errors.status}</small>}
                                </div>
                            )}
                        </div>

                        <div className="management-form-actions">
                            <Link href={editing ? `/sites/${site.id}` : '/sites'} className="management-secondary">Cancel</Link>
                            <button type="submit" className="management-submit" disabled={processing || organizations.length === 0}>
                                {processing ? 'Saving...' : editing ? 'Save Changes' : 'Register Site'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
