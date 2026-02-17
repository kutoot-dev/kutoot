import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function Index({ auth, plans, currentSubscription }) {
    const handleUpgrade = (planId) => {
        if (confirm('Are you sure you want to upgrade to this plan?')) {
            router.post(route('subscriptions.upgrade'), { plan_id: planId });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Subscription Plans</h2>}
        >
            <Head title="Subscriptions" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {plans.map((plan) => (
                            <div key={plan.id} className={`bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 ${currentSubscription?.plan_id === plan.id ? 'border-indigo-500' : 'border-transparent'}`}>
                                <div className="p-6">
                                    <h3 className="text-2xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                                    <p className="text-gray-600 mb-4">
                                        Max Discounted Bills: {plan.max_discounted_bills}
                                    </p>
                                    <p className="text-gray-600 mb-4">
                                        Max Redeemable Amount: ${parseFloat(plan.max_redeemable_amount).toFixed(2)}
                                    </p>
                                    <p className="text-gray-600 mb-6">
                                        Concurrent Campaigns: {plan.max_concurrent_campaigns_per_bill}
                                    </p>

                                    {currentSubscription?.plan_id === plan.id ? (
                                        <button disabled className="w-full bg-indigo-100 text-indigo-700 font-bold py-2 px-4 rounded cursor-not-allowed">
                                            Current Plan
                                        </button>
                                    ) : (
                                        <button
                                            onClick={() => handleUpgrade(plan.id)}
                                            className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition duration-200"
                                        >
                                            {plan.is_default ? 'Revert to Base' : 'Upgrade'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
