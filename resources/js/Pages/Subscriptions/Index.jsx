import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import CurrencySymbol from '@/Components/CurrencySymbol';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { useState, useEffect } from 'react';
import useRazorpay from '@/Hooks/useRazorpay';

export default function Index({ auth, plans, currentSubscription, primaryCampaignId, availableCampaigns, isLoggedIn }) {
    const currentPlanIndex = plans.findIndex(p => p.id === currentSubscription?.plan_id);
    const currentSortOrder = plans.find(p => p.id === currentSubscription?.plan_id)?.sort_order ?? -1;
    const { flash } = usePage().props;
    const [showCampaignModal, setShowCampaignModal] = useState(false);
    const [selectedCampaign, setSelectedCampaign] = useState(null);
    const [upgradingPlanId, setUpgradingPlanId] = useState(null);
    const [showSuccess, setShowSuccess] = useState(false);
    const [successDetails, setSuccessDetails] = useState(null);

    const { paymentStatus, resetState } = useRazorpay({
        user: auth.user,
    });

    useEffect(() => {
        if (flash?.needsCampaignSelection) {
            setShowCampaignModal(true);
        }
        if (flash?.success) {
            setShowSuccess(true);
            setSuccessDetails({ message: flash.success });
        }
    }, [flash?.needsCampaignSelection, flash?.success]);

    const handleUpgrade = async (plan) => {
        if (upgradingPlanId) return;

        // prevent upgrading/downgrading based on sort order
        if ((plan.sort_order ?? 0) <= currentSortOrder) return;

        // Free plan: standard Inertia POST (no payment needed)
        if (plan.price <= 0) {
            setUpgradingPlanId(plan.id);
            router.post(route('subscriptions.upgrade'), { plan_id: plan.id }, {
                onFinish: () => setUpgradingPlanId(null),
            });
            return;
        }

        // Paid plan: fetch order and open Razorpay popup
        setUpgradingPlanId(plan.id);
        try {
            const { data: result } = await axios.post(route('subscriptions.upgrade'), { plan_id: plan.id }, {
                headers: { 'Accept': 'application/json' },
            });

            const { order, transaction_id, plan_id } = result;

            const options = {
                key: order.key,
                amount: order.amount,
                currency: order.currency,
                name: 'Kutoot',
                description: `Upgrade to ${plan.name}`,
                order_id: order.id,
                handler: function (response) {
                    router.post(route('subscriptions.verify-payment', transaction_id), {
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        plan_id: plan_id,
                    }, {
                        onFinish: () => setUpgradingPlanId(null),
                    });
                },
                prefill: {
                    name: auth.user?.name || '',
                    email: auth.user?.email || '',
                },
                theme: { color: '#f08c10' },
                modal: {
                    ondismiss: () => setUpgradingPlanId(null),
                },
            };

            const rzp = new window.Razorpay(options);
            rzp.on('payment.failed', () => setUpgradingPlanId(null));
            rzp.open();
        } catch (error) {
            const errMsg = error.response?.data?.error || 'Payment initiation failed. Please try again.';
            console.error('Payment initiation failed', error);
            alert(errMsg);
            setUpgradingPlanId(null);
        }
    };

    const handleCampaignSelect = () => {
        if (!selectedCampaign) return;
        router.post(route('subscriptions.setPrimaryCampaign'), { campaign_id: selectedCampaign }, {
            onSuccess: () => setShowCampaignModal(false),
        });
    };

    const tierConfig = [
        { bg: 'from-lucky-50 to-lucky-100', accent: 'text-lucky-600', border: 'border-lucky-300', badge: 'bg-lucky-100 text-lucky-700', icon: '??', ring: 'ring-lucky-200' },
        { bg: 'from-ticket-50 to-ticket-100', accent: 'text-ticket-600', border: 'border-ticket-300', badge: 'bg-ticket-100 text-ticket-700', icon: '?', ring: 'ring-ticket-200', popular: true },
        { bg: 'from-yellow-50 to-amber-100', accent: 'text-amber-600', border: 'border-yellow-400', badge: 'bg-yellow-100 text-amber-700', icon: '??', ring: 'ring-yellow-200' },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">? Subscription Plans</h2>}
        >
            <Head title="Subscriptions" />

            <div className="py-6 sm:py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header text */}
                    <div className="text-center mb-8">
                        <p className="text-gray-500 text-sm max-w-lg mx-auto">
                            Choose the plan that fits your needs. Upgrade anytime to unlock more discounts, stamps, and rewards.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                        {plans.map((plan, index) => {
                            const tier = tierConfig[index % tierConfig.length];
                            const isCurrent = currentSubscription?.plan_id === plan.id;
                            const isUpgradable = isLoggedIn && !isCurrent && !plan.is_default && (plan.sort_order ?? 0) > currentSortOrder;
                            const isLowerTier = isLoggedIn && !isCurrent && !plan.is_default && (plan.sort_order ?? 0) <= currentSortOrder;
                            const isUpgrading = upgradingPlanId === plan.id;

                            return (
                                <div
                                    key={plan.id}
                                    className={`coupon-card overflow-visible transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl relative
                                        ${isCurrent ? `${tier.border} ring-2 ${tier.ring} shadow-lg` : ''}
                                        ${tier.popular && !isCurrent ? 'md:-translate-y-2' : ''}
                                    `}
                                >
                                    {/* Popular badge */}
                                    {tier.popular && !isCurrent && (
                                        <div className="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                                            <span className="bg-gradient-to-r from-ticket-500 to-ticket-600 text-white px-4 py-1 rounded-full text-xs font-bold shadow-md whitespace-nowrap">
                                                ?? BEST VALUE
                                            </span>
                                        </div>
                                    )}

                                    {/* Current badge */}
                                    {isCurrent && (
                                        <div className="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                                            <span className="golden-badge px-4 py-1 rounded-full text-xs whitespace-nowrap">? CURRENT PLAN</span>
                                        </div>
                                    )}

                                    {/* Plan header */}
                                    <div className={`p-5 sm:p-6 bg-gradient-to-br ${tier.bg} rounded-t-2xl text-center`}>
                                        <span className="text-4xl block mb-2">{tier.icon}</span>
                                        <h3 className="font-display text-xl sm:text-2xl text-gray-900 mb-1">{plan.name}</h3>
                                        <div className="mb-4">
                                            {plan.price > 0 ? (
                                                <span className={`text-3xl font-bold ${tier.accent}`}><CurrencySymbol />{plan.price.toFixed(0)}</span>
                                            ) : (
                                                <span className="text-lg font-medium text-gray-400">Free</span>
                                            )}
                                        </div>

                                        <div className="flex gap-2 sm:gap-3">
                                            <div className="flex-1 bg-white/80 backdrop-blur-sm rounded-xl p-2.5 sm:p-3 text-center border border-dashed border-lucky-200">
                                                <p className={`text-xl sm:text-2xl font-bold ${tier.accent}`}>{plan.stamps_on_purchase}</p>
                                                <p className="text-xs text-gray-500 font-medium">?? Bonus</p>
                                            </div>
                                            <div className="flex-1 bg-white/80 backdrop-blur-sm rounded-xl p-2.5 sm:p-3 text-center border border-dashed border-lucky-200">
                                                <p className={`text-xl sm:text-2xl font-bold ${tier.accent}`}>{plan.stamps_per_denomination}</p>
                                                <p className="text-xs text-gray-500 font-medium">Per <CurrencySymbol />{plan.stamp_denomination}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Perforation */}
                                    <div className="flex justify-center gap-1.5 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent">
                                        {[...Array(12)].map((_, i) => (
                                            <div key={i} className="w-1.5 h-1.5 rounded-full bg-lucky-200" />
                                        ))}
                                    </div>

                                    {/* Plan features */}
                                    <div className="p-5 sm:p-6">
                                        <ul className="text-sm text-gray-600 space-y-3 mb-6">
                                            <FeatureRow icon="???" label="Max Discounted Bills" value={plan.max_discounted_bills} />
                                            <FeatureRow icon="??" label="Max Redeemable" value={<><CurrencySymbol />{parseFloat(plan.max_redeemable_amount).toFixed(0)}</>} />
                                            <FeatureRow icon="?" label="Validity" value={plan.duration_days ? `${plan.duration_days} days` : '8'} last />
                                        </ul>

                                        {/* Action button */}
                                        {isCurrent ? (
                                            <div>
                                                <button disabled className="w-full golden-badge py-2.5 px-4 rounded-full cursor-default text-sm flex items-center justify-center gap-1.5">
                                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg>
                                                    Current Plan
                                                </button>
                                                {currentSubscription.expires_at && (
                                                    <p className="text-center text-xs text-gray-400 mt-2 bg-gray-50 rounded-full py-1.5 px-3">
                                                        ? Expires: <span className="font-bold">{currentSubscription.expires_at}</span>
                                                    </p>
                                                )}
                                            </div>
                                        ) : plan.is_default ? (
                                            <p className="w-full text-center text-xs text-gray-400 py-2.5 bg-gray-50 rounded-full">Auto-assigned on signup</p>
                                        ) : !isLoggedIn ? (
                                            <Link
                                                href={route('login')}
                                                className="w-full block text-center lucky-gradient text-white font-bold py-2.5 px-4 rounded-full transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm"
                                            >
                                                ?? Login to Upgrade
                                            </Link>
                                        ) : isUpgradable ? (
                                            <button
                                                onClick={() => handleUpgrade(plan)}
                                                disabled={isUpgrading}
                                                className="w-full lucky-gradient text-white font-bold py-2.5 px-4 rounded-full transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                            >
                                                {isUpgrading ? (
                                                    <>
                                                        <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                                                        Processing...
                                                    </>
                                                ) : (
                                                    '?? Upgrade'
                                                )}
                                            </button>
                                        ) : (
                                            <p className="w-full text-center text-xs text-gray-400 py-2.5 bg-gray-50 rounded-full">Lower tier</p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Guest CTA */}
                    {!isLoggedIn && (
                        <div className="mt-10 text-center">
                            <div className="inline-block coupon-card p-6 sm:p-8">
                                <span className="text-3xl block mb-2">??</span>
                                <h3 className="font-display text-lg text-gray-900 mb-1">Sign up to unlock all plans</h3>
                                <p className="text-sm text-gray-500 mb-4 max-w-sm">
                                    Create a free account to upgrade your plan, earn stamps, and access exclusive discounts.
                                </p>
                                <Link
                                    href={route('login')}
                                    className="inline-flex items-center gap-2 lucky-gradient text-white font-bold py-2.5 px-6 rounded-full shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all text-sm"
                                >
                                    Get Started Free
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Campaign Selection Modal */}
            {showCampaignModal && availableCampaigns.length > 0 && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div className="coupon-card w-full max-w-md p-6">
                        <h3 className="font-display text-xl text-gray-900 mb-2 flex items-center gap-2">
                            <span className="text-2xl">??</span> Select Your Campaign
                        </h3>
                        <p className="text-sm text-gray-500 mb-5">Choose the campaign you'd like to collect stamps for:</p>

                        <div className="space-y-2 mb-6 max-h-60 overflow-y-auto">
                            {availableCampaigns.map(campaign => (
                                <label
                                    key={campaign.id}
                                    className={`flex items-center gap-3 p-3 rounded-xl border-2 border-dashed cursor-pointer transition-all ${
                                        selectedCampaign === campaign.id
                                            ? 'border-lucky-400 bg-lucky-50'
                                            : 'border-gray-200 hover:border-lucky-200 hover:bg-lucky-50/30'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="campaign"
                                        value={campaign.id}
                                        checked={selectedCampaign === campaign.id}
                                        onChange={() => setSelectedCampaign(campaign.id)}
                                        className="text-lucky-500 focus:ring-lucky-400"
                                    />
                                    <span className="font-medium text-gray-900">{campaign.reward_name}</span>
                                </label>
                            ))}
                        </div>

                        <button
                            onClick={handleCampaignSelect}
                            disabled={!selectedCampaign}
                            className={`w-full font-bold py-2.5 px-4 rounded-full transition-all text-sm ${
                                selectedCampaign
                                    ? 'lucky-gradient text-white shadow-md hover:shadow-lg transform hover:-translate-y-0.5'
                                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                            }`}
                        >
                            ? Confirm Campaign
                        </button>
                    </div>
                </div>
            )}

            {/* Success Modal */}
            <ConfirmationModal
                show={showSuccess}
                onClose={() => { setShowSuccess(false); setSuccessDetails(null); }}
                title="Plan Upgraded!"
                message={successDetails?.message}
            />
        </AuthenticatedLayout>
    );
}

function FeatureRow({ icon, label, value, last = false }) {
    return (
        <li className={`flex justify-between py-1.5 ${!last ? 'border-b border-dashed border-lucky-100' : ''}`}>
            <span className="text-gray-500 flex items-center gap-1.5">
                <span>{icon}</span> {label}
            </span>
            <span className="font-bold text-gray-900">{value}</span>
        </li>
    );
}
