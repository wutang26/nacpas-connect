
import { Link, useForm } from '@inertiajs/react';

export default function PackageForm({
    sites = [],
    package: existingPackage = null,
    selectedSiteId = null,
}) {
    const isEditing = Boolean(existingPackage);

    const { data, setData, post, put, processing, errors } = useForm({
        site_id: existingPackage?.site_id ?? selectedSiteId ?? sites[0]?.id ?? '',
        name: existingPackage?.name ?? '',
        description: existingPackage?.description ?? '',
        price: existingPackage?.price ?? '',
        duration_minutes: existingPackage?.duration_minutes ?? 60,
        download_speed_kbps: existingPackage?.download_speed_kbps ?? 2048,
        upload_speed_kbps: existingPackage?.upload_speed_kbps ?? 1024,
        data_limit_mb: existingPackage?.data_limit_mb ?? '',
        device_limit: existingPackage?.device_limit ?? 1,
        is_active: existingPackage?.is_active ?? true,
    });

    const submit = (event) => {
        event.preventDefault();

        if (isEditing) {
            put(`/packages/${existingPackage.id}`);
        } else {
            post('/packages');
        }
    };

    const updateField = (field, value) => {
        setData(field, value);
    };

    return (
        <form onSubmit={submit} className="package-form">
            <div className="package-form-section">
                <div className="package-form-section-heading">
                    <span className="package-section-icon">📦</span>
                    <div>
                        <h3>Basic Information</h3>
                        <p>Enter the package name and selling price.</p>
                    </div>
                </div>

                <div className="package-form-grid">
                    <div className="package-field package-field-full">
                        <label>Site <span>*</span></label>

                        {isEditing ? (
                            <div className="package-readonly">
                                {sites.find(
                                    (site) => Number(site.id) === Number(data.site_id)
                                )?.name || 'Selected Site'}
                            </div>
                        ) : (
                            <select
                                value={data.site_id}
                                onChange={(e) => updateField('site_id', e.target.value)}
                                required
                            >
                                {sites.map((site) => (
                                    <option key={site.id} value={site.id}>
                                        {site.name}
                                        {site.code ? ` (${site.code})` : ''}
                                    </option>
                                ))}
                            </select>
                        )}

                        {errors.site_id && (
                            <small className="package-error">{errors.site_id}</small>
                        )}
                    </div>

                    <div className="package-field">
                        <label>Package Name <span>*</span></label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => updateField('name', e.target.value)}
                            placeholder="e.g. Dakika 60"
                            maxLength="255"
                            required
                        />
                        {errors.name && (
                            <small className="package-error">{errors.name}</small>
                        )}
                    </div>

                    <div className="package-field">
                        <label>Price (TZS) <span>*</span></label>
                        <div className="package-input-prefix">
                            <span>TZS</span>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.price}
                                onChange={(e) => updateField('price', e.target.value)}
                                placeholder="500"
                                required
                            />
                        </div>
                        {errors.price && (
                            <small className="package-error">{errors.price}</small>
                        )}
                    </div>

                    <div className="package-field package-field-full">
                        <label>Description</label>
                        <textarea
                            rows="3"
                            value={data.description}
                            onChange={(e) => updateField('description', e.target.value)}
                            placeholder="Describe what customers get with this package..."
                        />
                        {errors.description && (
                            <small className="package-error">{errors.description}</small>
                        )}
                    </div>
                </div>
            </div>

            <div className="package-form-section">
                <div className="package-form-section-heading">
                    <span className="package-section-icon">⏱️</span>
                    <div>
                        <h3>Validity & Speed</h3>
                        <p>Set the package duration and internet speed.</p>
                    </div>
                </div>

                <div className="package-form-grid">
                    <div className="package-field">
                        <label>Duration (Minutes) <span>*</span></label>
                        <input
                            type="number"
                            min="1"
                            max="525600"
                            value={data.duration_minutes}
                            onChange={(e) => updateField('duration_minutes', e.target.value)}
                            required
                        />
                        <small className="package-hint">
                            60 = 1 hour, 1440 = 1 day, 43200 = 30 days.
                        </small>
                        {errors.duration_minutes && (
                            <small className="package-error">{errors.duration_minutes}</small>
                        )}
                    </div>

                    <div className="package-field">
                        <label>Device Limit <span>*</span></label>
                        <input
                            type="number"
                            min="1"
                            max="1000"
                            value={data.device_limit}
                            onChange={(e) => updateField('device_limit', e.target.value)}
                            required
                        />
                        <small className="package-hint">
                            Maximum devices allowed per subscription.
                        </small>
                        {errors.device_limit && (
                            <small className="package-error">{errors.device_limit}</small>
                        )}
                    </div>

                    <div className="package-field">
                        <label>Download Speed (Kbps) <span>*</span></label>
                        <input
                            type="number"
                            min="1"
                            value={data.download_speed_kbps}
                            onChange={(e) => updateField('download_speed_kbps', e.target.value)}
                            required
                        />
                        {errors.download_speed_kbps && (
                            <small className="package-error">{errors.download_speed_kbps}</small>
                        )}
                    </div>

                    <div className="package-field">
                        <label>Upload Speed (Kbps) <span>*</span></label>
                        <input
                            type="number"
                            min="1"
                            value={data.upload_speed_kbps}
                            onChange={(e) => updateField('upload_speed_kbps', e.target.value)}
                            required
                        />
                        {errors.upload_speed_kbps && (
                            <small className="package-error">{errors.upload_speed_kbps}</small>
                        )}
                    </div>
                </div>
            </div>

            <div className="package-form-section">
                <div className="package-form-section-heading">
                    <span className="package-section-icon">📊</span>
                    <div>
                        <h3>Data & Availability</h3>
                        <p>Choose the data allowance and package status.</p>
                    </div>
                </div>

                <div className="package-form-grid">
                    <div className="package-field">
                        <label>Data Limit (MB)</label>
                        <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={data.data_limit_mb}
                            onChange={(e) => updateField('data_limit_mb', e.target.value)}
                            placeholder="Leave empty for unlimited"
                        />
                        <small className="package-hint">
                            Leave blank to provide unlimited data.
                        </small>
                        {errors.data_limit_mb && (
                            <small className="package-error">{errors.data_limit_mb}</small>
                        )}
                    </div>

                    <div className="package-field">
                        <label>Package Status</label>
                        <select
                            value={data.is_active ? '1' : '0'}
                            onChange={(e) => updateField('is_active', e.target.value === '1')}
                        >
                            <option value="1">Active - Available to customers</option>
                            <option value="0">Inactive - Hidden from customers</option>
                        </select>
                        {errors.is_active && (
                            <small className="package-error">{errors.is_active}</small>
                        )}
                    </div>
                </div>
            </div>

            <div className="package-form-actions">
                <Link
                    href={`/packages${isEditing ? `?site_id=${existingPackage.site_id}` : ''}`}
                    className="package-cancel-button"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    className="package-submit-button"
                    disabled={processing || sites.length === 0}
                >
                    {processing
                        ? 'Saving...'
                        : isEditing
                            ? 'Save Changes'
                            : 'Create Package'}
                </button>
            </div>
        </form>
    );
}
