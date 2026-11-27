import { Head, Link, useForm } from '@inertiajs/react';
import '../Management/Management.css';

export default function OrganizationForm({ organization = null }) {
    const editing = Boolean(organization);
    const { data, setData, post, put, processing, errors } = useForm({
        name: organization?.name || '',
        business_email: organization?.business_email || '',
        business_phone: organization?.business_phone || '',
        billing_email: organization?.billing_email || '',
        address: organization?.address || '',
        region: organization?.region || '',
        district: organization?.district || '',
        timezone: organization?.timezone || 'Africa/Dar_es_Salaam',
        status: organization?.status || 'active',
    });

    const submit = (event) => {
        event.preventDefault();
        editing ? put(`/organizations/${organization.id}`) : post('/organizations');
    };

    const field = (name, label, type = 'text', full = false) => (
        <div className={`management-field ${full ? 'full' : ''}`} key={name}>
            <label htmlFor={name}>{label}</label>
            <input id={name} type={type} value={data[name]}
                onChange={(e) => setData(name, e.target.value)} />
            {errors[name] && <small>{errors[name]}</small>}
        </div>
    );

    return (
        <>
            <Head title={editing ? 'Edit Organization' : 'Create Organization'} />
            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>{editing ? 'Edit Organization' : 'Create Organization'}</h1>
                        <p>{editing ? 'Update organization profile and status.' : 'Register a new NACPASI customer organization.'}</p>
                    </div>
                    <Link href={editing ? `/organizations/${organization.id}` : '/organizations'} className="management-secondary">← Back</Link>
                </div>

                <div className="management-panel">
                    <form className="management-form" onSubmit={submit}>
                        <div className="management-form-grid">
                            <div className="management-form-section">Business Information</div>
                            {field('name', 'Organization Name')}
                            {field('business_email', 'Business Email', 'email')}
                            {field('business_phone', 'Business Phone')}
                            {field('billing_email', 'Billing Email', 'email')}
                            {field('region', 'Region')}
                            {field('district', 'District')}
                            {field('address', 'Business Address', 'text', true)}
                            {field('timezone', 'Timezone')}
                            {editing && (
                                <div className="management-field">
                                    <label htmlFor="status">Organization Status</label>
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
                            <Link href={editing ? `/organizations/${organization.id}` : '/organizations'} className="management-secondary">Cancel</Link>
                            <button type="submit" className="management-submit" disabled={processing}>
                                {processing ? 'Saving...' : editing ? 'Save Changes' : 'Create Organization'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
