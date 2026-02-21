import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import CurrencySymbol from '@/Components/CurrencySymbol';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';

export default function Transactions({ auth, subscriptionTransactions, couponTransactions }) {
    const [activeTab, setActiveTab] = useState('subscriptions');

    const subCount = subscriptionTransactions.total || 0;
    const couponCount = couponTransactions.total || 0;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">💳 Transactions</h2>}
        >
            <Head title="Transactions" />

            <div className="py-6 sm:py-8">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Tab Navigation */}
                    <div className="flex gap-2 mb-6 bg-white/80 backdrop-blur-sm rounded-full p-1.5 border-2 border-dashed border-lucky-200 shadow-sm w-fit">
                        <TabButton
                            active={activeTab === 'subscriptions'}
                            onClick={() => setActiveTab('subscriptions')}
                            icon="🏆"
                            label="Subscriptions"
                            count={subCount}
                        />
                        <TabButton
                            active={activeTab === 'coupons'}
                            onClick={() => setActiveTab('coupons')}
                            icon="🎫"
                            label="Coupons"
                            count={couponCount}
                        />
                    </div>

                    {/* Subscription Transactions */}
                    {activeTab === 'subscriptions' && (
                        <div className="space-y-3">
                            {subscriptionTransactions.data.length > 0 ? (
                                subscriptionTransactions.data.map((tx) => (
                                    <TransactionCard key={tx.id} type="subscription" transaction={tx} />
                                ))
                            ) : (
                                <div className="coupon-card">
                                    <EmptyState
                                        icon="🏆"
                                        title="No subscription payments yet"
                                        description="Upgrade your plan to unlock more coupons and earn bonus stamps."
                                        actionLabel="Browse Plans"
                                        actionHref={route('subscriptions.index')}
                                    />
                                </div>
                            )}
                        </div>
                    )}

                    {/* Coupon Transactions */}
                    {activeTab === 'coupons' && (
                        <div className="space-y-3">
                            {couponTransactions.data.length > 0 ? (
                                couponTransactions.data.map((tx) => (
                                    <TransactionCard key={tx.id} type="coupon" transaction={tx} />
                                ))
                            ) : (
                                <div className="coupon-card">
                                    <EmptyState
                                        icon="🎫"
                                        title="No coupon redemptions yet"
                                        description="Redeem a coupon at a partner store to see your first transaction here."
                                        actionLabel="Browse Coupons"
                                        actionHref={route('coupons.index')}
                                    />
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function TabButton({ active, onClick, icon, label, count }) {
    return (
        <button
            onClick={onClick}
            className={`flex items-center gap-1.5 px-4 sm:px-5 py-2 rounded-full text-sm font-bold transition-all ${
                active
                    ? 'lucky-gradient text-white shadow-md'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-lucky-50'
            }`}
        >
            <span>{icon}</span>
            <span className="hidden sm:inline">{label}</span>
            <span className={`text-xs px-1.5 py-0.5 rounded-full font-bold ${
                active ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'
            }`}>
                {count}
            </span>
        </button>
    );
}

function TransactionCard({ type, transaction: tx }) {
    const [expanded, setExpanded] = useState(false);

    if (type === 'subscription') {
        return (
            <div className="coupon-card overflow-hidden hover:shadow-lg transition-all duration-200">
                <div className="p-4 sm:p-5">
                    {/* Header row */}
                    <div className="flex justify-between items-start mb-3">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-lucky-100 to-lucky-200 flex items-center justify-center flex-shrink-0">
                                <span className="text-lg">🏆</span>
                            </div>
                            <div>
                                <h3 className="font-bold text-gray-900">{tx.plan_name}</h3>
                                <div className="flex items-center gap-2 mt-0.5">
                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-lucky-100 text-lucky-600">
                                        ⭐ Plan Purchase
                                    </span>
                                    <span className="text-xs text-gray-400">{tx.created_at}</span>
                                </div>
                            </div>
                        </div>
                        <StatusBadge status={tx.payment_status} />
                    </div>

                    {/* Quick summary */}
                    <div className="grid grid-cols-3 gap-2 sm:gap-3">
                        <MetricBox label="Amount" value={<><CurrencySymbol />{tx.amount.toFixed(2)}</>} />
                        <MetricBox label="GST" value={<><CurrencySymbol />{tx.gst_amount.toFixed(2)}</>} />
                        <MetricBox label="Total Paid" value={<><CurrencySymbol />{tx.total_amount.toFixed(2)}</>} highlight />
                    </div>

                    {/* Expandable details */}
                    <button
                        onClick={() => setExpanded(!expanded)}
                        className="mt-3 text-xs text-lucky-600 hover:text-lucky-700 font-medium flex items-center gap-1 transition-colors"
                    >
                        {expanded ? 'Hide' : 'Show'} details
                        <svg className={`w-3.5 h-3.5 transition-transform ${expanded ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {expanded && (
                        <div className="mt-3 bg-lucky-50/50 rounded-xl p-3 space-y-1.5 text-sm border border-lucky-100">
                            <DetailRow label="Payment Method" value={tx.payment_method} />
                            {tx.payment_id && <DetailRow label="Payment ID" value={tx.payment_id} mono />}
                        </div>
                    )}
                </div>
            </div>
        );
    }

    // Coupon transaction
    return (
        <div className="coupon-card overflow-hidden hover:shadow-lg transition-all duration-200">
            <div className="p-4 sm:p-5">
                {/* Header row */}
                <div className="flex justify-between items-start mb-3">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-ticket-100 to-ticket-200 flex items-center justify-center flex-shrink-0">
                            <span className="text-lg">🎫</span>
                        </div>
                        <div className="min-w-0">
                            <h3 className="font-bold text-gray-900 truncate">{tx.coupon_title}</h3>
                            <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-600">
                                    🎟️ Coupon Redemption
                                </span>
                                <span className="text-xs text-gray-500 truncate">📍 {tx.merchant_location}</span>
                            </div>
                            <p className="text-xs text-gray-400 mt-0.5">{tx.created_at}</p>
                        </div>
                    </div>
                    <StatusBadge status={tx.payment_status} />
                </div>

                {/* Quick summary grid */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <MetricBox label="Bill Amount" value={<><CurrencySymbol />{tx.bill_amount.toFixed(2)}</>} />
                    <MetricBox label="Discount" value={<>-<CurrencySymbol />{tx.discount_applied.toFixed(2)}</>} valueClass="text-green-600" />
                    <MetricBox label="Fee + GST" value={<><CurrencySymbol />{(tx.platform_fee + tx.gst_amount).toFixed(2)}</>} />
                    <MetricBox label="Total Paid" value={<><CurrencySymbol />{tx.total_paid.toFixed(2)}</>} highlight />
                </div>

                {/* Expandable details */}
                <button
                    onClick={() => setExpanded(!expanded)}
                    className="mt-3 text-xs text-lucky-600 hover:text-lucky-700 font-medium flex items-center gap-1 transition-colors"
                >
                    {expanded ? 'Hide' : 'Show'} details
                    <svg className={`w-3.5 h-3.5 transition-transform ${expanded ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {expanded && (
                    <div className="mt-3 bg-lucky-50/50 rounded-xl p-3 space-y-1.5 text-sm border border-lucky-100">
                        <DetailRow label="Platform Fee" value={<><CurrencySymbol />{tx.platform_fee.toFixed(2)}</>} />
                        <DetailRow label="GST" value={<><CurrencySymbol />{tx.gst_amount.toFixed(2)}</>} />
                        <DetailRow label="Payment Method" value={tx.payment_method} />
                        {tx.payment_id && <DetailRow label="Payment ID" value={tx.payment_id} mono />}
                    </div>
                )}
            </div>
        </div>
    );
}

function MetricBox({ label, value, highlight = false, valueClass = '' }) {
    return (
        <div className={`rounded-xl p-2.5 sm:p-3 ${highlight ? 'bg-gradient-to-br from-lucky-50 to-lucky-100 border border-lucky-200' : 'bg-gray-50 border border-gray-100'}`}>
            <p className="text-xs text-gray-400 uppercase tracking-wide font-medium">{label}</p>
            <p className={`text-sm sm:text-base font-bold mt-0.5 ${highlight ? 'text-lucky-700' : valueClass || 'text-gray-900'}`}>{value}</p>
        </div>
    );
}

function DetailRow({ label, value, mono = false }) {
    return (
        <div className="flex justify-between items-center">
            <span className="text-gray-500">{label}</span>
            <span className={`font-medium text-gray-900 ${mono ? 'font-mono text-xs' : 'capitalize'}`}>{value}</span>
        </div>
    );
}
