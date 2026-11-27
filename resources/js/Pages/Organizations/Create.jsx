import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import OrganizationForm from './Form';

export default function Create() {
    return <AuthenticatedLayout><OrganizationForm /></AuthenticatedLayout>;
}
