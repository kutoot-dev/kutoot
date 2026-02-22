import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import BountyMeter from '@/Components/BountyMeter';
import { Head, Link } from '@inertiajs/react';

export default function Index({ auth, campaigns }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">🏆 Campaigns</h2>}
        >
            <Head title="Campaigns" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {campaigns.data.length > 0 ? (
                            campaigns.data.map((campaign) => {
                                const isEligible = campaign.is_eligible !== false;

                                return isEligible ? (
                                    <Link
                                        key={campaign.id}
                                        href={route('campaigns.show', campaign.id)}
                                        className="block group"
                                    >
                                        <CampaignCard campaign={campaign} />
                                    </Link>
                                ) : (
                                    <div key={campaign.id} className="block relative">
                                        <div className="relative">
                                            <CampaignCard campaign={campaign} locked />
                                            {/* Lock overlay */}
                                            <div className="absolute inset-0 bg-gray-900/10 backdrop-blur-[1px] rounded-2xl flex flex-col items-center justify-center z-10">
                                                <div className="bg-white/95 backdrop-blur-sm rounded-2xl p-5 shadow-xl text-center mx-4 border border-gray-200">
                                                    <span className="text-3xl block mb-2">🔒</span>
                                                    <p className="text-sm font-bold text-gray-900 mb-1">
                                                        {campaign.required_plan
                                                            ? `Requires ${campaign.required_plan.name} Plan`
                                                            : 'Upgrade Required'}
                                                    </p>
                                                    <p className="text-xs text-gray-500 mb-3">
                                                        Upgrade your plan to access this campaign
                                                    </p>
                                                    <Link
                                                        href={route('subscriptions.index')}
                                                        className="inline-flex items-center gap-1.5 lucky-gradient text-white font-bold py-2 px-5 rounded-full text-xs shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all"
                                                    >
                                                        🚀 Upgrade Now
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        ) : (
                            <div className="col-span-full text-center py-16">
                                <span className="text-5xl mb-4 block">🎭</span>
                                <h3 className="font-display text-lg text-gray-900">No campaigns available</h3>
                                <p className="mt-1 text-sm text-gray-500">Check back later for new rewards.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function CampaignCard({ campaign, locked = false }) {
    return (
        <div className={`coupon-card overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 ${locked ? 'opacity-75 grayscale-[30%]' : ''}`}>
            <div className="h-48 bg-gradient-to-br from-lucky-100 to-ticket-100 w-full relative overflow-hidden">
                <img
                    src={campaign.creator?.merchant?.logo || `https://placehold.co/600x400?text=${encodeURIComponent(campaign.reward_name)}`}
                    alt={campaign.reward_name}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                <div className="absolute top-3 right-3 golden-badge px-3 py-1 rounded-full text-xs shadow-md">
                    {campaign.category?.name}
                </div>
                {locked && (
                    <div className="absolute top-3 left-3 bg-gray-800/80 text-white px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                        🔒 Locked
                    </div>
                )}
            </div>
            {/* Ticket perforation */}
            <div className="flex justify-center gap-2 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent">
                {[...Array(10)].map((_, i) => (
                    <div key={i} className="w-2 h-2 rounded-full bg-lucky-200" />
                ))}
            </div>
            <div className="p-5">
                <div className="flex items-center justify-between mb-2">
                    <span className="text-xs font-bold text-lucky-600 uppercase tracking-wider">
                        {campaign.creator?.merchant?.name || 'Kutoot Exclusive'}
                    </span>
                </div>
                <h3 className="font-display text-lg text-gray-900 mb-2 truncate">
                    {campaign.reward_name}
                </h3>
                <p className="text-sm text-gray-500 mb-4 line-clamp-2">
                    Collect stamps to unlock this reward.
                </p>

                <div className="mb-4">
                    <BountyMeter percentage={campaign.bounty_percentage} size="sm" />
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex items-center text-sm text-lucky-600 font-bold">
                        <span className="text-lg mr-1">🎫</span>
                        <span>Target: {campaign.stamp_target} Stamps</span>
                    </div>
                    {!locked && (
                        <span className="text-lucky-500 group-hover:translate-x-1 transition-transform">→</span>
                    )}
                </div>
            </div>
        </div>
    );
}
