import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import CurrencySymbol from '@/Components/CurrencySymbol';


export default function Index({ auth, plans, currentSubscription, isLoggedIn }) {
    const currentPlanIndex = plans.findIndex(p => p.id === currentSubscription?.plan_id);

    const handleUpgrade = (planId) => {
        if (confirm('Are you sure you want to upgrade to this plan?')) {
            router.post(route('subscriptions.upgrade'), { plan_id: planId });
        }
    };

    const tierColors = [
        { card: 'border-lucky-300', bg: 'from-lucky-50 to-lucky-100', accent: 'text-lucky-600', badge: 'bg-lucky-100 text-lucky-700 border-lucky-200', icon: '🎫' },
        { card: 'border-ticket-300', bg: 'from-ticket-50 to-ticket-100', accent: 'text-ticket-600', badge: 'bg-ticket-100 text-ticket-700 border-ticket-200', icon: '⭐' },
        { card: 'border-yellow-400', bg: 'from-yellow-50 to-amber-100', accent: 'text-amber-600', badge: 'bg-yellow-100 text-amber-700 border-yellow-300', icon: '👑' },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">⭐ Subscription Plans</h2>}
        >
            <Head title="Subscriptions" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {plans.map((plan, index) => {
                            const colors = tierColors[index % tierColors.length];
                            const isCurrent = currentSubscription?.plan_id === plan.id;

                            return (
                                <div key={plan.id} className={`coupon-card overflow-visible transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl ${isCurrent ? colors.card : ''}`}>
                                    {isCurrent && (
                                        <div className="absolute -top-3 left-6 z-10">
                                            <span className="golden-badge px-4 py-1 rounded-full text-xs">⭐ CURRENT PLAN</span>
                                        </div>
                                    )}
                                    <div className={`p-6 bg-gradient-to-br ${colors.bg} rounded-t-2xl`}>
                                        <div className="text-center mb-4">
                                            <span className="text-4xl">{colors.icon}</span>
                                        </div>
                                        <h3 className="font-display text-2xl text-gray-900 text-center mb-4">{plan.name}</h3>

                                        <div className="flex gap-3 mb-4">
                                            <div className="flex-1 bg-white/80 backdrop-blur-sm rounded-xl p-3 text-center border border-dashed border-lucky-200">
                                                <p className={`text-2xl font-bold ${colors.accent}`}>{plan.stamps_on_purchase}</p>
                                                <p className="text-xs text-gray-500 font-medium">🎫 Stamps / Buy</p>
                                            </div>
                                            <div className="flex-1 bg-white/80 backdrop-blur-sm rounded-xl p-3 text-center border border-dashed border-lucky-200">
                                                <p className={`text-2xl font-bold ${colors.accent}`}>{plan.stamps_per_100}</p>
                                                <p className="text-xs text-gray-500 font-medium">📊 per <CurrencySymbol />100</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Ticket perforation */}
                                    <div className="flex justify-center gap-2 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent">
                                        {[...Array(10)].map((_, i) => (
                                            <div key={i} className="w-2 h-2 rounded-full bg-lucky-200" />
                                        ))}
                                    </div>

                                    <div className="p-6">
                                        <ul className="text-sm text-gray-600 space-y-3 mb-6">
                                            <li className="flex justify-between py-1.5 border-b border-dashed border-lucky-100">
                                                <span className="text-gray-500">🎟️ Max Discounted Bills</span>
                                                <span className="font-bold text-gray-900">{plan.max_discounted_bills}</span>
                                            </li>
                                            <li className="flex justify-between py-1.5 border-b border-dashed border-lucky-100">
                                                <span className="text-gray-500">💰 Max Redeemable</span>
                                                <span className="font-bold text-gray-900"><CurrencySymbol />{parseFloat(plan.max_redeemable_amount).toFixed(2)}</span>
                                            </li>
                                            <li className="flex justify-between py-1.5">
                                                <span className="text-gray-500">⏳ Validity</span>
                                                <span className="font-bold text-gray-900">{plan.duration_days ? `${plan.duration_days} days` : '∞'}</span>
                                            </li>
                                        </ul>

                                        {isCurrent ? (
                                            <div>
                                                <button disabled className="w-full golden-badge py-2.5 px-4 rounded-full cursor-not-allowed text-sm">
                                                    ⭐ Current Plan
                                                </button>
                                                {currentSubscription.expires_at && (
                                                    <p className="text-center text-xs text-gray-400 mt-2 bg-gray-50 rounded-full py-1">
                                                        ⏳ Expires: <span className="font-bold">{currentSubscription.expires_at}</span>
                                                    </p>
                                                )}
                                            </div>
                                        ) : plan.is_default ? (
                                            <p className="w-full text-center text-xs text-gray-400 py-2.5 bg-gray-50 rounded-full">Auto-assigned on registration</p>
                                        ) : !isLoggedIn ? (
                                            <Link
                                                href={route('login')}
                                                className="w-full block text-center lucky-gradient text-white font-bold py-2.5 px-4 rounded-full transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm"
                                            >
                                                🔑 Login to Upgrade
                                            </Link>
                                        ) : plans.indexOf(plan) > currentPlanIndex ? (
                                            <button
                                                onClick={() => handleUpgrade(plan.id)}
                                                className="w-full lucky-gradient text-white font-bold py-2.5 px-4 rounded-full transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm"
                                            >
                                                🚀 Upgrade
                                            </button>
                                        ) : (
                                            <p className="w-full text-center text-xs text-gray-400 py-2.5 bg-gray-50 rounded-full">Lower tier</p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
