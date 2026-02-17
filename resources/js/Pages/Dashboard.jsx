import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import CurrencySymbol from '@/Components/CurrencySymbol';

export default function Dashboard({ auth, user, plan, primaryCampaign, stats, recentTransactions, recentRedemptions, activityLogs }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">🎯 Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                    {/* Profile & Plan Row */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Profile Card */}
                        <div className="coupon-card p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="w-12 h-12 rounded-full bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-xl font-bold shadow-lg">
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <h3 className="font-display text-lg text-gray-900">Welcome back!</h3>
                                    <p className="text-sm text-gray-500">{user.email}</p>
                                </div>
                            </div>
                            <dl className="space-y-2 text-sm">
                                <div className="flex justify-between py-1 border-b border-dashed border-lucky-100">
                                    <dt className="text-gray-500">👤 Name</dt>
                                    <dd className="font-bold text-gray-900">{user.name}</dd>
                                </div>
                                <div className="flex justify-between py-1 border-b border-dashed border-lucky-100">
                                    <dt className="text-gray-500">📧 Email</dt>
                                    <dd className="font-bold text-gray-900">{user.email}</dd>
                                </div>
                                <div className="flex justify-between py-1 border-b border-dashed border-lucky-100">
                                    <dt className="text-gray-500">📅 Member Since</dt>
                                    <dd className="font-bold text-gray-900">{user.created_at}</dd>
                                </div>
                                {primaryCampaign && (
                                    <div className="flex justify-between py-1">
                                        <dt className="text-gray-500">🏆 Campaign</dt>
                                        <dd className="font-bold text-lucky-600">{primaryCampaign}</dd>
                                    </div>
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
                            <div className="p-6">
                                <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                                    <span className="text-2xl">🎫</span> Plan Details
                                </h3>
                                {plan ? (
                                    <>
                                        <p className="text-2xl font-display text-lucky-600 mb-3">
                                            {plan.name}
                                            {plan.is_default && <span className="ml-2 text-xs font-normal text-gray-400 font-sans">(Base)</span>}
                                        </p>
                                        <div className="grid grid-cols-3 gap-3 text-sm">
                                            <div className="bg-gradient-to-br from-lucky-50 to-lucky-100 rounded-xl p-3 text-center border border-lucky-200">
                                                <p className="text-2xl font-bold text-lucky-600">{plan.stamps_on_purchase}</p>
                                                <p className="text-xs text-lucky-700 font-medium">Bonus Stamps</p>
                                            </div>
                                            <div className="bg-gradient-to-br from-lucky-50 to-lucky-100 rounded-xl p-3 text-center border border-lucky-200">
                                                <p className="text-2xl font-bold text-lucky-600">{plan.stamps_per_100}</p>
                                                <p className="text-xs text-lucky-700 font-medium">Per <CurrencySymbol />100</p>
                                            </div>
                                            <div className="bg-gradient-to-br from-ticket-50 to-ticket-100 rounded-xl p-3 text-center border border-ticket-200">
                                                <p className="text-2xl font-bold text-ticket-600">{plan.max_discounted_bills}</p>
                                                <p className="text-xs text-ticket-700 font-medium">Max Bills</p>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3 text-sm mt-3">
                                            <div className="bg-gradient-to-br from-ticket-50 to-ticket-100 rounded-xl p-3 text-center border border-ticket-200">
                                                <p className="text-2xl font-bold text-ticket-600"><CurrencySymbol />{plan.max_redeemable_amount.toFixed(2)}</p>
                                                <p className="text-xs text-ticket-700 font-medium">Max Redeem</p>
                                            </div>
                                            {plan.duration_days && (
                                                <div className="bg-gradient-to-br from-prize-50 to-prize-100 rounded-xl p-3 text-center border border-prize-200">
                                                    <p className="text-2xl font-bold text-prize-600">{plan.duration_days}</p>
                                                    <p className="text-xs text-prize-700 font-medium">Validity (Days)</p>
                                                </div>
                                            )}
                                        </div>
                                        {(plan.purchased_at || plan.expires_at) && (
                                            <div className="mt-3 space-y-1 text-xs text-gray-500 bg-gray-50 rounded-xl p-3">
                                                {plan.purchased_at && (
                                                    <div className="flex justify-between">
                                                        <span>Purchased</span>
                                                        <span className="font-bold text-gray-700">{plan.purchased_at}</span>
                                                    </div>
                                                )}
                                                {plan.expires_at && (
                                                    <div className="flex justify-between">
                                                        <span>Expires</span>
                                                        <span className="font-bold text-gray-700">{plan.expires_at}</span>
                                                    </div>
                                                )}
                                                {plan.days_remaining !== null && plan.days_remaining >= 0 && (
                                                    <div className="flex justify-between">
                                                        <span>Remaining</span>
                                                        <span className={`font-bold ${plan.days_remaining <= 7 ? 'text-red-600' : 'text-green-600'}`}>
                                                            {plan.days_remaining} days
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </>
                                ) : (
                                    <div className="text-center py-4">
                                        <span className="text-4xl">🎭</span>
                                        <p className="text-gray-400 mt-2">No active plan</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Stats Row */}
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <StatCard label="Total Stamps" value={stats.stamps_count} emoji="🎫" color="lucky" />
                        <StatCard label="Coupons Used" value={stats.total_coupons_used} emoji="🎟️" color="green" />
                        <StatCard label="Discount Redeemed" value={<><CurrencySymbol />{stats.total_discount_redeemed.toFixed(2)}</>} emoji="💰" color="emerald" />
                        <StatCard label="Bills Remaining" value={stats.remaining_bills} emoji="📋" color="amber" />
                        <StatCard label="Redeem Amount Left" value={<><CurrencySymbol />{stats.remaining_redeem_amount.toFixed(2)}</>} emoji="🎁" color="rose" />
                    </div>

                    {/* Transactions & Redemptions */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Recent Transactions */}
                        <div className="coupon-card p-6">
                            <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                                <span className="text-xl">📜</span> Recent Transactions
                            </h3>
                            {recentTransactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b-2 border-dashed border-lucky-200 text-left text-lucky-600">
                                                <th className="pb-2 font-bold">Coupon</th>
                                                <th className="pb-2 font-bold">Location</th>
                                                <th className="pb-2 font-bold text-right">Amount</th>
                                                <th className="pb-2 font-bold text-right">When</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-dashed divide-lucky-100">
                                            {recentTransactions.map(t => (
                                                <tr key={t.id} className="hover:bg-lucky-50/50 transition-colors">
                                                    <td className="py-2.5 text-gray-900 font-medium">{t.coupon_title ?? '—'}</td>
                                                    <td className="py-2.5 text-gray-600">{t.location_name ?? '—'}</td>
                                                    <td className="py-2.5 text-right font-bold text-lucky-600"><CurrencySymbol />{t.total_amount.toFixed(2)}</td>
                                                    <td className="py-2.5 text-right text-gray-400 text-xs">{t.created_at}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-6">
                                    <span className="text-3xl">💤</span>
                                    <p className="text-gray-400 text-sm mt-2">No transactions yet.</p>
                                </div>
                            )}
                        </div>

                        {/* Coupon Redemptions */}
                        <div className="coupon-card p-6">
                            <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                                <span className="text-xl">🎫</span> Coupons Used
                            </h3>
                            {recentRedemptions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b-2 border-dashed border-ticket-200 text-left text-ticket-600">
                                                <th className="pb-2 font-bold">Coupon</th>
                                                <th className="pb-2 font-bold text-right">Discount</th>
                                                <th className="pb-2 font-bold text-right">Bill</th>
                                                <th className="pb-2 font-bold text-right">When</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-dashed divide-ticket-100">
                                            {recentRedemptions.map(r => (
                                                <tr key={r.id} className="hover:bg-ticket-50/50 transition-colors">
                                                    <td className="py-2.5 text-gray-900 font-medium">{r.coupon_title ?? '—'}</td>
                                                    <td className="py-2.5 text-right font-bold text-green-600"><CurrencySymbol />{r.discount_applied.toFixed(2)}</td>
                                                    <td className="py-2.5 text-right text-gray-600">{r.bill_amount ? <><CurrencySymbol />{r.bill_amount.toFixed(2)}</> : '—'}</td>
                                                    <td className="py-2.5 text-right text-gray-400 text-xs">{r.created_at}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-6">
                                    <span className="text-3xl">🎭</span>
                                    <p className="text-gray-400 text-sm mt-2">No coupons redeemed yet.</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Activity Log */}
                    <div className="coupon-card p-6">
                        <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                            <span className="text-xl">📋</span> Activity Log
                        </h3>
                        {activityLogs.length > 0 ? (
                            <ul className="space-y-3">
                                {activityLogs.map(log => (
                                    <li key={log.id} className="flex items-start gap-3 text-sm p-2 rounded-lg hover:bg-lucky-50/50 transition-colors">
                                        <span className="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-lucky-100 to-lucky-200 flex items-center justify-center text-lucky-600 text-xs font-bold">
                                            ⚡
                                        </span>
                                        <div className="flex-1">
                                            <p className="text-gray-900">
                                                <span className="font-bold capitalize text-lucky-700">{log.event ?? 'action'}</span>
                                                {log.subject_type && <span className="text-gray-500"> on {log.subject_type}</span>}
                                                {' — '}
                                                {log.description}
                                            </p>
                                            <p className="text-xs text-gray-400">{log.created_at}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="text-center py-6">
                                <span className="text-3xl">📭</span>
                                <p className="text-gray-400 text-sm mt-2">No activity yet.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({ label, value, emoji, color }) {
    const colorMap = {
        lucky: 'from-lucky-100 to-lucky-200 text-lucky-700 border-lucky-300',
        green: 'from-green-100 to-green-200 text-green-700 border-green-300',
        emerald: 'from-emerald-100 to-emerald-200 text-emerald-700 border-emerald-300',
        amber: 'from-amber-100 to-amber-200 text-amber-700 border-amber-300',
        rose: 'from-rose-100 to-rose-200 text-rose-700 border-rose-300',
    };

    return (
        <div className={`rounded-2xl p-4 text-center bg-gradient-to-br border-2 border-dashed shadow-sm hover:shadow-md transition-shadow ${colorMap[color] ?? 'from-gray-100 to-gray-200 text-gray-700 border-gray-300'}`}>
            <div className="text-2xl mb-1">{emoji}</div>
            <p className="text-2xl font-bold">{value}</p>
            <p className="text-xs mt-1 font-medium opacity-80">{label}</p>
        </div>
    );
}
