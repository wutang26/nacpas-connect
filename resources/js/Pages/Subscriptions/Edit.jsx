import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SubscriptionForm from './Form';

export default function Edit({
    subscription,
    organizations,
    plans,
}) {
    return (
        <AuthenticatedLayout>
            <SubscriptionForm
                subscription={subscription}
                organizations={organizations}
                plans={plans}
            />
        </AuthenticatedLayout>
    );
}