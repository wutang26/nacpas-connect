import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SaasPlanForm from './Form';

export default function Create() {
    return (
        <AuthenticatedLayout>
            <SaasPlanForm />
        </AuthenticatedLayout>
    );
}
