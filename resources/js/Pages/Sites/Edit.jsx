import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SiteForm from './Form';

export default function Edit({ site, organizations = [] }) {
    return <AuthenticatedLayout><SiteForm site={site} organizations={organizations} /></AuthenticatedLayout>;
}
