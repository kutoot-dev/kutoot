import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CurrencySymbol from '@/Components/CurrencySymbol';

export default function Transactions({ auth, subscriptionTransactions, couponTransactions }) {
    const [activeTab, setActiveTab] = useState('subscriptions');

    const getPaymentStatusColor = (status) => {
        switch (status?.toLowerCase()) {
            case 'paid':
            case 'completed':
                return 'text-green-600 bg-green-50';
            case 'pending':
                return 'text-yellow-600 bg-yellow-50';
            case 'failed':
                return 'text-red-600 bg-red-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">💳 Transactions</h2>}
        >
            <Head title="Transactions" />

            <div className="py-8">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">
                    {/* Tab Navigation */}
                    <div className="mb-6 flex gap-4 border-b border-gray-700">
                        <button
                            onClick={() => setActiveTab('subscriptions')}
                            className={`pb-3 px-1 font-semibold transition-colors ${
                                activeTab === 'subscriptions'
                                    ? 'text-white border-b-2 border-orange-500'
                                    : 'text-gray-400 hover:text-gray-300'
                            }`}
                        >
                            🏆 Subscription Plans ({subscriptionTransactions.total || 0})
                        </button>
                        <button
                            onClick={() => setActiveTab('coupons')}
                            className={`pb-3 px-1 font-semibold transition-colors ${
                                activeTab === 'coupons'
                                    ? 'text-white border-b-2 border-orange-500'
                                    : 'text-gray-400 hover:text-gray-300'
                            }`}
                        >
                            🎫 Coupon Redemptions ({couponTransactions.total || 0})
                        </button>
                    </div>

                    {/* Subscription Transactions Tab */}
                    {activeTab === 'subscriptions' && (
                        <div className="space-y-4">
                            {subscriptionTransactions.data.length > 0 ? (
                                <>
                                    <div className="space-y-3">
                                        {subscriptionTransactions.data.map((transaction) => (
                                            <div
                                                key={transaction.id}
                                                className="bg-gray-800 rounded-lg p-5 border border-gray-700 hover:border-orange-500 transition-colors"
                                            >
                                                <div className="flex justify-between items-start mb-3">
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-white">
                                                            {transaction.plan_name}
                                                        </h3>
                                                        <p className="text-sm text-gray-400 mt-1">
                                                            {transaction.created_at}
                                                        </p>
                                                    </div>
                                                    <span
                                                        className={`px-3 py-1 rounded-full text-sm font-medium ${getPaymentStatusColor(
                                                            transaction.payment_status
                                                        )}`}
                                                    >
                                                        {transaction.payment_status}
                                                    </span>
                                                </div>

                                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            Amount
                                                        </p>
                                                        <p className="text-lg font-bold text-white mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.amount.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            GST
                                                        </p>
                                                        <p className="text-lg font-bold text-white mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.gst_amount.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            Total
                                                        </p>
                                                        <p className="text-lg font-bold text-orange-500 mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.total_amount.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            Method
                                                        </p>
                                                        <p className="text-sm font-semibold text-white mt-1 capitalize">
                                                            {transaction.payment_method}
                                                        </p>
                                                    </div>
                                                </div>

                                                {transaction.payment_id && (
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            Payment ID
                                                        </p>
                                                        <p className="text-sm font-mono text-white mt-1">
                                                            {transaction.payment_id}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>

                                    {/* Pagination would go here if implemented */}
                                </>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-400 text-lg">No subscription transactions found</p>
                                    <Link
                                        href={route('subscriptions.index')}
                                        className="text-orange-500 hover:text-orange-400 mt-4 inline-block"
                                    >
                                        Browse Plans →
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Coupon Redemption Transactions Tab */}
                    {activeTab === 'coupons' && (
                        <div className="space-y-4">
                            {couponTransactions.data.length > 0 ? (
                                <>
                                    <div className="space-y-3">
                                        {couponTransactions.data.map((transaction) => (
                                            <div
                                                key={transaction.id}
                                                className="bg-gray-800 rounded-lg p-5 border border-gray-700 hover:border-orange-500 transition-colors"
                                            >
                                                <div className="flex justify-between items-start mb-3">
                                                    <div>
                                                        <h3 className="text-lg font-semibold text-white">
                                                            {transaction.coupon_title}
                                                        </h3>
                                                        <p className="text-sm text-gray-400 mt-1">
                                                            📍 {transaction.merchant_location}
                                                        </p>
                                                        <p className="text-xs text-gray-500 mt-1">
                                                            {transaction.created_at}
                                                        </p>
                                                    </div>
                                                    <span
                                                        className={`px-3 py-1 rounded-full text-sm font-medium ${getPaymentStatusColor(
                                                            transaction.payment_status
                                                        )}`}
                                                    >
                                                        {transaction.payment_status}
                                                    </span>
                                                </div>

                                                <div className="grid grid-cols-2 md:grid-cols-5 gap-3 mb-3">
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            Bill Amount
                                                        </p>
                                                        <p className="text-base font-bold text-white mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.bill_amount.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-green-900/30 rounded p-3 border border-green-700">
                                                        <p className="text-xs text-green-400 uppercase tracking-wide">
                                                            Discount
                                                        </p>
                                                        <p className="text-lg font-bold text-green-400 mt-1">
                                                            -<CurrencySymbol />
                                                            {transaction.discount_applied.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            Platform Fee
                                                        </p>
                                                        <p className="text-base font-bold text-gray-300 mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.platform_fee.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-gray-700 rounded p-3">
                                                        <p className="text-xs text-gray-400 uppercase tracking-wide">
                                                            GST
                                                        </p>
                                                        <p className="text-base font-bold text-gray-300 mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.gst_amount.toFixed(2)}
                                                        </p>
                                                    </div>
                                                    <div className="bg-blue-900/30 rounded p-3 border border-blue-700">
                                                        <p className="text-xs text-blue-400 uppercase tracking-wide">
                                                            Paid Total
                                                        </p>
                                                        <p className="text-lg font-bold text-blue-400 mt-1">
                                                            <CurrencySymbol />
                                                            {transaction.total_paid.toFixed(2)}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="bg-gray-700 rounded p-3">
                                                    <p className="text-xs text-gray-400 uppercase tracking-wide mb-1">
                                                        Payment Details
                                                    </p>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs text-gray-500">Method:</p>
                                                            <p className="text-sm font-semibold text-white capitalize">
                                                                {transaction.payment_method}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs text-gray-500">Payment ID:</p>
                                                            <p className="text-sm font-mono text-gray-300">
                                                                {transaction.payment_id}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Pagination would go here if implemented */}
                                </>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-400 text-lg">No coupon redemption transactions found</p>
                                    <Link
                                        href={route('coupons.index')}
                                        className="text-orange-500 hover:text-orange-400 mt-4 inline-block"
                                    >
                                        Browse Coupons →
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
