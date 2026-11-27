import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SubscriptionForm from './Form';

export default function Create({ organizations, plans }) {
    return (
        <AuthenticatedLayout>
            <SubscriptionForm
                organizations={organizations}
                plans={plans}
            />
        </AuthenticatedLayout>
    );
}