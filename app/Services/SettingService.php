<?php

namespace App\Services;

use App\Models\AdminSetting;

/**
 * Service for accessing admin settings with .env fallback chain.
 *
 * Priority: DB → config() → env() → provided default
 */
class SettingService
{
    /**
     * Map of setting keys to their config/env fallback paths.
     *
     * @var array<string, array{config?: string, env?: string}>
     */
    protected static array $fallbackMap = [
        // Payment
        'razorpay_key_id' => ['config' => 'app.razorpay.key_id', 'env' => 'RAZORPAY_KEY_ID'],
        'razorpay_key_secret' => ['config' => 'app.razorpay.key_secret', 'env' => 'RAZORPAY_KEY_SECRET'],
        'razorpay_webhook_secret' => ['config' => 'app.razorpay.webhook_secret', 'env' => 'RAZORPAY_WEBHOOK_SECRET'],
        'payment_default_gateway' => ['env' => 'PAYMENT_DEFAULT_GATEWAY'],

        // SMS
        'sms_driver' => ['config' => 'services.sms.driver', 'env' => 'SMS_DRIVER'],
        'way2mint_base_url' => ['config' => 'services.sms.way2mint.base_url'],
        'way2mint_username' => ['config' => 'services.sms.way2mint.username'],
        'way2mint_password' => ['config' => 'services.sms.way2mint.password'],
        'way2mint_sender_id' => ['config' => 'services.sms.way2mint.sender_id'],
        'way2mint_pe_id' => ['config' => 'services.sms.way2mint.pe_id'],
        'way2mint_otp_template_id' => ['config' => 'services.sms.way2mint.otp_template_id'],

        // Platform
        'platform_fee' => ['env' => 'PLATFORM_FEE'],
        'platform_fee_type' => ['env' => 'PLATFORM_FEE_TYPE'],
        'gst_rate' => ['env' => 'GST_RATE'],
        'plan_tax_type' => ['env' => 'PLAN_TAX_TYPE'],
        'app_currency' => ['env' => 'APP_CURRENCY'],

        // Auth
        'otp_length' => ['env' => 'OTP_LENGTH'],
        'auth_login_methods' => ['env' => 'AUTH_LOGIN_METHODS'],
        'recaptcha_enabled' => [],
        'recaptcha_site_key' => ['env' => 'RECAPTCHA_SITE_KEY'],
        'recaptcha_secret_key' => ['env' => 'RECAPTCHA_SECRET_KEY'],

        // Stamps
        'stamp_edit_duration_minutes' => ['config' => 'services.stamps.edit_duration_minutes'],

        // Storage
        'aws_access_key_id' => ['env' => 'AWS_ACCESS_KEY_ID'],
        'aws_secret_access_key' => ['env' => 'AWS_SECRET_ACCESS_KEY'],
        'aws_default_region' => ['env' => 'AWS_DEFAULT_REGION'],
        'aws_bucket' => ['env' => 'AWS_BUCKET'],
        'media_disk' => ['env' => 'MEDIA_DISK'],

        // QR Print
        'qr_print_width_in' => ['env' => 'QR_PRINT_WIDTH_IN'],
        'qr_print_height_in' => ['env' => 'QR_PRINT_HEIGHT_IN'],
    ];

    /**
     * Get a setting value with full fallback chain: DB → config() → env() → default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 1. Try DB
        $dbValue = AdminSetting::get($key);

        if ($dbValue !== null) {
            return $dbValue;
        }

        // 2. Try config/env fallbacks
        $fallback = static::$fallbackMap[$key] ?? [];

        if (isset($fallback['config'])) {
            $configValue = config($fallback['config']);
            if (! blank($configValue)) {
                return $configValue;
            }
        }

        if (isset($fallback['env'])) {
            $envValue = env($fallback['env']);
            if (! blank($envValue)) {
                return $envValue;
            }
        }

        return $default;
    }

    /**
     * Check the configuration status of all integration groups.
     *
     * @return array<string, array{configured: bool, missing: array<string>, source?: 'database'|'env', driver?: string}>
     */
    public static function getConfigStatus(): array
    {
        $razorpay = array_merge(
            AdminSetting::checkGroupStatus('payment', ['razorpay_key_id', 'razorpay_key_secret']),
            ['source' => static::resolveSource('payment', ['razorpay_key_id', 'razorpay_key_secret'])],
        );
        $sms = array_merge(
            AdminSetting::checkGroupStatus('sms', ['sms_driver']),
            [
                'driver' => static::get('sms_driver', 'log'),
                'source' => static::resolveSource('sms', ['sms_driver']),
            ],
        );
        $s3 = array_merge(
            AdminSetting::checkGroupStatus('storage', ['aws_access_key_id', 'aws_secret_access_key', 'aws_bucket']),
            ['source' => static::resolveSource('storage', ['aws_access_key_id', 'aws_secret_access_key', 'aws_bucket'])],
        );
        $recaptcha = array_merge(
            AdminSetting::checkGroupStatus('auth', ['recaptcha_site_key', 'recaptcha_secret_key']),
            ['source' => static::resolveSource('auth', ['recaptcha_site_key', 'recaptcha_secret_key'])],
        );

        return [
            'razorpay' => $razorpay,
            'sms' => $sms,
            's3' => $s3,
            'recaptcha' => $recaptcha,
            'auth' => [
                'configured' => true,
                'missing' => [],
                'source' => static::resolveSource('auth', ['auth_login_methods']) ?? 'env',
                'login_methods' => static::get('auth_login_methods', 'otp'),
            ],
        ];
    }

    /**
     * Resolve configuration source for a group: 'database' if any key comes from DB, else 'env' if configured.
     */
    protected static function resolveSource(string $group, array $requiredKeys): ?string
    {
        if (empty($requiredKeys)) {
            return 'env';
        }

        $settings = AdminSetting::where('group', $group)->get()->keyBy('key');
        $anyFromDb = false;
        $allPresent = true;

        foreach ($requiredKeys as $key) {
            $dbSetting = $settings->get($key);
            $hasDb = $dbSetting && ! blank($dbSetting->value);
            if ($hasDb) {
                $anyFromDb = true;
            }
            $fallback = static::$fallbackMap[$key] ?? [];
            $envVal = null;
            if (isset($fallback['config'])) {
                $envVal = config($fallback['config']);
            }
            if ($envVal === null && isset($fallback['env'])) {
                $envVal = env($fallback['env']);
            }
            if (! $hasDb && blank($envVal)) {
                $allPresent = false;
            }
        }

        if (! $allPresent) {
            return null;
        }

        return $anyFromDb ? 'database' : 'env';
    }

    /**
     * Get public (non-sensitive) frontend config.
     *
     * @return array<string, mixed>
     */
    public static function getPublicConfig(): array
    {
        return [
            'currency' => static::get('app_currency', 'INR'),
            'razorpay_key_id' => static::get('razorpay_key_id', ''),
            'recaptcha_site_key' => static::get('recaptcha_site_key', ''),
            'recaptcha_enabled' => (bool) static::get('recaptcha_enabled', false),
            'auth_login_methods' => static::get('auth_login_methods', 'otp'),
        ];
    }
}
