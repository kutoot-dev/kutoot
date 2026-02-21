import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import CurrencySymbol from '@/Components/CurrencySymbol';
import EmptyState from '@/Components/EmptyState';
import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';

export default function Dashboard({ auth, user, plan, primaryCampaign, stats, stamps: initialStamps }) {
    const allStatsZero = stats.stamps_count === 0 && stats.total_coupons_used === 0 && stats.total_discount_redeemed === 0;
    const [stamps, setStamps] = useState(initialStamps);
    const [editingStamp, setEditingStamp] = useState(null);
    const [slotValues, setSlotValues] = useState([]);
    const [editError, setEditError] = useState('');
    const [editSubmitting, setEditSubmitting] = useState(false);

    const openEditModal = useCallback((stamp) => {
        const config = stamp.stamp_config;
        if (!config) return;
        setEditingStamp(stamp);
        setSlotValues(Array(config.slots).fill(config.min));
        setEditError('');
    }, []);

    const closeEditModal = useCallback(() => {
        setEditingStamp(null);
        setSlotValues([]);
        setEditError('');
    }, []);

    const handleSlotChange = useCallback((index, value) => {
        setSlotValues(prev => {
            const next = [...prev];
            next[index] = parseInt(value) || 0;
            return next;
        });
    }, []);

    const submitStampEdit = useCallback(async () => {
        if (!editingStamp) return;
        setEditSubmitting(true);
        setEditError('');
        try {
            const response = await axios.patch(`/api/stamps/${editingStamp.id}/code`, {
                slot_values: slotValues,
            });
            setStamps(prev => prev.map(s =>
                s.id === editingStamp.id ? { ...s, code: response.data.stamp.code, is_editable: true } : s
            ));
            closeEditModal();
        } catch (err) {
            setEditError(err.response?.data?.message || 'Failed to update stamp code.');
        } finally {
            setEditSubmitting(false);
        }
    }, [editingStamp, slotValues, closeEditModal]);

    const previewCode = editingStamp?.stamp_config ? (() => {
        const config = editingStamp.stamp_config;
        const digits = String(config.max).length;
        const code = editingStamp.code.split('-')[0] || 'CODE';
        const paddedSlots = slotValues.map(v => String(v).padStart(digits, '0'));
        return code + '-' + paddedSlots.join('-');
    })() : '';

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
                                    <ProfileRow icon="🏆" label="Campaign" value={primaryCampaign} valueClass="text-lucky-600" last />
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


                    {/* Stamps */}
                    <div className="coupon-card overflow-hidden">
                        <div className="p-5 sm:p-6 pb-0 sm:pb-0">
                            <h3 className="font-display text-lg text-gray-900 mb-4 flex items-center gap-2">
                                <span className="text-xl">🎫</span> My Stamps
                            </h3>
                        </div>
                        {stamps.length > 0 ? (
                            <>
                                {/* Desktop table */}
                                <div className="hidden md:block overflow-x-auto px-5 sm:px-6 pb-5 sm:pb-6">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b-2 border-dashed border-lucky-200 text-left text-lucky-600">
                                                <th className="pb-2 font-bold">Code</th>
                                                <th className="pb-2 font-bold">Source</th>
                                                <th className="pb-2 font-bold">Campaign</th>
                                                <th className="pb-2 font-bold text-right">Bill Amount</th>
                                                <th className="pb-2 font-bold text-right">Earned</th>
                                                <th className="pb-2 font-bold text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-dashed divide-lucky-100">
                                            {stamps.map(s => (
                                                <tr key={s.id} className="hover:bg-lucky-50/50 transition-colors">
                                                    <td className="py-2.5">
                                                        <span className="font-mono text-xs bg-lucky-100 text-lucky-700 px-2 py-0.5 rounded">{s.code}</span>
                                                    </td>
                                                    <td className="py-2.5">
                                                        <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${
                                                            s.source === 'Plan Purchase'
                                                                ? 'bg-ticket-100 text-ticket-700'
                                                                : s.source === 'Coupon Redemption'
                                                                    ? 'bg-green-100 text-green-700'
                                                                    : 'bg-lucky-100 text-lucky-700'
                                                        }`}>
                                                            {s.source === 'Plan Purchase' ? '⭐' : s.source === 'Coupon Redemption' ? '🎟️' : '🧾'} {s.source}
                                                        </span>
                                                    </td>
                                                    <td className="py-2.5 text-gray-700 font-medium">{s.campaign_name ?? '—'}</td>
                                                    <td className="py-2.5 text-right text-gray-600">
                                                        {s.bill_amount > 0 ? <><CurrencySymbol />{s.bill_amount.toFixed(2)}</> : '—'}
                                                    </td>
                                                    <td className="py-2.5 text-right text-gray-400 text-xs">{s.created_at}</td>
                                                    <td className="py-2.5 text-center">
                                                        {s.is_editable && s.stamp_config && (
                                                            <button
                                                                onClick={() => openEditModal(s)}
                                                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-ticket-100 text-ticket-700 hover:bg-ticket-200 transition-colors"
                                                            >
                                                                ✏️ Pick Numbers
                                                            </button>
                                                        )}
                                                        {s.is_editable && s.editable_until && (
                                                            <StampCountdown editableUntil={s.editable_until} />
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile cards */}
                                <div className="md:hidden space-y-3 px-5 pb-5">
                                    {stamps.map(s => (
                                        <div key={s.id} className="bg-lucky-50/30 rounded-xl p-3 border border-lucky-100">
                                            <div className="flex items-center gap-3">
                                                <div className={`w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${s.source === 'Plan Purchase' ? 'bg-ticket-100' : s.source === 'Coupon Redemption' ? 'bg-green-100' : 'bg-lucky-100'}`}>
                                                    <span className="text-sm">{s.source === 'Plan Purchase' ? '⭐' : s.source === 'Coupon Redemption' ? '🎟️' : '🧾'}</span>
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex justify-between items-baseline">
                                                        <span className="font-mono text-xs bg-lucky-100 text-lucky-700 px-1.5 py-0.5 rounded">{s.code}</span>
                                                        <span className="text-xs text-gray-400">{s.created_at}</span>
                                                    </div>
                                                    <p className="text-xs text-gray-500 mt-0.5 truncate">{s.campaign_name ?? 'No campaign'}</p>
                                                </div>
                                            </div>
                                            {s.is_editable && s.stamp_config && (
                                                <div className="mt-2 flex items-center justify-between">
                                                    <button
                                                        onClick={() => openEditModal(s)}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-ticket-100 text-ticket-700 hover:bg-ticket-200 transition-colors"
                                                    >
                                                        ✏️ Pick Numbers
                                                    </button>
                                                    {s.editable_until && <StampCountdown editableUntil={s.editable_until} />}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="px-5 sm:px-6 pb-5 sm:pb-6">
                                <EmptyState icon="🎭" title="No stamps collected yet" description="Earn stamps by purchasing a plan or redeeming coupons at partner stores." />
                            </div>
                        )}
                    </div>

                    {/* Stamp Edit Modal */}
                    {editingStamp && editingStamp.stamp_config && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" onClick={closeEditModal}>
                            <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 relative" onClick={e => e.stopPropagation()}>
                                <button onClick={closeEditModal} className="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                                <h3 className="font-display text-lg text-gray-900 mb-1 flex items-center gap-2">
                                    🎯 Pick Your Numbers
                                </h3>
                                <p className="text-xs text-gray-500 mb-4">
                                    Choose {editingStamp.stamp_config.slots} numbers between {editingStamp.stamp_config.min} and {editingStamp.stamp_config.max} in ascending order.
                                </p>

                                {/* Live Preview */}
                                <div className="bg-lucky-50 rounded-xl p-3 mb-4 text-center border border-lucky-200">
                                    <p className="text-xs text-lucky-600 font-medium mb-1">Preview</p>
                                    <p className="font-mono text-lg font-bold text-lucky-700">{previewCode}</p>
                                </div>

                                {/* Slot Inputs */}
                                <div className="grid grid-cols-3 gap-2 mb-4">
                                    {slotValues.map((val, idx) => (
                                        <div key={idx}>
                                            <label className="text-xs text-gray-500 font-medium">Slot {idx + 1}</label>
                                            <input
                                                type="number"
                                                min={editingStamp.stamp_config.min}
                                                max={editingStamp.stamp_config.max}
                                                value={val}
                                                onChange={(e) => handleSlotChange(idx, e.target.value)}
                                                className="w-full rounded-lg border-gray-300 text-center font-mono text-sm focus:border-lucky-500 focus:ring-lucky-500"
                                            />
                                        </div>
                                    ))}
                                </div>

                                {editError && (
                                    <div className="bg-red-50 text-red-700 text-xs rounded-lg p-2.5 mb-3 border border-red-200">
                                        {editError}
                                    </div>
                                )}

                                {editingStamp.editable_until && (
                                    <div className="text-center mb-3">
                                        <StampCountdown editableUntil={editingStamp.editable_until} showLabel />
                                    </div>
                                )}

                                <div className="flex gap-2">
                                    <button
                                        onClick={closeEditModal}
                                        className="flex-1 px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        onClick={submitStampEdit}
                                        disabled={editSubmitting}
                                        className="flex-1 px-4 py-2.5 rounded-xl lucky-gradient text-white text-sm font-bold shadow-md hover:shadow-lg transition-all disabled:opacity-50"
                                    >
                                        {editSubmitting ? 'Saving...' : 'Confirm Numbers'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

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

function StampCountdown({ editableUntil, showLabel = false }) {
    const [remaining, setRemaining] = useState('');

    useEffect(() => {
        const update = () => {
            const diff = new Date(editableUntil) - new Date();
            if (diff <= 0) {
                setRemaining('Expired');
                return;
            }
            const mins = Math.floor(diff / 60000);
            const secs = Math.floor((diff % 60000) / 1000);
            setRemaining(`${mins}:${String(secs).padStart(2, '0')}`);
        };
        update();
        const timer = setInterval(update, 1000);
        return () => clearInterval(timer);
    }, [editableUntil]);

    const isExpired = remaining === 'Expired';

    return (
        <span className={`inline-flex items-center gap-1 text-xs font-mono ${isExpired ? 'text-red-500' : 'text-amber-600'}`}>
            {showLabel && <span className="font-sans font-medium">Time left:</span>}
            <span>⏱️ {remaining}</span>
        </span>
    );
}
