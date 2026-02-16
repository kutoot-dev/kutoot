import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ auth, campaigns }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Campaigns</h2>}
        >
            <Head title="Campaigns" />

            <div className="py-12 bg-gray-50 min-h-screen">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {campaigns.data.length > 0 ? (
                            campaigns.data.map((campaign) => (
                                <Link
                                    key={campaign.id}
                                    href={route('campaigns.show', campaign.id)}
                                    className="block group"
                                >
                                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                                        <div className="h-48 bg-gray-200 w-full object-cover relative overflow-hidden">
                                            <img
                                                src={campaign.creator?.merchant?.logo || `https://placehold.co/600x400?text=${encodeURIComponent(campaign.reward_name)}`}
                                                alt={campaign.reward_name}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            />
                                            <div className="absolute top-2 right-2 bg-white px-2 py-1 rounded-md text-xs font-bold text-indigo-600 shadow-sm">
                                                {campaign.category?.name}
                                            </div>
                                        </div>
                                        <div className="p-6">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {campaign.creator?.merchant?.name || 'Kutoot Exclusive'}
                                                </span>
                                            </div>
                                            <h3 className="text-lg font-bold text-gray-900 mb-2 truncate">
                                                {campaign.reward_name}
                                            </h3>
                                            <p className="text-sm text-gray-600 mb-4 line-clamp-2">
                                                Collect stamps to unlock this reward.
                                            </p>

                                            <div className="flex items-center text-sm text-gray-500">
                                                <svg className="w-4 h-4 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                                <span>Target: {campaign.stamp_target} Stamps</span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            ))
                        ) : (
                            <div className="col-span-full text-center py-10">
                                <h3 className="text-lg font-medium text-gray-900">No campaigns available</h3>
                                <p className="mt-1 text-sm text-gray-500">Check back later for new rewards.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
