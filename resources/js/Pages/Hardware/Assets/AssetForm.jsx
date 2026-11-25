import { Link, useForm } from '@inertiajs/react';

export default function AssetForm({ products = [] }) {
    const firstProduct = products[0] || null;

    const form = useForm({
        product_id: firstProduct ? String(firstProduct.id) : '',
        serial_number: '',
        mac_address: '',
        condition: 'new',
        purchase_date: '',
        purchase_cost: firstProduct?.default_purchase_price ?? '',
        currency: firstProduct?.currency || 'TZS',
        warranty_expires_at: '',
        notes: '',
    });

    const selectedProduct = products.find(
        (product) => String(product.id) === String(form.data.product_id)
    );

    const updateProduct = (productId) => {
        const product = products.find(
            (item) => String(item.id) === String(productId)
        );

       form.setData({
        ...form.data,
        product_id: productId,
        purchase_cost: product?.default_purchase_price ?? '',
        currency: product?.currency || 'TZS',
    });
    };

    const submit = (event) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            serial_number: data.serial_number.trim() || null,
            mac_address: data.mac_address.trim().toUpperCase() || null,
            purchase_date: data.purchase_date || null,
            purchase_cost: data.purchase_cost === '' ? null : data.purchase_cost,
            warranty_expires_at: data.warranty_expires_at || null,
        }));

        form.post('/hardware/assets', {
            preserveScroll: true,
            onFinish: () => form.transform((data) => data),
        });
    };

    return (
        <form onSubmit={submit} className="hardware-asset-form">
            <section className="hardware-asset-form-section">
                <div className="hardware-asset-section-heading">
                    <span>01</span>
                    <div>
                        <h3>Device Identification</h3>
                        <p>
                            Select the catalogue product and enter the unique
                            identifiers of this physical unit.
                        </p>
                    </div>
                </div>

                <div className="hardware-asset-form-grid">
                    <div className="hardware-asset-field hardware-asset-field-full">
                        <label htmlFor="asset-product">
                            Catalogue Product <span>*</span>
                        </label>
                        <select
                            id="asset-product"
                            value={form.data.product_id}
                            onChange={(e) => updateProduct(e.target.value)}
                            required
                        >
                            <option value="">Select a product</option>
                            {products.map((product) => (
                                <option key={product.id} value={product.id}>
                                    {product.name} ({product.sku})
                                    {product.brand ? ` - ${product.brand}` : ''}
                                </option>
                            ))}
                        </select>
                        {form.errors.product_id && (
                            <small className="hardware-asset-error">
                                {form.errors.product_id}
                            </small>
                        )}
                        {selectedProduct && (
                            <small className="hardware-asset-hint">
                                Selected: {selectedProduct.name}
                                {selectedProduct.model ? ` - ${selectedProduct.model}` : ''}
                            </small>
                        )}
                    </div>

                    <div className="hardware-asset-field">
                        <label htmlFor="asset-serial">Serial Number</label>
                        <input
                            id="asset-serial"
                            type="text"
                            value={form.data.serial_number}
                            onChange={(e) => form.setData('serial_number', e.target.value)}
                            placeholder="Enter device serial number"
                            maxLength="255"
                        />
                        {form.errors.serial_number && (
                            <small className="hardware-asset-error">
                                {form.errors.serial_number}
                            </small>
                        )}
                        <small className="hardware-asset-hint">
                            Leave blank if the device has no readable serial number.
                        </small>
                    </div>

                    <div className="hardware-asset-field">
                        <label htmlFor="asset-mac">MAC Address</label>
                        <input
                            id="asset-mac"
                            type="text"
                            value={form.data.mac_address}
                            onChange={(e) => form.setData('mac_address', e.target.value.toUpperCase())}
                            placeholder="e.g. A4:2B:B0:12:34:56"
                            maxLength="50"
                        />
                        {form.errors.mac_address && (
                            <small className="hardware-asset-error">
                                {form.errors.mac_address}
                            </small>
                        )}
                        <small className="hardware-asset-hint">
                            Enter the hardware MAC address where available.
                        </small>
                    </div>

                    <div className="hardware-asset-field">
                        <label htmlFor="asset-condition">
                            Device Condition <span>*</span>
                        </label>
                        <select
                            id="asset-condition"
                            value={form.data.condition}
                            onChange={(e) => form.setData('condition', e.target.value)}
                            required
                        >
                            <option value="new">New</option>
                            <option value="used">Used</option>
                            <option value="refurbished">Refurbished</option>
                            <option value="damaged">Damaged</option>
                        </select>
                        {form.errors.condition && (
                            <small className="hardware-asset-error">
                                {form.errors.condition}
                            </small>
                        )}
                    </div>
                </div>
            </section>

            <section className="hardware-asset-form-section">
                <div className="hardware-asset-section-heading">
                    <span>02</span>
                    <div>
                        <h3>Purchase Information</h3>
                        <p>
                            Record the actual purchase date, cost and currency
                            for this individual device.
                        </p>
                    </div>
                </div>

                <div className="hardware-asset-form-grid">
                    <div className="hardware-asset-field">
                        <label htmlFor="asset-purchase-date">Purchase Date</label>
                        <input
                            id="asset-purchase-date"
                            type="date"
                            value={form.data.purchase_date}
                            onChange={(e) => form.setData('purchase_date', e.target.value)}
                        />
                        {form.errors.purchase_date && (
                            <small className="hardware-asset-error">
                                {form.errors.purchase_date}
                            </small>
                        )}
                    </div>

                    <div className="hardware-asset-field">
                        <label htmlFor="asset-purchase-cost">Actual Purchase Cost</label>
                        <input
                            id="asset-purchase-cost"
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.data.purchase_cost}
                            onChange={(e) => form.setData('purchase_cost', e.target.value)}
                            placeholder="e.g. 250000"
                        />
                        {form.errors.purchase_cost && (
                            <small className="hardware-asset-error">
                                {form.errors.purchase_cost}
                            </small>
                        )}
                        <small className="hardware-asset-hint">
                            This is the cost of this physical unit, not the catalogue default.
                        </small>
                    </div>

                    <div className="hardware-asset-field">
                        <label htmlFor="asset-currency">
                            Currency <span>*</span>
                        </label>
                        <select
                            id="asset-currency"
                            value={form.data.currency}
                            onChange={(e) => form.setData('currency', e.target.value)}
                            required
                        >
                            <option value="TZS">TZS - Tanzanian Shilling</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                        {form.errors.currency && (
                            <small className="hardware-asset-error">
                                {form.errors.currency}
                            </small>
                        )}
                    </div>

                    <div className="hardware-asset-field">
                        <label htmlFor="asset-warranty">Warranty Expiry Date</label>
                        <input
                            id="asset-warranty"
                            type="date"
                            value={form.data.warranty_expires_at}
                            min={form.data.purchase_date || undefined}
                            onChange={(e) => form.setData('warranty_expires_at', e.target.value)}
                        />
                        {form.errors.warranty_expires_at && (
                            <small className="hardware-asset-error">
                                {form.errors.warranty_expires_at}
                            </small>
                        )}
                    </div>
                </div>

                <div className="hardware-asset-price-note">
                    The catalogue price is only a reference. This field records
                    the actual amount paid for this particular device.
                </div>
            </section>

            <section className="hardware-asset-form-section">
                <div className="hardware-asset-section-heading">
                    <span>03</span>
                    <div>
                        <h3>Inventory Status</h3>
                        <p>
                            Newly registered devices are automatically added
                            to available inventory.
                        </p>
                    </div>
                </div>

                <div className="hardware-asset-initial-status">
                    <div className="hardware-asset-status-symbol">✓</div>
                    <div>
                        <strong>Initial Status: In Stock</strong>
                        <p>
                            The device will be registered in NACPASI inventory.
                            Customer ownership, site assignment and installation
                            will be recorded during the relevant operations.
                        </p>
                    </div>
                </div>
            </section>

            <section className="hardware-asset-form-section">
                <div className="hardware-asset-section-heading">
                    <span>04</span>
                    <div>
                        <h3>Additional Information</h3>
                        <p>
                            Add notes that may help identify or manage this device.
                        </p>
                    </div>
                </div>

                <div className="hardware-asset-form-grid">
                    <div className="hardware-asset-field hardware-asset-field-full">
                        <label htmlFor="asset-notes">Notes</label>
                        <textarea
                            id="asset-notes"
                            rows="4"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            placeholder="Enter additional information about this device..."
                            maxLength="5000"
                        />
                        {form.errors.notes && (
                            <small className="hardware-asset-error">
                                {form.errors.notes}
                            </small>
                        )}
                    </div>
                </div>
            </section>

            <div className="hardware-asset-form-actions">
                <Link
                    href="/hardware/assets"
                    className="hardware-assets-secondary-button"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    className="hardware-assets-submit"
                    disabled={form.processing || products.length === 0}
                >
                    {form.processing ? 'Registering Device...' : 'Register Device Asset'}
                </button>
            </div>
        </form>
    );
}
