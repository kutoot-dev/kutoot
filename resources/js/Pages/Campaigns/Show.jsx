import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import CurrencySymbol from '@/Components/CurrencySymbol';


export default function Show({ auth, campaign, bountyMeter, collectedCommission, issuedStamps }) {
    const progressPercentage = Math.min(Math.round(bountyMeter * 100), 100);
    const isLoggedIn = !!auth.user;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">🏆 Campaign Details</h2>}
        >
            <Head title={campaign.reward_name} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="coupon-card overflow-hidden">
                        <div className="md:flex">
                            <div className="md:flex-shrink-0">
                                <img
                                    className="h-48 w-full object-cover md:w-48"
                                    src={campaign.creator?.merchant?.logo || `https://placehold.co/400x400?text=${encodeURIComponent(campaign.reward_name)}`}
                                    alt={campaign.reward_name}
                                />
                            </div>
                            <div className="p-8 w-full">
                                <div className="uppercase tracking-wide text-sm text-lucky-600 font-bold">
                                    {campaign.category?.name}
                                </div>
                                <h1 className="block mt-1 text-lg leading-tight font-display text-gray-900">
                                    {campaign.reward_name}
                                </h1>
                                <p className="mt-2 text-gray-500">
                                    {campaign.description || 'Complete the requirements to unlock this reward!'}
                                </p>

                                <div className="mt-6">
                                    <h3 className="text-lg font-display text-gray-900 flex items-center gap-2">
                                        <span>📊</span> Progress
                                    </h3>

                                    <div className="relative pt-1">
                                        <div className="flex mb-2 items-center justify-between">
                                            <div>
                                                <span className="text-xs font-bold inline-block py-1 px-3 uppercase rounded-full text-lucky-700 bg-lucky-100 border border-lucky-200">
                                                    Bounty Meter
                                                </span>
                                            </div>
                                            <div className="text-right">
                                                <span className="text-xs font-bold inline-block text-lucky-600">
                                                    {progressPercentage}%
                                                </span>
                                            </div>
                                        </div>
                                        <div className="overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-lucky-100 border border-lucky-200">
                                            <div
                                                style={{ width: `${progressPercentage}%` }}
                                                className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center lucky-gradient transition-all duration-500 ease-out rounded-full"
                                            ></div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 mt-4 text-sm">
                                        <div className="bg-gradient-to-br from-lucky-50 to-lucky-100 p-4 rounded-xl border border-dashed border-lucky-200">
                                            <span className="block font-display text-lucky-700">💰 Review Spend</span>
                                            <span className="block text-gray-700">Collected: <span className="font-bold text-lucky-600"><CurrencySymbol />{parseFloat(collectedCommission).toFixed(2)}</span></span>
                                            <span className="block text-xs text-gray-400">Target: <CurrencySymbol />{parseFloat(campaign.reward_cost_target).toFixed(2)}</span>
                                        </div>

                                        <div className="bg-gradient-to-br from-ticket-50 to-ticket-100 p-4 rounded-xl border border-dashed border-ticket-200">
                                            <span className="block font-display text-ticket-700">🎫 Stamps</span>
                                            <span className="block text-gray-700">Collected: <span className="font-bold text-ticket-600">{issuedStamps}</span></span>
                                            <span className="block text-xs text-gray-400">Target: {campaign.stamp_target}</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-6 flex flex-wrap gap-3">
                                    <button
                                        disabled={progressPercentage < 100}
                                        className={`inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold shadow-md transition-all ${progressPercentage >= 100
                                            ? 'lucky-gradient text-white hover:shadow-lg transform hover:-translate-y-0.5 animate-pulse-glow'
                                            : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                            }`}
                                    >
                                        <span>{progressPercentage >= 100 ? '🎁' : '⏳'}</span>
                                        {progressPercentage >= 100 ? 'Claim Reward' : 'In Progress'}
                                    </button>

                                    {!isLoggedIn ? (
                                        <Link
                                            href={route('login')}
                                            className="inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold border-2 border-lucky-300 text-lucky-700 bg-white hover:bg-lucky-50 transition-colors"
                                        >
                                            🔑 Login to Set Primary
                                        </Link>
                                    ) : auth.user.primary_campaign_id !== campaign.id ? (
                                        <button
                                            onClick={() => {
                                                if (confirm('Set this as your primary campaign for future stamps?')) {
                                                    router.patch(route('profile.update-primary-campaign'), {
                                                        primary_campaign_id: campaign.id
                                                    });
                                                }
                                            }}
                                            className="inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold border-2 border-lucky-300 text-lucky-700 bg-white hover:bg-lucky-50 transition-colors"
                                        >
                                            🎯 Set as Primary
                                        </button>
                                    ) : (
                                        <span className="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold golden-badge">
                                            ⭐ Primary Campaign
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
