import { Link } from '@inertiajs/react';

export function money(value, currency = 'TZS') {
    if (value === null || value === undefined || value === '') return '—';
    return `${currency} ${Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export function date(value) {
    if (!value) return '—';
    const d = new Date(value);
    return Number.isNaN(d.getTime())
        ? '—'
        : d.toLocaleDateString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
        });
}

export function statusLabel(value) {
    return (value || 'unknown').replaceAll('_', ' ');
}

export function Status({ value }) {
    return <span className={`v-status v-status-${value || 'unknown'}`}>
        {statusLabel(value)}
    </span>;
}

export function Flash() {
    // Flash messages are rendered by each page using its own usePage props.
    return null;
}

export function Breadcrumb({ items = [] }) {
    return <div className="v-breadcrumb">
        <Link href="/dashboard">Dashboard</Link>
        {items.map((item, i) => (
            <span key={`${item.label}-${i}`}>
                <span className="v-breadcrumb-separator">/</span>
                {item.href ? <Link href={item.href}>{item.label}</Link> : item.label}
            </span>
        ))}
    </div>;
}

export function Heading({ eyebrow = 'VOUCHER MANAGEMENT', title, description, action }) {
    return <div className="v-heading">
        <div>
            <span className="v-eyebrow">{eyebrow}</span>
            <h1>{title}</h1>
            {description && <p>{description}</p>}
        </div>
        {action}
    </div>;
}

export function Alert({ flash = {}, errors = {} }) {
    return <>
        {flash.success && <div className="v-alert success">✓ {flash.success}</div>}
        {flash.error && <div className="v-alert error">! {flash.error}</div>}
        {Object.keys(errors).length > 0 && (
            <div className="v-alert error">
                {Object.values(errors).map((error, i) => <div key={i}>{error}</div>)}
            </div>
        )}
    </>;
}

export function Stats({ items = [] }) {
    return <div className="v-stats">
        {items.map((item) => (
            <div className="v-stat" key={item.label}>
                <span className="v-stat-icon">{item.icon || '▣'}</span>
                <div>
                    <span>{item.label}</span>
                    <strong>{item.value ?? 0}</strong>
                    {item.note && <small>{item.note}</small>}
                </div>
            </div>
        ))}
    </div>;
}

export function Pager({ links = [] }) {
    if (!links.length) return null;
    return <div className="v-pagination">
        {links.map((link, i) => (
            <Link
                key={i}
                href={link.url || '#'}
                preserveScroll
                className={`${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`}
                dangerouslySetInnerHTML={{ __html: link.label }}
                onClick={(e) => { if (!link.url) e.preventDefault(); }}
            />
        ))}
    </div>;
}

export function Empty({ children = 'Hakuna taarifa zilizopatikana.' }) {
    return <div className="v-empty">{children}</div>;
}

export function CopyButton({ value, label = 'Copy' }) {
    return <button
        type="button"
        className="v-button v-button-light v-copy"
        onClick={() => navigator.clipboard?.writeText(String(value ?? ''))}
    >
        {label}
    </button>;
}
