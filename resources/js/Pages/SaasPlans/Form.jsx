import { Head, Link, useForm } from '@inertiajs/react';
import '../Management/Management.css';

export default function SaasPlanForm({ plan = null }) {
    const editing = Boolean(plan);

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        code: plan?.code || '',
        description: plan?.description || '',
        price_monthly: plan?.price_monthly || '0',
        price_annual: plan?.price_annual || '0',
        currency: plan?.currency || 'TZS',
        max_sites: plan?.max_sites || 1,
        max_users: plan?.max_users || 2,
        max_devices: plan?.max_devices || 2,
        features_text: plan?.features_text || '',
        is_active: plan?.is_active ?? true,
    });

    const submit = (event) => {
        event.preventDefault();

        if (editing) {
            put(`/saas-plans/${plan.id}`);
        } else {
            post('/saas-plans');
        }
    };

    const field = (name, label, type = 'text', full = false, min = undefined) => (
        <div className={`management-field ${full ? 'full' : ''}`} key={name}>
            <label htmlFor={name}>{label}</label>
            <input
                id={name}
                type={type}
                min={min}
                value={data[name]}
                onChange={(event) => setData(name, event.target.value)}
            />
            {errors[name] && <small>{errors[name]}</small>}
        </div>
    );

    return (
        <>
            <Head title={editing ? 'Edit SaaS Plan' : 'Create SaaS Plan'} />

            <div className="management-page">
                <div className="management-heading">
                    <div>
                        <h1>{editing ? 'Edit SaaS Plan' : 'Create SaaS Plan'}</h1>
                        <p>
                            {editing
                                ? 'Update plan pricing, limits and features.'
                                : 'Configure a subscription plan for NACPASI customer organizations.'}
                        </p>
                    </div>

                    <Link href="/saas-plans" className="management-secondary">
                        ← Back to Plans
                    </Link>
                </div>

                <div className="management-panel">
                    <form className="management-form" onSubmit={submit}>
                        <div className="management-form-grid">
                            <div className="management-form-section">Plan Information</div>

                            {field('name', 'Plan Name')}
                            {field('code', 'Plan Code')}

                            <div className="management-field full">
                                <label htmlFor="description">Description</label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    placeholder="Describe what this plan offers..."
                                />
                                {errors.description && <small>{errors.description}</small>}
                            </div>

                            <div className="management-form-section">Pricing</div>

                            {field('price_monthly', 'Monthly Price', 'number', false, '0')}
                            {field('price_annual', 'Annual Price', 'number', false, '0')}

                            <div className="management-field">
                                <label htmlFor="currency">Currency</label>
                                <select
                                    id="currency"
                                    value={data.currency}
                                    onChange={(event) => setData('currency', event.target.value)}
                                >
                                    <option value="TZS">TZS - Tanzanian Shilling</option>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="KES">KES - Kenyan Shilling</option>
                                    <option value="UGX">UGX - Ugandan Shilling</option>
                                </select>
                                {errors.currency && <small>{errors.currency}</small>}
                            </div>

                            <div className="management-form-section">Plan Limits</div>

                            {field('max_sites', 'Maximum Sites', 'number', false, '1')}
                            {field('max_users', 'Maximum Users', 'number', false, '1')}
                            {field('max_devices', 'Maximum Devices', 'number', false, '1')}

                            <div className="management-field full">
                                <label htmlFor="features_text">Plan Features</label>
                                <textarea
                                    id="features_text"
                                    value={data.features_text}
                                    onChange={(event) => setData('features_text', event.target.value)}
                                    placeholder={'Enter one feature per line:\nRemote device management\nUsage reports\nPriority support'}
                                />
                                <small>Enter one feature per line.</small>
                                {errors.features_text && <small>{errors.features_text}</small>}
                            </div>

                            <div className="management-form-section">Availability</div>

                            <div className="management-field">
                                <label htmlFor="is_active">Plan Status</label>
                                <select
                                    id="is_active"
                                    value={data.is_active ? '1' : '0'}
                                    onChange={(event) => setData('is_active', event.target.value === '1')}
                                >
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                {errors.is_active && <small>{errors.is_active}</small>}
                            </div>
                        </div>

                        <div className="management-form-actions">
                            <Link href="/saas-plans" className="management-secondary">
                                Cancel
                            </Link>
                            <button type="submit" className="management-submit" disabled={processing}>
                                {processing ? 'Saving...' : editing ? 'Save Changes' : 'Create Plan'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
