import { Link, useForm } from '@inertiajs/react';

export default function ProductForm({ product = null, categories = [] }) {
    const isEditing = Boolean(product);

    const form = useForm({
        sku: product?.sku || '',
        name: product?.name || '',
        category: product?.category || '',
        brand: product?.brand || '',
        model: product?.model || '',
        description: product?.description || '',
        specifications: product?.specifications || '',
        default_purchase_price: product?.default_purchase_price || '',
        default_selling_price: product?.default_selling_price || '',
        currency: product?.currency || 'TZS',
        unit_of_measure: product?.unit_of_measure || 'piece',
        is_active: product?.is_active ?? true,
    });

    const updateField = (field, value) => {
        form.setData(field, value);
    };

    const submit = (event) => {
        event.preventDefault();

        if (isEditing) {
            form.put(`/hardware/products/${product.id}`, {
                preserveScroll: true,
            });
        } else {
            form.post('/hardware/products', {
                preserveScroll: true,
            });
        }
    };

    return (
        <form onSubmit={submit} className="hardware-product-form">
            <section className="hardware-product-form-section">
                <div className="hardware-product-section-heading">
                    <span>01</span>
                    <div>
                        <h3>Product Information</h3>
                        <p>Basic identification and classification of the product.</p>
                    </div>
                </div>

                <div className="hardware-product-form-grid">
                    <div className="hardware-product-field">
                        <label>SKU <span>*</span></label>
                        <input
                            type="text"
                            value={form.data.sku}
                            onChange={(e) => updateField('sku', e.target.value.toUpperCase())}
                            placeholder="e.g. EAP725-OUT"
                            maxLength="80"
                            required
                        />
                        {form.errors.sku && <small className="hardware-product-error">{form.errors.sku}</small>}
                        <small className="hardware-product-hint">Unique stock keeping unit for this product.</small>
                    </div>

                    <div className="hardware-product-field">
                        <label>Product Name <span>*</span></label>
                        <input
                            type="text"
                            value={form.data.name}
                            onChange={(e) => updateField('name', e.target.value)}
                            placeholder="e.g. Omada WiFi 7 Outdoor Access Point"
                            maxLength="255"
                            required
                        />
                        {form.errors.name && <small className="hardware-product-error">{form.errors.name}</small>}
                    </div>

                    <div className="hardware-product-field">
                        <label>Category <span>*</span></label>
                        <select
                            value={form.data.category}
                            onChange={(e) => updateField('category', e.target.value)}
                            required
                        >
                            <option value="">Select category</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>{category}</option>
                            ))}
                        </select>
                        {form.errors.category && <small className="hardware-product-error">{form.errors.category}</small>}
                    </div>

                    <div className="hardware-product-field">
                        <label>Brand</label>
                        <input
                            type="text"
                            value={form.data.brand}
                            onChange={(e) => updateField('brand', e.target.value)}
                            placeholder="e.g. TP-Link"
                            maxLength="255"
                        />
                        {form.errors.brand && <small className="hardware-product-error">{form.errors.brand}</small>}
                    </div>

                    <div className="hardware-product-field">
                        <label>Model</label>
                        <input
                            type="text"
                            value={form.data.model}
                            onChange={(e) => updateField('model', e.target.value)}
                            placeholder="e.g. EAP725-Outdoor"
                            maxLength="255"
                        />
                        {form.errors.model && <small className="hardware-product-error">{form.errors.model}</small>}
                    </div>

                    <div className="hardware-product-field">
                        <label>Unit of Measure <span>*</span></label>
                        <select
                            value={form.data.unit_of_measure}
                            onChange={(e) => updateField('unit_of_measure', e.target.value)}
                            required
                        >
                            <option value="piece">Piece</option>
                            <option value="metre">Metre</option>
                            <option value="roll">Roll</option>
                            <option value="box">Box</option>
                            <option value="set">Set</option>
                            <option value="pair">Pair</option>
                            <option value="pack">Pack</option>
                        </select>
                        {form.errors.unit_of_measure && <small className="hardware-product-error">{form.errors.unit_of_measure}</small>}
                    </div>

                    <div className="hardware-product-field hardware-product-field-full">
                        <label>Description</label>
                        <textarea
                            rows="3"
                            value={form.data.description}
                            onChange={(e) => updateField('description', e.target.value)}
                            placeholder="Describe this hardware product..."
                        />
                        {form.errors.description && <small className="hardware-product-error">{form.errors.description}</small>}
                    </div>

                    <div className="hardware-product-field hardware-product-field-full">
                        <label>Technical Specifications</label>
                        <textarea
                            rows="5"
                            value={form.data.specifications}
                            onChange={(e) => updateField('specifications', e.target.value)}
                            placeholder="Enter technical specifications, features and supported standards..."
                        />
                        {form.errors.specifications && <small className="hardware-product-error">{form.errors.specifications}</small>}
                    </div>
                </div>
            </section>

            <section className="hardware-product-form-section">
                <div className="hardware-product-section-heading">
                    <span>02</span>
                    <div>
                        <h3>Pricing Information</h3>
                        <p>Default purchase and selling prices. Actual transaction prices will be recorded separately.</p>
                    </div>
                </div>

                <div className="hardware-product-form-grid">
                    <div className="hardware-product-field">
                        <label>Default Purchase Price</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.data.default_purchase_price}
                            onChange={(e) => updateField('default_purchase_price', e.target.value)}
                            placeholder="e.g. 250000"
                        />
                        {form.errors.default_purchase_price && <small className="hardware-product-error">{form.errors.default_purchase_price}</small>}
                    </div>

                    <div className="hardware-product-field">
                        <label>Default Selling Price</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.data.default_selling_price}
                            onChange={(e) => updateField('default_selling_price', e.target.value)}
                            placeholder="e.g. 320000"
                        />
                        {form.errors.default_selling_price && <small className="hardware-product-error">{form.errors.default_selling_price}</small>}
                    </div>

                    <div className="hardware-product-field">
                        <label>Currency <span>*</span></label>
                        <select
                            value={form.data.currency}
                            onChange={(e) => updateField('currency', e.target.value)}
                            required
                        >
                            <option value="TZS">TZS - Tanzanian Shilling</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                        {form.errors.currency && <small className="hardware-product-error">{form.errors.currency}</small>}
                    </div>
                </div>

                <div className="hardware-product-price-note">
                    These prices are catalogue defaults. Purchase orders and sales invoices will store their own actual prices.
                </div>
            </section>

            <section className="hardware-product-form-section">
                <div className="hardware-product-section-heading">
                    <span>03</span>
                    <div>
                        <h3>Product Availability</h3>
                        <p>Control whether this product is available for hardware operations.</p>
                    </div>
                </div>

                <div className="hardware-product-form-grid">
                    <div className="hardware-product-field hardware-product-field-full">
                        <label>Product Status</label>
                        <select
                            value={form.data.is_active ? '1' : '0'}
                            onChange={(e) => updateField('is_active', e.target.value === '1')}
                        >
                            <option value="1">Active - Available for use</option>
                            <option value="0">Inactive - Not available for new transactions</option>
                        </select>
                        {form.errors.is_active && <small className="hardware-product-error">{form.errors.is_active}</small>}
                    </div>
                </div>
            </section>

            <div className="hardware-product-form-actions">
                <Link href="/hardware/products" className="hardware-product-cancel">
                    Cancel
                </Link>

                <button
                    type="submit"
                    className="hardware-product-submit"
                    disabled={form.processing}
                >
                    {form.processing
                        ? 'Saving...'
                        : isEditing
                            ? 'Save Changes'
                            : 'Register Product'}
                </button>
            </div>
        </form>
    );
}
