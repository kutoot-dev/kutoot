<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CouponRedemption extends Model
{
    /** @use HasFactory<\Database\Factories\CouponRedemptionFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'transaction_id',
        'discount_applied',
        'original_bill_amount',
        'platform_fee',
        'gst_amount',
        'total_paid',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_applied' => 'decimal:2',
            'original_bill_amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_paid' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<DiscountCoupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
