import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SiteForm from './Form';

export default function Create({ organizations = [] }) {
    return <AuthenticatedLayout><SiteForm organizations={organizations} /></AuthenticatedLayout>;
}
