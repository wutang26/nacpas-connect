import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import OrganizationForm from './Form';

export default function Edit({ organization }) {
    return <AuthenticatedLayout><OrganizationForm organization={organization} /></AuthenticatedLayout>;
}
