<?php

namespace App\Models;

use App\Enums\MerchantApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_type',
        'owner_mobile',
        'owner_email',
        'phone_verified',
        'email_verified',
        'address',
        'gst_number',
        'pan_number',
        'bank_name',
        'sub_bank_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at',
        'merchant_location_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MerchantApplicationStatus::class,
            'phone_verified' => 'boolean',
            'email_verified' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function merchantLocation(): BelongsTo
    {
        return $this->belongsTo(MerchantLocation::class);
    }

    public function isPending(): bool
    {
        return $this->status === MerchantApplicationStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === MerchantApplicationStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === MerchantApplicationStatus::Rejected;
    }
}
