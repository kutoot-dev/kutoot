import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import CurrencySymbol from '@/Components/CurrencySymbol';


export default function Index({ auth, coupons, locations, planName, stampsPerHundred, primaryCampaign, availableCampaigns }) {
    const { platform_fee, gst_rate, platform_fee_type } = usePage().props;

    const [confirmingRedemption, setConfirmingRedemption] = useState(false);
    const [selectedCoupon, setSelectedCoupon] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        merchant_location_id: '',
        amount: '',
        campaign_id: primaryCampaign?.id || '',
    });

    const selectedLocationName = locations.find(l => String(l.id) === String(data.merchant_location_id))?.name;

    useEffect(() => {
        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        document.body.appendChild(script);

        return () => {
            document.body.removeChild(script);
        }
    }, []);


    const confirmRedemption = (coupon) => {
        setSelectedCoupon(coupon);
        setConfirmingRedemption(true);
        if (coupon.merchant_location_id) {
            setData('merchant_location_id', coupon.merchant_location_id);
        } else {
            setData('merchant_location_id', '');
        }
    };

    const closeModal = () => {
        setConfirmingRedemption(false);
        setSelectedCoupon(null);
        reset();
    };

    const redeemCoupon = (e) => {
        e.preventDefault();

        post(route('coupons.redeem', selectedCoupon.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.order || page.props.order) {
                    // Logic for JSON response if handled by Inertia as visit
                    // But we used response()->json() in controller which isn't ideal for Inertia partials
                    // Let's adjust controller to use Inertia::render or handle it here
                }
            },
            onError: (err) => console.error(err),
            // Custom handler because we are returning JSON from a POST
            onFinish: () => { },
        });
    };

    // Re-implementing redeemCoupon to handle the JSON response manually since we're using a payment gateway
    const initiatePayment = async (e) => {
        e.preventDefault();

        const couponId = selectedCoupon?.id;
        const formData = { ...data };

        if (!couponId) return;

        try {
            const response = await fetch(route('coupons.redeem', couponId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.ok) {
                closeModal();
                handleRazorpayPayment(result.order, result.transaction_id);
            } else {
                alert(result.error || 'Something went wrong');
            }
        } catch (error) {
            console.error('Payment initiation failed', error);
            alert('Payment initiation failed. Please try again.');
        }
    };

    const handleRazorpayPayment = (order, transactionId) => {
        const options = {
            key: order.key,
            amount: order.amount,
            currency: order.currency,
            name: "Kutoot",
            description: `Payment for ${selectedCoupon.title}`,
            order_id: order.id,
            handler: function (response) {
                router.post(route('coupons.verify-payment', transactionId), {
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                });
            },
            prefill: {
                name: auth.user.name,
                email: auth.user.email,
            },
            theme: {
                color: "#f08c10",
            },
        };

        const rzp = new window.Razorpay(options);
        rzp.open();
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
        const finalBill = Math.max(0, billAmount - discount);
        const fee = parseFloat(platform_fee);
        // handle percentage fee if needed (frontend sync)
        const feeAmount = platform_fee_type === 'percentage' ? (billAmount * fee / 100) : fee;
        const gst = (feeAmount * gst_rate) / 100;
        const total = finalBill + feeAmount + gst;
        const estimatedStamps = Math.floor(billAmount / 100) * stampsPerHundred;

        return { billAmount, discount, finalBill, feeAmount, gst, total, estimatedStamps };
    };

    const breakdown = calculateBreakdown();


    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="text-xl font-bold leading-tight text-white flex items-center gap-2">🎫 My Coupons ({planName})</h2>}
        >
            <Head title="Coupons" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {coupons.data.length > 0 ? (
                            coupons.data.map((coupon) => (
                                <div key={coupon.id} className="coupon-card overflow-hidden flex flex-col hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                    <div className="p-6 flex-grow">
                                        <span className="inline-block px-3 py-1 text-xs font-bold text-lucky-700 bg-lucky-100 rounded-full mb-3 border border-lucky-200">
                                            {coupon.merchant_location ? coupon.merchant_location.branch_name : '🌐 Global Coupon'}
                                        </span>
                                        <h3 className="font-display text-lg text-gray-900 mb-1">{coupon.title}</h3>
                                        <p className="text-gray-500 text-sm mb-4">{coupon.description}</p>

                                        <div className="bg-gradient-to-br from-lucky-50 to-ticket-50 p-4 rounded-xl text-sm text-gray-700 border border-dashed border-lucky-200">
                                            <div className="flex justify-between mb-2 pb-2 border-b border-dashed border-lucky-100">
                                                <span className="text-gray-500">Code:</span>
                                                <span className="font-mono font-bold text-lucky-700 bg-lucky-100 px-2 py-0.5 rounded">{coupon.code}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Value:</span>
                                                <span className="font-bold text-ticket-600">
                                                    {coupon.discount_type === 'percentage' ? `${coupon.discount_value}% Off` : <><CurrencySymbol />{coupon.discount_value} Off</>}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    {/* Ticket perforation */}
                                    <div className="flex justify-center gap-2 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent">
                                        {[...Array(10)].map((_, i) => (
                                            <div key={i} className="w-2 h-2 rounded-full bg-lucky-200" />
                                        ))}
                                    </div>
                                    <div className="bg-gradient-to-br from-lucky-50 to-ticket-50 px-6 py-4">
                                        {auth.user ? (
                                            <PrimaryButton
                                                className="w-full justify-center"
                                                onClick={() => confirmRedemption(coupon)}
                                            >
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
                            ))
                        ) : (
                            <div className="col-span-full text-center py-16">
                                <span className="text-5xl mb-4 block">🎭</span>
                                <h3 className="font-display text-lg text-gray-900">No coupons available</h3>
                                <p className="mt-1 text-sm text-gray-500">Upgrade your plan to unlock more rewards.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <Modal show={confirmingRedemption} onClose={closeModal}>
                <form onSubmit={initiatePayment} className="p-6">
                    <h2 className="font-display text-lg text-gray-900 flex items-center gap-2">
                        <span>🎟️</span> Redeem: {selectedCoupon?.title}
                        {selectedLocationName && (
                            <span className="block text-sm font-normal text-lucky-600 mt-1">
                                at {selectedLocationName}
                            </span>
                        )}
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        To redeem this coupon, please select the store location and enter the bill amount.
                    </p>

                    <div className="mt-6">
                        <InputLabel htmlFor="merchant_location_id" value="Store Location" />

                        <select
                            id="merchant_location_id"
                            name="merchant_location_id"
                            value={data.merchant_location_id}
                            onChange={(e) => setData('merchant_location_id', e.target.value)}
                            className="mt-1 block w-full border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-lg shadow-sm"
                            required
                        >
                            <option value="">Select a location</option>
                            {locations.map((loc) => (
                                <option key={loc.id} value={loc.id}>
                                    {loc.name}
                                </option>
                            ))}
                        </select>

                        <InputError message={errors.merchant_location_id} className="mt-2" />
                    </div>

                    <div className="mt-6">
                        <InputLabel htmlFor="amount" value={<span>Bill Amount (<CurrencySymbol />)</span>} />


                        <TextInput
                            id="amount"
                            type="number"
                            step="0.01"
                            name="amount"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="0.00"
                            required
                        />
                        <InputError message={errors.amount} className="mt-2" />
                    </div>

                    <div className="mt-6 bg-gradient-to-br from-lucky-50 to-ticket-50 p-4 rounded-xl border-2 border-dashed border-lucky-200">
                        <div className="flex justify-between text-sm text-gray-700 mb-1">
                            <span>Total Bill:</span>
                            <span className="font-bold"><CurrencySymbol />{breakdown.billAmount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-700 mb-1">
                            <span>Discount Applied:</span>
                            <span className="font-bold text-green-600">- <CurrencySymbol />{breakdown.discount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm font-bold text-lucky-700 mb-1 pt-1 border-t border-dashed border-lucky-200">
                            <span>Bill after Discount:</span>
                            <span><CurrencySymbol />{breakdown.finalBill.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-700 mb-1">
                            <span>Platform Fee:</span>
                            <span><CurrencySymbol />{breakdown.feeAmount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-sm text-gray-700 mb-2">
                            <span>GST ({gst_rate}%):</span>
                            <span><CurrencySymbol />{breakdown.gst.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between font-bold text-lucky-800 pt-2 border-t-2 border-dashed border-lucky-300">
                            <span>💰 Total to Pay:</span>
                            <span><CurrencySymbol />{breakdown.total.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Stamps Earned Preview */}
                    {breakdown.estimatedStamps > 0 && (
                        <div className="mt-4 bg-green-50 p-3 rounded-lg border border-green-200 flex items-center gap-3">
                            <div className="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <span className="text-green-700 font-bold text-lg">{breakdown.estimatedStamps}</span>
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-green-800">
                                    You'll earn {breakdown.estimatedStamps} stamp{breakdown.estimatedStamps !== 1 ? 's' : ''}
                                </p>
                                <p className="text-xs text-green-600">
                                    {stampsPerHundred} stamp{stampsPerHundred !== 1 ? 's' : ''} per <CurrencySymbol />100 bill
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Primary Campaign Note / Selector */}
                    {primaryCampaign ? (
                        <div className="mt-4 bg-amber-50 p-3 rounded-lg border border-amber-200">
                            <p className="text-sm text-amber-800">
                                <span className="font-semibold">Stamps will be added to:</span>{' '}
                                {primaryCampaign.reward_name}
                            </p>
                        </div>
                    ) : availableCampaigns?.length > 0 ? (
                        <div className="mt-4">
                            <InputLabel htmlFor="campaign_id" value="Select Campaign for Stamps" />
                            <select
                                id="campaign_id"
                                name="campaign_id"
                                value={data.campaign_id}
                                onChange={(e) => setData('campaign_id', e.target.value)}
                                className="mt-1 block w-full border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-lg shadow-sm"
                                required
                            >
                                <option value="">Choose a campaign</option>
                                {availableCampaigns.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.reward_name}
                                    </option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-gray-500">
                                No primary campaign set. Select which campaign should receive your stamps.
                            </p>
                            <InputError message={errors.campaign_id} className="mt-1" />
                        </div>
                    ) : null}

                    <div className="mt-6 flex justify-end">

                        <SecondaryButton onClick={closeModal}>Cancel</SecondaryButton>
                        <PrimaryButton className="ms-3" disabled={processing}>
                            Pay <CurrencySymbol />{breakdown.total.toFixed(2)} & Redeem
                        </PrimaryButton>
                    </div>


                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
