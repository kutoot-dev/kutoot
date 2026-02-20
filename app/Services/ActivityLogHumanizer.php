<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;

class ActivityLogHumanizer
{
    /**
     * Map of subject_type + event to human-readable template strings.
     * Placeholders: {name}, {title}, {code}, {branch}, {amount}, {plan}, {reward}, {discount}
     *
     * @var array<string, array<string, string>>
     */
    private const TEMPLATES = [
        'User' => [
            'created' => 'Account was created',
            'updated' => 'Profile was updated',
            'deleted' => 'Account was deleted',
        ],
        'Transaction' => [
            'created' => 'New transaction of ₹{amount} was initiated',
            'updated' => 'Transaction of ₹{amount} was updated',
            'deleted' => 'Transaction was removed',
        ],
        'DiscountCoupon' => [
            'created' => 'Coupon "{title}" was created with {discount} off',
            'updated' => 'Coupon "{title}" was updated',
            'deleted' => 'Coupon "{title}" was removed',
        ],
        'CouponRedemption' => [
            'created' => 'Redeemed a coupon — saved ₹{discount}',
            'updated' => 'Coupon redemption was updated',
            'deleted' => 'Coupon redemption was removed',
        ],
        'Campaign' => [
            'created' => 'Campaign "{reward}" was created',
            'updated' => 'Campaign "{reward}" was updated',
            'deleted' => 'Campaign was removed',
        ],
        'Stamp' => [
            'created' => 'Earned a stamp ({code})',
            'updated' => 'Stamp {code} was updated',
            'deleted' => 'Stamp was removed',
        ],
        'Merchant' => [
            'created' => 'Merchant "{name}" was registered',
            'updated' => 'Merchant "{name}" was updated',
            'deleted' => 'Merchant was removed',
        ],
        'MerchantLocation' => [
            'created' => 'Store location "{branch}" was added',
            'updated' => 'Store location "{branch}" was updated',
            'deleted' => 'Store location was removed',
        ],
        'SubscriptionPlan' => [
            'created' => 'Subscription plan "{name}" was created',
            'updated' => 'Subscription plan "{name}" was updated',
            'deleted' => 'Subscription plan was removed',
        ],
        'UserSubscription' => [
            'created' => 'Subscribed to the {plan} plan',
            'updated' => 'Subscription was updated',
            'deleted' => 'Subscription was cancelled',
        ],
        'QrCode' => [
            'created' => 'QR code {code} was generated',
            'updated' => 'QR code {code} was updated',
            'deleted' => 'QR code was removed',
            'scanned' => 'QR code {code} was scanned',
        ],
    ];

    /**
     * Transform an activity log record into a human-readable string.
     */
    public function humanize(Activity $activity): string
    {
        $subjectType = class_basename($activity->subject_type ?? '');
        $event = $activity->event ?? 'updated';

        $template = self::TEMPLATES[$subjectType][$event] ?? null;

        if (! $template) {
            return ucfirst($event).' '.str_replace('_', ' ', \Illuminate\Support\Str::snake($subjectType));
        }

        $replacements = $this->extractReplacements($activity, $subjectType);

        return str_replace(
            array_map(fn ($key) => '{'.$key.'}', array_keys($replacements)),
            array_values($replacements),
            $template
        );
    }

    /**
     * Get the emoji icon for a given event type.
     */
    public function icon(string $event): string
    {
        return match ($event) {
            'created' => '✨',
            'updated' => '✏️',
            'deleted' => '🗑️',
            'scanned' => '📱',
            default => '⚡',
        };
    }

    /**
     * Extract replacement values from the activity log's subject and properties.
     *
     * @return array<string, string>
     */
    private function extractReplacements(Activity $activity, string $subjectType): array
    {
        $subject = $activity->subject;
        $properties = $activity->properties?->toArray() ?? [];
        $attributes = $properties['attributes'] ?? [];

        return match ($subjectType) {
            'User' => [
                'name' => $subject?->name ?? $attributes['name'] ?? 'Unknown',
            ],
            'Transaction' => [
                'amount' => $subject?->total_amount ?? $attributes['total_amount'] ?? '0.00',
            ],
            'DiscountCoupon' => [
                'title' => $subject?->title ?? $attributes['title'] ?? 'Unknown',
                'discount' => $this->formatDiscount($subject, $attributes),
            ],
            'CouponRedemption' => [
                'discount' => $subject?->discount_applied ?? $attributes['discount_applied'] ?? '0.00',
            ],
            'Campaign' => [
                'reward' => $subject?->reward_name ?? $attributes['reward_name'] ?? 'Unknown',
            ],
            'Stamp' => [
                'code' => $subject?->code ?? $attributes['code'] ?? 'N/A',
            ],
            'Merchant' => [
                'name' => $subject?->name ?? $attributes['name'] ?? 'Unknown',
            ],
            'MerchantLocation' => [
                'branch' => $subject?->branch_name ?? $attributes['branch_name'] ?? 'Unknown',
            ],
            'SubscriptionPlan' => [
                'name' => $subject?->name ?? $attributes['name'] ?? 'Unknown',
            ],
            'UserSubscription' => [
                'plan' => $subject?->plan?->name ?? 'Unknown',
            ],
            'QrCode' => [
                'code' => $subject?->unique_code ?? $attributes['unique_code'] ?? 'N/A',
            ],
            default => [],
        };
    }

    /**
     * Format discount display from a coupon subject or attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function formatDiscount(mixed $subject, array $attributes): string
    {
        $type = $subject?->discount_type?->value ?? $attributes['discount_type'] ?? 'fixed';
        $value = $subject?->discount_value ?? $attributes['discount_value'] ?? '0';

        if ($type === 'percentage') {
            return $value.'%';
        }

        return '₹'.$value;
    }
}
