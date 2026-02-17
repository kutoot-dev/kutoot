import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function Show({ auth, campaign, bountyMeter, collectedCommission, issuedStamps }) {
    const progressPercentage = Math.min(Math.round(bountyMeter * 100), 100);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Campaign Details</h2>}
        >
            <Head title={campaign.reward_name} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="md:flex">
                            <div className="md:flex-shrink-0">
                                <img
                                    className="h-48 w-full object-cover md:w-48"
                                    src={campaign.creator?.merchant?.logo || `https://placehold.co/400x400?text=${encodeURIComponent(campaign.reward_name)}`}
                                    alt={campaign.reward_name}
                                />
                            </div>
                            <div className="p-8 w-full">
                                <div className="uppercase tracking-wide text-sm text-indigo-500 font-semibold">
                                    {campaign.category?.name}
                                </div>
                                <h1 className="block mt-1 text-lg leading-tight font-medium text-black">
                                    {campaign.reward_name}
                                </h1>
                                <p className="mt-2 text-gray-500">
                                    {campaign.description || 'Complete the requirements to unlock this reward!'}
                                </p>

                                <div className="mt-6">
                                    <h3 className="text-lg font-medium text-gray-900">Progress</h3>

                                    <div className="relative pt-1">
                                        <div className="flex mb-2 items-center justify-between">
                                            <div>
                                                <span className="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-indigo-600 bg-indigo-200">
                                                    Bounty Meter
                                                </span>
                                            </div>
                                            <div className="text-right">
                                                <span className="text-xs font-semibold inline-block text-indigo-600">
                                                    {progressPercentage}%
                                                </span>
                                            </div>
                                        </div>
                                        <div className="overflow-hidden h-4 mb-4 text-xs flex rounded bg-indigo-200">
                                            <div
                                                style={{ width: `${progressPercentage}%` }}
                                                className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-500 ease-out"
                                            ></div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 mt-4 text-sm text-gray-600">
                                        <div className="bg-gray-50 p-3 rounded-lg">
                                            <span className="block font-bold text-gray-800">Review Spend</span>
                                            <span className="block">Collected: ${parseFloat(collectedCommission).toFixed(2)}</span>
                                            <span className="block text-xs text-gray-400">Target: ${parseFloat(campaign.reward_cost_target).toFixed(2)}</span>
                                        </div>
                                        <div className="bg-gray-50 p-3 rounded-lg">
                                            <span className="block font-bold text-gray-800">Stamps</span>
                                            <span className="block">Collected: {issuedStamps}</span>
                                            <span className="block text-xs text-gray-400">Target: {campaign.stamp_target}</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-6 flex space-x-4">
                                    <button
                                        disabled={progressPercentage < 100}
                                        className={`px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${progressPercentage >= 100
                                            ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                                            : 'bg-gray-300 cursor-not-allowed'
                                            }`}
                                    >
                                        {progressPercentage >= 100 ? 'Claim Reward' : 'In Progress'}
                                    </button>

                                    {auth.user.primary_campaign_id !== campaign.id ? (
                                        <button
                                            onClick={() => {
                                                if (confirm('Set this as your primary campaign for future stamps?')) {
                                                    // This would ideally be an Inertia post/patch
                                                    router.patch(route('profile.update-primary-campaign'), {
                                                        primary_campaign_id: campaign.id
                                                    });
                                                }
                                            }}
                                            className="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            Set as Primary
                                        </button>
                                    ) : (
                                        <span className="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                            Primary Campaign
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
