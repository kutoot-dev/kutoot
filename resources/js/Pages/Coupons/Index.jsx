import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import CurrencySymbol from '@/Components/CurrencySymbol';
import EmptyState from '@/Components/EmptyState';
import PaymentBreakdown from '@/Components/PaymentBreakdown';
import ConfirmationModal from '@/Components/ConfirmationModal';

export default function Index({ auth, coupons, locations, planName, stampsPerHundred, primaryCampaign, availableCampaigns, remainingRedeemAmount, maxRedeemableAmount }) {
    const { platform_fee, gst_rate, platform_fee_type, flash } = usePage().props;

    const [confirmingRedemption, setConfirmingRedemption] = useState(false);
    const [selectedCoupon, setSelectedCoupon] = useState(null);
    const [modalStep, setModalStep] = useState(1); // 1=location, 2=amount, 3=review
    const [isProcessing, setIsProcessing] = useState(false);
    const [showSuccess, setShowSuccess] = useState(false);
    const [successData, setSuccessData] = useState(null);

    const { data, setData, processing, errors, reset } = useForm({
        merchant_location_id: '',
        amount: '',
        campaign_id: primaryCampaign?.id || '',
    });

    const selectedLocationName = locations.find(l => String(l.id) === String(data.merchant_location_id))?.name;

    const confirmRedemption = (coupon) => {
        setSelectedCoupon(coupon);
        setConfirmingRedemption(true);
        setModalStep(1);
        if (coupon.merchant_location_id) {
            setData('merchant_location_id', coupon.merchant_location_id);
            setModalStep(2); // skip location selection if pre-set
        } else {
            setData('merchant_location_id', '');
        }
    };

    const closeModal = () => {
        setConfirmingRedemption(false);
        setSelectedCoupon(null);
        setModalStep(1);
        setIsProcessing(false);
        reset();
    };

    const handlePayment = async (e) => {
        e.preventDefault();

        const couponId = selectedCoupon?.id;
        const formData = { ...data };
        if (!couponId) return;

        setIsProcessing(true);

        // Fetch order via JSON, then open Razorpay popup
        try {
            const response = await fetch(route('coupons.redeem', couponId), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.ok) {
                closeModal();
                const { order, transaction_id } = result;
                const options = {
                    key: order.key,
                    amount: order.amount,
                    currency: order.currency,
                    name: 'Kutoot',
                    description: `Payment for ${selectedCoupon.title}`,
                    order_id: order.id,
                    handler: function (response) {
                        router.post(route('coupons.verify-payment', transaction_id), {
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature,
                        }, {
                            onSuccess: () => {
                                setShowSuccess(true);
                                setSuccessData({ stamps: breakdown.estimatedStamps });
                            },
                        });
                    },
                    prefill: {
                        name: auth.user.name,
                        email: auth.user.email,
                    },
                    theme: { color: '#f08c10' },
                    modal: {
                        ondismiss: () => setIsProcessing(false),
                    },
                };

                const rzp = new window.Razorpay(options);
                rzp.on('payment.failed', () => setIsProcessing(false));
                rzp.open();
            } else {
                alert(result.error || 'Something went wrong');
                setIsProcessing(false);
            }
        } catch (error) {
            console.error('Payment initiation failed', error);
            alert('Payment initiation failed. Please try again.');
            setIsProcessing(false);
        }
    };

    const calculateBreakdown = () => {
        const billAmount = parseFloat(data.amount) || 0;
        let discount = 0;
        if (selectedCoupon) {
            if (selectedCoupon.discount_type === 'percentage') {
                discount = (billAmount * parseFloat(selectedCoupon.discount_value)) / 100;
            } else {
                discount = parseFloat(selectedCoupon.discount_value) || 0;
            }
            if (selectedCoupon.max_discount_amount) {
                discount = Math.min(discount, parseFloat(selectedCoupon.max_discount_amount));
            }
        }
        discount = Math.min(discount, billAmount);
        const finalBill = Math.max(0, billAmount - discount);
        const fee = parseFloat(platform_fee);
        const feeAmount = platform_fee_type === 'percentage' ? (billAmount * fee / 100) : fee;
        const gst = (feeAmount * gst_rate) / 100;
        const total = finalBill + feeAmount + gst;
        const estimatedStamps = Math.floor(billAmount / 100) * stampsPerHundred;

        return { billAmount, discount, finalBill, feeAmount, gst, total, estimatedStamps };
    };

    const breakdown = calculateBreakdown();

    // Remaining balance progress
    const balancePercent = maxRedeemableAmount > 0 ? Math.max(0, Math.min(100, (remainingRedeemAmount / maxRedeemableAmount) * 100)) : 0;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">🎫 My Coupons ({planName})</h2>}
        >
            <Head title="Coupons" />

            <div className="py-6 sm:py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                    {/* Remaining Balance Bar */}
                    {auth.user && remainingRedeemAmount !== undefined && (
                        <div className="coupon-card p-4 sm:p-5 mb-6">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                        <span>💰</span> Remaining Redeemable Balance
                                    </p>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        <CurrencySymbol />{parseFloat(remainingRedeemAmount).toFixed(2)} of <CurrencySymbol />{parseFloat(maxRedeemableAmount).toFixed(2)}
                                    </p>
                                </div>
                                <div className="w-full sm:w-48">
                                    <div className="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                        <div
                                            className={`h-full rounded-full transition-all duration-1000 ${balancePercent > 50 ? 'bg-green-500' : balancePercent > 20 ? 'bg-amber-500' : 'bg-red-500'}`}
                                            style={{ width: `${balancePercent}%` }}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Coupon Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        {coupons.data.length > 0 ? (
                            coupons.data.map((coupon) => {
                                const isEligible = coupon.is_eligible !== false;

                                return (
                                <div key={coupon.id} className={`coupon-card overflow-hidden flex flex-col transition-all duration-300 transform hover:-translate-y-1 group relative ${isEligible ? 'hover:shadow-xl' : 'opacity-80 grayscale-[20%]'}`}>
                                    {/* Locked overlay badge */}
                                    {!isEligible && (
                                        <div className="absolute top-3 left-3 z-10 bg-gray-800/80 text-white px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                                            🔒 Locked
                                        </div>
                                    )}

                                    <div className="p-5 sm:p-6 flex-grow">
                                        {/* Merchant badge */}
                                        <div className="flex items-center justify-between mb-3">
                                            <span className="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-lucky-700 bg-lucky-100 rounded-full border border-lucky-200">
                                                {coupon.merchant_location ? (
                                                    <>📍 {coupon.merchant_location.branch_name}</>
                                                ) : (
                                                    <>🌐 All Stores</>
                                                )}
                                            </span>
                                            {/* Discount tag */}
                                            <span className="inline-flex items-center px-2.5 py-1 text-xs font-bold text-white bg-gradient-to-r from-green-500 to-emerald-500 rounded-full shadow-sm">
                                                {coupon.discount_type === 'percentage' ? `${coupon.discount_value}% OFF` : <><CurrencySymbol />{coupon.discount_value} OFF</>}
                                            </span>
                                        </div>

                                        <h3 className="font-display text-lg text-gray-900 mb-1 group-hover:text-lucky-700 transition-colors">{coupon.title}</h3>
                                        {coupon.description && (
                                            <p className="text-gray-500 text-sm mb-4 line-clamp-2">{coupon.description}</p>
                                        )}

                                        {/* Info grid */}
                                        <div className="bg-gradient-to-br from-lucky-50/50 to-ticket-50/50 p-3 rounded-xl text-sm border border-lucky-100 space-y-2">
                                            <div className="flex justify-between items-center">
                                                <span className="text-gray-500 text-xs">Code</span>
                                                <span className="font-mono font-bold text-lucky-700 bg-lucky-100 px-2 py-0.5 rounded text-xs">{coupon.code}</span>
                                            </div>
                                            {(coupon.max_discount_amount || coupon.discount_type === 'fixed') && (
                                                <div className="flex justify-between items-center pt-1 border-t border-dashed border-lucky-100">
                                                    <span className="text-gray-500 text-xs">Max Savings</span>
                                                    <span className="font-bold text-green-600 text-xs">
                                                        <CurrencySymbol />{parseFloat(coupon.max_discount_amount || coupon.discount_value).toFixed(2)}
                                                    </span>
                                                </div>
                                            )}
                                            {coupon.min_order_value > 0 && (
                                                <div className="flex justify-between items-center pt-1 border-t border-dashed border-lucky-100">
                                                    <span className="text-gray-500 text-xs">Min Order</span>
                                                    <span className="font-bold text-gray-700 text-xs">
                                                        <CurrencySymbol />{parseFloat(coupon.min_order_value).toFixed(0)}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Perforation */}
                                    <div className="flex justify-center gap-1.5 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent">
                                        {[...Array(12)].map((_, i) => (
                                            <div key={i} className="w-1.5 h-1.5 rounded-full bg-lucky-200" />
                                        ))}
                                    </div>

                                    {/* Action */}
                                    <div className="bg-gradient-to-br from-lucky-50 to-ticket-50 px-5 py-3 sm:px-6 sm:py-4">
                                        {!isEligible ? (
                                            <Link
                                                href={route('subscriptions.index')}
                                                className="inline-flex items-center w-full justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-gray-700 to-gray-800 border border-transparent rounded-full font-bold text-xs text-white uppercase tracking-widest hover:shadow-lg transition-all transform hover:-translate-y-0.5"
                                            >
                                                🚀 Upgrade to {coupon.required_plan?.name || 'Unlock'}
                                            </Link>
                                        ) : auth.user ? (
                                            <PrimaryButton className="w-full justify-center" onClick={() => confirmRedemption(coupon)}>
                                                🎟️ Redeem Now
                                            </PrimaryButton>
                                        ) : (
                                            <Link
                                                href={route('login')}
                                                className="inline-flex items-center w-full justify-center gap-2 px-5 py-2.5 lucky-gradient border border-transparent rounded-full font-bold text-xs text-white uppercase tracking-widest hover:shadow-lg transition-all"
                                            >
                                                🔑 Login to Redeem
                                            </Link>
                                        )}
                                    </div>
                                </div>
                                );
                            })
                        ) : (
                            <div className="col-span-full">
                                <div className="coupon-card">
                                    <EmptyState
                                        icon="🎭"
                                        title="No coupons available"
                                        description="Check back later or upgrade your plan to unlock more coupons and exclusive discounts."
                                        actionLabel="Upgrade Plan"
                                        actionHref={route('subscriptions.index')}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Redemption Modal */}
            <Modal show={confirmingRedemption} onClose={closeModal} maxWidth="lg">
                <form onSubmit={handlePayment} className="p-5 sm:p-6">
                    {/* Modal header */}
                    <div className="flex items-center gap-3 mb-1">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-lucky-100 to-lucky-200 flex items-center justify-center flex-shrink-0">
                            <span className="text-lg">🎟️</span>
                        </div>
                        <div>
                            <h2 className="font-display text-lg text-gray-900">{selectedCoupon?.title}</h2>
                            {selectedLocationName && (
                                <p className="text-xs text-lucky-600">at {selectedLocationName}</p>
                            )}
                        </div>
                    </div>

                    {/* Step indicator */}
                    <div className="flex items-center gap-2 my-5">
                        <StepDot step={1} current={modalStep} label="Location" />
                        <div className="flex-1 h-0.5 bg-gray-200 rounded"><div className={`h-full rounded transition-all duration-300 ${modalStep >= 2 ? 'bg-lucky-400 w-full' : 'w-0'}`} /></div>
                        <StepDot step={2} current={modalStep} label="Bill" />
                        <div className="flex-1 h-0.5 bg-gray-200 rounded"><div className={`h-full rounded transition-all duration-300 ${modalStep >= 3 ? 'bg-lucky-400 w-full' : 'w-0'}`} /></div>
                        <StepDot step={3} current={modalStep} label="Pay" />
                    </div>

                    {/* Step 1: Location */}
                    {modalStep === 1 && (
                        <div className="space-y-4">
                            <InputLabel htmlFor="merchant_location_id" value="Select Store Location" />
                            <select
                                id="merchant_location_id"
                                value={data.merchant_location_id}
                                onChange={(e) => setData('merchant_location_id', e.target.value)}
                                className="block w-full border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-xl shadow-sm text-sm"
                                required
                            >
                                <option value="">Choose a location...</option>
                                {locations.map((loc) => (
                                    <option key={loc.id} value={loc.id}>{loc.name}</option>
                                ))}
                            </select>
                            <InputError message={errors.merchant_location_id} className="mt-1" />
                            <div className="flex justify-end">
                                <PrimaryButton
                                    type="button"
                                    disabled={!data.merchant_location_id}
                                    onClick={() => setModalStep(2)}
                                >
                                    Next →
                                </PrimaryButton>
                            </div>
                        </div>
                    )}

                    {/* Step 2: Bill Amount */}
                    {modalStep === 2 && (
                        <div className="space-y-4">
                            <InputLabel htmlFor="amount" value={<span>Enter Bill Amount (<CurrencySymbol />)</span>} />
                            <TextInput
                                id="amount"
                                type="number"
                                step="0.01"
                                min="1"
                                value={data.amount}
                                onChange={(e) => setData('amount', e.target.value)}
                                className="block w-full text-lg"
                                placeholder="e.g. 500.00"
                                autoFocus
                                required
                            />
                            <InputError message={errors.amount} className="mt-1" />
                            <div className="flex justify-between">
                                <SecondaryButton type="button" onClick={() => setModalStep(1)}>← Back</SecondaryButton>
                                <PrimaryButton
                                    type="button"
                                    disabled={!data.amount || parseFloat(data.amount) <= 0}
                                    onClick={() => setModalStep(3)}
                                >
                                    Review →
                                </PrimaryButton>
                            </div>
                        </div>
                    )}

                    {/* Step 3: Review & Pay */}
                    {modalStep === 3 && (
                        <div className="space-y-4">
                            {/* Summary */}
                            <div className="bg-gray-50 rounded-xl p-3 text-sm space-y-1">
                                <div className="flex justify-between text-gray-500">
                                    <span>📍 Location</span>
                                    <span className="font-medium text-gray-900">{selectedLocationName}</span>
                                </div>
                                <div className="flex justify-between text-gray-500">
                                    <span>🎫 Coupon</span>
                                    <span className="font-medium text-gray-900">{selectedCoupon?.code}</span>
                                </div>
                            </div>

                            {/* Breakdown */}
                            <PaymentBreakdown
                                billAmount={breakdown.billAmount}
                                discount={breakdown.discount}
                                finalBill={breakdown.finalBill}
                                platformFee={breakdown.feeAmount}
                                gst={breakdown.gst}
                                gstRate={gst_rate}
                                total={breakdown.total}
                            />

                            {/* Stamps preview */}
                            {breakdown.estimatedStamps > 0 && (
                                <div className="bg-green-50 p-3 rounded-xl border border-green-200 flex items-center gap-3">
                                    <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span className="text-green-700 font-bold text-lg">{breakdown.estimatedStamps}</span>
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-green-800">
                                            You'll earn {breakdown.estimatedStamps} stamp{breakdown.estimatedStamps !== 1 ? 's' : ''}
                                        </p>
                                        <p className="text-xs text-green-600">
                                            {stampsPerHundred} stamp{stampsPerHundred !== 1 ? 's' : ''} per <CurrencySymbol />100
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Campaign */}
                            {primaryCampaign ? (
                                <div className="bg-amber-50 p-3 rounded-xl border border-amber-200">
                                    <p className="text-sm text-amber-800">
                                        <span className="font-semibold">Stamps go to:</span> {primaryCampaign.reward_name}
                                    </p>
                                </div>
                            ) : availableCampaigns?.length > 0 ? (
                                <div>
                                    <InputLabel htmlFor="campaign_id" value="Select Campaign for Stamps" />
                                    <select
                                        id="campaign_id"
                                        value={data.campaign_id}
                                        onChange={(e) => setData('campaign_id', e.target.value)}
                                        className="mt-1 block w-full border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-xl shadow-sm text-sm"
                                        required
                                    >
                                        <option value="">Choose a campaign</option>
                                        {availableCampaigns.map((c) => (
                                            <option key={c.id} value={c.id}>{c.reward_name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.campaign_id} className="mt-1" />
                                </div>
                            ) : null}

                            {/* Actions */}
                            <div className="flex justify-between pt-2">
                                <SecondaryButton type="button" onClick={() => setModalStep(2)}>← Back</SecondaryButton>
                                <PrimaryButton disabled={isProcessing || processing} className="min-w-[140px] justify-center">
                                    {isProcessing ? (
                                        <span className="flex items-center gap-2">
                                            <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                                        Processing...
                                        </span>
                                    ) : (
                                        <>Pay <CurrencySymbol />{breakdown.total.toFixed(2)}</>
                                    )}
                                </PrimaryButton>
                            </div>
                        </div>
                    )}
                </form>
            </Modal>

            {/* Success Modal */}
            <ConfirmationModal
                show={showSuccess}
                onClose={() => { setShowSuccess(false); setSuccessData(null); }}
                title="Coupon Redeemed!"
                message="Your coupon has been successfully redeemed."
                stampsEarned={successData?.stamps || 0}
            />
        </AuthenticatedLayout>
    );
}

function StepDot({ step, current, label }) {
    const isActive = current >= step;
    return (
        <div className="flex flex-col items-center gap-1">
            <div className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 ${
                isActive ? 'lucky-gradient text-white shadow-md' : 'bg-gray-200 text-gray-500'
            }`}>
                {current > step ? (
                    <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg>
                ) : step}
            </div>
            <span className={`text-xs font-medium ${isActive ? 'text-lucky-600' : 'text-gray-400'}`}>{label}</span>
        </div>
    );
}
