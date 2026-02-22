import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import CurrencySymbol from '@/Components/CurrencySymbol';
import EmptyState from '@/Components/EmptyState';
import { useState } from 'react';

export default function Dashboard({ auth, user, plan, primaryCampaign, eligibleCampaigns = [], stats }) {
    const allStatsZero = stats.stamps_count === 0 && stats.total_coupons_used === 0 && stats.total_discount_redeemed === 0;
    const [changingCampaign, setChangingCampaign] = useState(false);

    const handleCampaignChange = (campaignId) => {
        if (!campaignId || campaignId === primaryCampaign?.id) return;
        setChangingCampaign(true);
        router.post(route('subscriptions.setPrimaryCampaign'), { campaign_id: campaignId }, {
            onFinish: () => setChangingCampaign(false),
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">🎯 Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-6 sm:py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Profile & Plan Row */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Profile Card */}
                        <div className="coupon-card p-5 sm:p-6">
                            <div className="flex items-center gap-4 mb-5">
                                <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-2xl font-bold shadow-lg flex-shrink-0">
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <div className="min-w-0">
                                    <h3 className="font-display text-lg text-gray-900 truncate">Welcome back, {user.name.split(' ')[0]}!</h3>
                                    <p className="text-sm text-gray-500 truncate">{user.email}</p>
                                </div>
                            </div>
                            <dl className="space-y-0">
                                <ProfileRow icon="👤" label="Full Name" value={user.name} />
                                <ProfileRow icon="📧" label="Email" value={user.email} />
                                <ProfileRow icon="📅" label="Member Since" value={user.created_at} />
                                {primaryCampaign && (
                                    <ProfileRow icon="🏆" label="Campaign" value={primaryCampaign.reward_name} valueClass="text-lucky-600" last />
                                )}
                            </dl>
                        </div>

                        {/* Active Plan Card */}
                        <div className="coupon-card overflow-visible">
                            {plan && !plan.is_default && (
                                <div className="absolute -top-3 left-6 z-10">
                                    <span className="golden-badge px-4 py-1 rounded-full text-xs">⭐ ACTIVE PLAN</span>
                                </div>
                            )}
                            <div className="p-5 sm:p-6">
                                <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                                    <span className="text-2xl">🎫</span> Plan Details
                                </h3>
                                {plan ? (
                                    <>
                                        <p className="text-2xl font-display text-lucky-600 mb-4">
                                            {plan.name}
                                            {plan.is_default && <span className="ml-2 text-xs font-normal text-gray-400 font-sans">(Free)</span>}
                                        </p>
                                        <div className="grid grid-cols-3 gap-2 sm:gap-3 text-sm">
                                            <PlanMetric value={plan.stamps_on_purchase} label="Bonus Stamps" color="lucky" />
                                            <PlanMetric value={plan.stamps_per_100} label={<>Per <CurrencySymbol />100</>} color="lucky" />
                                            <PlanMetric value={plan.max_discounted_bills} label="Max Bills" color="ticket" />
                                        </div>
                                        <div className="grid grid-cols-2 gap-2 sm:gap-3 text-sm mt-2 sm:mt-3">
                                            <PlanMetric value={<><CurrencySymbol />{plan.max_redeemable_amount.toFixed(0)}</>} label="Max Redeem" color="ticket" />
                                            {plan.duration_days && (
                                                <PlanMetric value={plan.duration_days} label="Days Validity" color="prize" />
                                            )}
                                        </div>

                                        {/* Days remaining progress bar */}
                                        {plan.days_remaining !== null && plan.days_remaining >= 0 && plan.duration_days && (
                                            <div className="mt-4 bg-gray-50 rounded-xl p-3">
                                                <div className="flex justify-between text-xs text-gray-500 mb-1.5">
                                                    <span>Time Remaining</span>
                                                    <span className={`font-bold ${plan.days_remaining <= 7 ? 'text-red-600' : 'text-green-600'}`}>
                                                        {plan.days_remaining} days left
                                                    </span>
                                                </div>
                                                <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div
                                                        className={`h-full rounded-full transition-all duration-1000 ${plan.days_remaining <= 7 ? 'bg-red-500' : plan.days_remaining <= 14 ? 'bg-amber-500' : 'bg-green-500'}`}
                                                        style={{ width: `${Math.min(100, (plan.days_remaining / plan.duration_days) * 100)}%` }}
                                                    />
                                                </div>
                                                <div className="flex justify-between text-xs text-gray-400 mt-1">
                                                    {plan.purchased_at && <span>{plan.purchased_at}</span>}
                                                    {plan.expires_at && <span>{plan.expires_at}</span>}
                                                </div>
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <EmptyState
                                        icon="🎭"
                                        title="No active plan"
                                        description="Upgrade to unlock more coupons and earn more stamps!"
                                        actionLabel="Browse Plans"
                                        actionHref={route('subscriptions.index')}
                                    />
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Stats Row */}
                    {allStatsZero ? (
                        <div className="coupon-card p-6">
                            <EmptyState
                                icon="🚀"
                                title="Your journey starts here!"
                                description="Redeem a coupon at a partner store to earn your first stamps and see your stats come alive."
                                actionLabel="Browse Coupons"
                                actionHref={route('coupons.index')}
                            />
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
                            <StatCard label="Total Stamps" value={stats.stamps_count} icon="🎫" color="lucky" />
                            <StatCard label="Coupons Used" value={stats.total_coupons_used} icon="🎟️" color="green" />
                            <StatCard label="Discount Saved" value={<><CurrencySymbol />{stats.total_discount_redeemed.toFixed(0)}</>} icon="💰" color="emerald" />
                            <StatCard label="Bills Left" value={stats.remaining_bills} icon="📋" color="amber" />
                            <StatCard label="Redeem Left" value={<><CurrencySymbol />{stats.remaining_redeem_amount.toFixed(0)}</>} icon="🎁" color="rose" />
                        </div>
                    )}

                    {/* Campaign Selection */}
                    {eligibleCampaigns.length > 0 && (
                        <div className="coupon-card p-5 sm:p-6">
                            <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                                <span className="text-2xl">🎯</span> Your Campaign
                            </h3>

                            {primaryCampaign ? (
                                <div className="flex flex-col sm:flex-row sm:items-center gap-4">
                                    <div className="flex items-center gap-3 flex-1 min-w-0">
                                        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-lucky-100 to-lucky-200 flex items-center justify-center flex-shrink-0">
                                            <span className="text-lg">🏆</span>
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-sm font-bold text-gray-900 truncate">{primaryCampaign.reward_name}</p>
                                            <p className="text-xs text-gray-500">Stamps are assigned to this campaign</p>
                                        </div>
                                    </div>

                                    {eligibleCampaigns.length > 1 && (
                                        <div className="flex items-center gap-2">
                                            <select
                                                value={primaryCampaign.id}
                                                onChange={(e) => handleCampaignChange(Number(e.target.value))}
                                                disabled={changingCampaign}
                                                className="block w-full sm:w-auto border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-xl shadow-sm text-sm disabled:opacity-50"
                                            >
                                                {eligibleCampaigns.map(c => (
                                                    <option key={c.id} value={c.id}>{c.reward_name}</option>
                                                ))}
                                            </select>
                                            {changingCampaign && (
                                                <svg className="animate-spin h-4 w-4 text-lucky-500" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                    <p className="text-sm text-amber-800 font-medium mb-3">
                                        ⚠️ No campaign selected! Select a campaign to start earning stamps.
                                    </p>
                                    <select
                                        value=""
                                        onChange={(e) => handleCampaignChange(Number(e.target.value))}
                                        disabled={changingCampaign}
                                        className="block w-full border-amber-300 focus:border-lucky-500 focus:ring-lucky-500 rounded-xl shadow-sm text-sm disabled:opacity-50"
                                    >
                                        <option value="">Choose a campaign...</option>
                                        {eligibleCampaigns.map(c => (
                                            <option key={c.id} value={c.id}>{c.reward_name}</option>
                                        ))}
                                    </select>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Quick Links */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Link
                            href={route('stamps.index')}
                            className="coupon-card p-4 flex items-center gap-3 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5 group"
                        >
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-lucky-100 to-lucky-200 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <span className="text-lg">🎫</span>
                            </div>
                            <div>
                                <p className="font-bold text-gray-900 text-sm">My Stamps</p>
                                <p className="text-xs text-gray-500">{stats.stamps_count} stamps collected</p>
                            </div>
                        </Link>
                        <Link
                            href={route('transactions.index')}
                            className="coupon-card p-4 flex items-center gap-3 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5 group"
                        >
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-ticket-100 to-ticket-200 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <span className="text-lg">💳</span>
                            </div>
                            <div>
                                <p className="font-bold text-gray-900 text-sm">Transactions</p>
                                <p className="text-xs text-gray-500">View payment history</p>
                            </div>
                        </Link>
                        <Link
                            href={route('coupons.index')}
                            className="coupon-card p-4 flex items-center gap-3 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5 group"
                        >
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-green-200 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <span className="text-lg">🎟️</span>
                            </div>
                            <div>
                                <p className="font-bold text-gray-900 text-sm">Coupons</p>
                                <p className="text-xs text-gray-500">Discover new deals</p>
                            </div>
                        </Link>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ProfileRow({ icon, label, value, valueClass = 'text-gray-900', last = false }) {
    return (
        <div className={`flex justify-between py-2.5 text-sm ${!last ? 'border-b border-dashed border-lucky-100' : ''}`}>
            <dt className="text-gray-500 flex items-center gap-1.5">
                <span>{icon}</span> {label}
            </dt>
            <dd className={`font-bold ${valueClass} truncate ml-4`}>{value}</dd>
        </div>
    );
}

function PlanMetric({ value, label, color }) {
    const colors = {
        lucky: 'from-lucky-50 to-lucky-100 border-lucky-200 text-lucky-600',
        ticket: 'from-ticket-50 to-ticket-100 border-ticket-200 text-ticket-600',
        prize: 'from-prize-50 to-prize-100 border-prize-200 text-prize-600',
    };

    return (
        <div className={`bg-gradient-to-br ${colors[color]} rounded-xl p-2.5 sm:p-3 text-center border`}>
            <p className="text-xl sm:text-2xl font-bold">{value}</p>
            <p className="text-xs font-medium opacity-80 mt-0.5">{label}</p>
        </div>
    );
}

function StatCard({ label, value, icon, color }) {
    const colorMap = {
        lucky: 'from-lucky-100 to-lucky-200/80 text-lucky-700 border-lucky-300',
        green: 'from-green-100 to-green-200/80 text-green-700 border-green-300',
        emerald: 'from-emerald-100 to-emerald-200/80 text-emerald-700 border-emerald-300',
        amber: 'from-amber-100 to-amber-200/80 text-amber-700 border-amber-300',
        rose: 'from-rose-100 to-rose-200/80 text-rose-700 border-rose-300',
    };

    return (
        <div className={`rounded-2xl p-3 sm:p-4 text-center bg-gradient-to-br border-2 border-dashed shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 ${colorMap[color]}`}>
            <div className="text-xl sm:text-2xl mb-1">{icon}</div>
            <p className="text-xl sm:text-2xl font-bold leading-tight">{value}</p>
            <p className="text-xs mt-1 font-medium opacity-80">{label}</p>
        </div>
    );
}
