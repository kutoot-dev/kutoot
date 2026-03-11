<?php

namespace Database\Seeders;

use App\Models\AdminSetting;
use Illuminate\Database\Seeder;

class AdminSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Payment ─────────────────────────────────────────
            [
                'key' => 'razorpay_key_id',
                'value' => config('app.razorpay.key_id', ''),
                'type' => 'string',
                'group' => 'payment',
                'label' => 'Razorpay Key ID',
                'description' => 'Your Razorpay API key ID for payment processing',
                'is_sensitive' => false,
            ],
            [
                'key' => 'razorpay_key_secret',
                'value' => config('app.razorpay.key_secret', ''),
                'type' => 'string',
                'group' => 'payment',
                'label' => 'Razorpay Key Secret',
                'description' => 'Your Razorpay API secret key',
                'is_sensitive' => true,
            ],
            [
                'key' => 'razorpay_webhook_secret',
                'value' => config('app.razorpay.webhook_secret', ''),
                'type' => 'string',
                'group' => 'payment',
                'label' => 'Razorpay Webhook Secret',
                'description' => 'Secret for verifying Razorpay webhook signatures',
                'is_sensitive' => true,
            ],
            [
                'key' => 'payment_default_gateway',
                'value' => env('PAYMENT_DEFAULT_GATEWAY', 'razorpay'),
                'type' => 'string',
                'group' => 'payment',
                'label' => 'Default Payment Gateway',
                'description' => 'The default payment gateway to use (razorpay)',
                'is_sensitive' => false,
            ],

            // ── SMS ─────────────────────────────────────────────
            [
                'key' => 'sms_driver',
                'value' => config('services.sms.driver', 'log'),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'SMS Driver',
                'description' => 'SMS provider driver: log (development) or way2mint (production)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'way2mint_base_url',
                'value' => config('services.sms.way2mint.base_url', ''),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'Way2Mint Base URL',
                'description' => 'Way2Mint SMS API base URL',
                'is_sensitive' => false,
            ],
            [
                'key' => 'way2mint_username',
                'value' => config('services.sms.way2mint.username', ''),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'Way2Mint Username',
                'description' => 'Way2Mint SMS account username',
                'is_sensitive' => false,
            ],
            [
                'key' => 'way2mint_password',
                'value' => config('services.sms.way2mint.password', ''),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'Way2Mint Password',
                'description' => 'Way2Mint SMS account password',
                'is_sensitive' => true,
            ],
            [
                'key' => 'way2mint_sender_id',
                'value' => config('services.sms.way2mint.sender_id', ''),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'Way2Mint Sender ID',
                'description' => 'Sender ID for SMS messages',
                'is_sensitive' => false,
            ],
            [
                'key' => 'way2mint_pe_id',
                'value' => config('services.sms.way2mint.pe_id', ''),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'Way2Mint PE ID',
                'description' => 'Principal Entity ID for DLT registration',
                'is_sensitive' => false,
            ],
            [
                'key' => 'way2mint_otp_template_id',
                'value' => config('services.sms.way2mint.otp_template_id', ''),
                'type' => 'string',
                'group' => 'sms',
                'label' => 'Way2Mint OTP Template ID',
                'description' => 'DLT registered template ID for OTP messages',
                'is_sensitive' => false,
            ],

            // ── Platform ────────────────────────────────────────
            [
                'key' => 'platform_fee',
                'value' => env('PLATFORM_FEE', '10'),
                'type' => 'integer',
                'group' => 'platform',
                'label' => 'Platform Fee',
                'description' => 'Platform service fee amount',
                'is_sensitive' => false,
            ],
            [
                'key' => 'platform_fee_type',
                'value' => env('PLATFORM_FEE_TYPE', 'fixed'),
                'type' => 'string',
                'group' => 'platform',
                'label' => 'Platform Fee Type',
                'description' => 'Platform fee type: fixed or percentage',
                'is_sensitive' => false,
            ],
            [
                'key' => 'gst_rate',
                'value' => env('GST_RATE', '18'),
                'type' => 'integer',
                'group' => 'platform',
                'label' => 'GST Rate (%)',
                'description' => 'GST rate applied to transactions (percentage)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'plan_tax_type',
                'value' => env('PLAN_TAX_TYPE', 'exclusive'),
                'type' => 'string',
                'group' => 'platform',
                'label' => 'Plan Tax Type',
                'description' => 'How tax is calculated on plan prices: inclusive or exclusive',
                'is_sensitive' => false,
            ],
            [
                'key' => 'app_currency',
                'value' => env('APP_CURRENCY', 'INR'),
                'type' => 'string',
                'group' => 'platform',
                'label' => 'Currency',
                'description' => 'Platform currency code (e.g. INR, USD)',
                'is_sensitive' => false,
            ],

            // ── Auth ────────────────────────────────────────────
            [
                'key' => 'otp_length',
                'value' => env('OTP_LENGTH', '4'),
                'type' => 'integer',
                'group' => 'auth',
                'label' => 'OTP Length',
                'description' => 'Number of digits in OTP codes',
                'is_sensitive' => false,
            ],
            [
                'key' => 'auth_login_methods',
                'value' => 'otp',
                'type' => 'string',
                'group' => 'auth',
                'label' => 'Login Methods',
                'description' => 'Allowed login methods: otp, password, or both',
                'is_sensitive' => false,
            ],
            [
                'key' => 'recaptcha_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'auth',
                'label' => 'reCAPTCHA Enabled',
                'description' => 'Enable Google reCAPTCHA v3 on login/register forms',
                'is_sensitive' => false,
            ],
            [
                'key' => 'recaptcha_site_key',
                'value' => '',
                'type' => 'string',
                'group' => 'auth',
                'label' => 'reCAPTCHA Site Key',
                'description' => 'Google reCAPTCHA v3 site key (public)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'recaptcha_secret_key',
                'value' => '',
                'type' => 'string',
                'group' => 'auth',
                'label' => 'reCAPTCHA Secret Key',
                'description' => 'Google reCAPTCHA v3 secret key',
                'is_sensitive' => true,
            ],

            // ── Stamps ──────────────────────────────────────────
            [
                'key' => 'stamp_edit_duration_minutes',
                'value' => (string) config('services.stamps.edit_duration_minutes', 15),
                'type' => 'integer',
                'group' => 'stamps',
                'label' => 'Stamp Edit Duration (minutes)',
                'description' => 'Time window (in minutes) during which users can edit their stamp codes',
                'is_sensitive' => false,
            ],

            // ── Storage ─────────────────────────────────────────
            [
                'key' => 'aws_access_key_id',
                'value' => env('AWS_ACCESS_KEY_ID', ''),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'AWS Access Key ID',
                'description' => 'AWS S3 access key ID for file storage',
                'is_sensitive' => true,
            ],
            [
                'key' => 'aws_secret_access_key',
                'value' => env('AWS_SECRET_ACCESS_KEY', ''),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'AWS Secret Access Key',
                'description' => 'AWS S3 secret access key',
                'is_sensitive' => true,
            ],
            [
                'key' => 'aws_default_region',
                'value' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'AWS Region',
                'description' => 'AWS S3 bucket region',
                'is_sensitive' => false,
            ],
            [
                'key' => 'object_storage_driver',
                'value' => (env('FILESYSTEM_DISK') === 's3' || env('MEDIA_DISK') === 's3') ? 's3' : 'local',
                'type' => 'string',
                'group' => 'storage',
                'label' => 'Storage Mode',
                'description' => 'Local or S3 object storage',
                'is_sensitive' => false,
            ],
            [
                'key' => 'max_upload_size_mb',
                'value' => (string) (env('MAX_UPLOAD_SIZE_MB') ?: 100),
                'type' => 'integer',
                'group' => 'storage',
                'label' => 'Max Upload Size (MB)',
                'description' => 'Maximum file size for uploads',
                'is_sensitive' => false,
            ],
            [
                'key' => 'aws_bucket',
                'value' => env('AWS_BUCKET', ''),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'AWS Bucket',
                'description' => 'S3 bucket name for file uploads',
                'is_sensitive' => false,
            ],
            [
                'key' => 'aws_url',
                'value' => env('AWS_URL', ''),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'Public URL',
                'description' => 'Base URL for public file access',
                'is_sensitive' => false,
            ],
            [
                'key' => 'aws_endpoint',
                'value' => env('AWS_ENDPOINT', ''),
                'type' => 'string',
                'group' => 'storage',
                'label' => 'Custom Endpoint',
                'description' => 'S3 API endpoint for R2, Spaces, MinIO',
                'is_sensitive' => false,
            ],
            [
                'key' => 'aws_use_path_style_endpoint',
                'value' => env('AWS_USE_PATH_STYLE_ENDPOINT', 'false') === 'true' ? '1' : '0',
                'type' => 'boolean',
                'group' => 'storage',
                'label' => 'Path-style Endpoint',
                'description' => 'Use path-style URLs',
                'is_sensitive' => false,
            ],
        ];

        foreach ($settings as $setting) {
            AdminSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }
}
