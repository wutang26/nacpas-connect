import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SaasPlanForm from './Form';

export default function Edit({ plan }) {
    return (
        <AuthenticatedLayout>
            <SaasPlanForm plan={plan} />
        </AuthenticatedLayout>
    );
}
