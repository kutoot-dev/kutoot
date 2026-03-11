<?php

namespace App\Filament\Pages;

use App\Models\AdminSetting;
use App\Services\SettingService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class ManageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 49;

    protected static ?string $navigationLabel = 'System Settings';

    protected static ?string $title = 'System Settings';

    protected static ?string $slug = 'system-settings';

    /**
     * Form state array — holds all settings keyed by their setting key.
     */
    public ?array $data = [];

    /**
     * Define settings per group with metadata for building the form dynamically.
     */
    protected function getSettingsSchema(): array
    {
        return [
            'payment' => [
                'label' => 'Payment',
                'icon' => Heroicon::OutlinedCreditCard,
                'description' => 'Razorpay payment gateway configuration',
                'fields' => [
                    'razorpay_key_id' => ['label' => 'Razorpay Key ID', 'type' => 'text', 'sensitive' => false],
                    'razorpay_key_secret' => ['label' => 'Razorpay Key Secret', 'type' => 'password', 'sensitive' => true],
                    'razorpay_webhook_secret' => ['label' => 'Razorpay Webhook Secret', 'type' => 'password', 'sensitive' => true],
                    'payment_default_gateway' => ['label' => 'Default Payment Gateway', 'type' => 'select', 'options' => ['razorpay' => 'Razorpay'], 'sensitive' => false],
                ],
            ],
            'sms' => [
                'label' => 'SMS',
                'icon' => Heroicon::OutlinedChatBubbleLeftRight,
                'description' => 'SMS gateway configuration for OTP and notifications',
                'fields' => [
                    'sms_driver' => ['label' => 'SMS Driver', 'type' => 'select', 'options' => ['way2mint' => 'Way2Mint', 'log' => 'Log (testing)'], 'sensitive' => false],
                    'way2mint_base_url' => ['label' => 'Way2Mint Base URL', 'type' => 'text', 'sensitive' => false],
                    'way2mint_username' => ['label' => 'Way2Mint Username', 'type' => 'text', 'sensitive' => false],
                    'way2mint_password' => ['label' => 'Way2Mint Password', 'type' => 'password', 'sensitive' => true],
                    'way2mint_sender_id' => ['label' => 'Sender ID', 'type' => 'text', 'sensitive' => false],
                    'way2mint_pe_id' => ['label' => 'PE ID', 'type' => 'text', 'sensitive' => false],
                    'way2mint_otp_template_id' => ['label' => 'OTP Template ID', 'type' => 'text', 'sensitive' => false],
                ],
            ],
            'platform' => [
                'label' => 'Platform',
                'icon' => Heroicon::OutlinedAdjustmentsHorizontal,
                'description' => 'Platform fees, tax rates, and currency settings',
                'fields' => [
                    'platform_fee' => ['label' => 'Platform Fee', 'type' => 'number', 'sensitive' => false],
                    'platform_fee_type' => ['label' => 'Platform Fee Type', 'type' => 'select', 'options' => ['fixed' => 'Fixed Amount', 'percentage' => 'Percentage'], 'sensitive' => false],
                    'gst_rate' => ['label' => 'GST Rate (%)', 'type' => 'number', 'sensitive' => false],
                    'plan_tax_type' => ['label' => 'Plan Tax Type', 'type' => 'select', 'options' => ['inclusive' => 'Inclusive', 'exclusive' => 'Exclusive'], 'sensitive' => false],
                    'app_currency' => ['label' => 'App Currency', 'type' => 'select', 'options' => ['INR' => 'INR (₹)', 'USD' => 'USD ($)'], 'sensitive' => false],
                ],
            ],
            'auth' => [
                'label' => 'Authentication',
                'icon' => Heroicon::OutlinedShieldCheck,
                'description' => 'Login methods, OTP settings, and reCAPTCHA configuration',
                'fields' => [
                    'otp_length' => ['label' => 'OTP Length', 'type' => 'number', 'sensitive' => false],
                    'auth_login_methods' => ['label' => 'Login Methods', 'type' => 'select', 'options' => ['otp' => 'OTP Only', 'password' => 'Password Only', 'both' => 'OTP + Password'], 'sensitive' => false],
                    'recaptcha_enabled' => ['label' => 'Enable reCAPTCHA', 'type' => 'toggle', 'sensitive' => false],
                    'recaptcha_site_key' => ['label' => 'reCAPTCHA Site Key', 'type' => 'text', 'sensitive' => false],
                    'recaptcha_secret_key' => ['label' => 'reCAPTCHA Secret Key', 'type' => 'password', 'sensitive' => true],
                ],
            ],
            'stamps' => [
                'label' => 'Stamps',
                'icon' => Heroicon::OutlinedTicket,
                'description' => 'Stamp system configuration',
                'fields' => [
                    'stamp_edit_duration_minutes' => ['label' => 'Stamp Edit Duration (minutes)', 'type' => 'number', 'sensitive' => false],
                ],
            ],
            'storage' => [
                'label' => 'Object Storage',
                'icon' => Heroicon::OutlinedCloudArrowUp,
                'description' => 'Storage mode (Local or S3/R2), upload limits, and S3 credentials',
                'fields' => [
                    'object_storage_driver' => [
                        'label' => 'Storage Mode *',
                        'type' => 'select',
                        'options' => ['local' => 'Local', 's3' => 'S3 / R2'],
                        'sensitive' => false,
                        'helperText' => 'Local stores files on server; S3 uses AWS S3, Cloudflare R2, or compatible storage.',
                        'default' => 'local',
                    ],
                    'max_upload_size_mb' => [
                        'label' => 'Max Upload Size (MB) *',
                        'type' => 'number',
                        'sensitive' => false,
                        'helperText' => 'Maximum file size for uploads. Used by Media Library, Filament, and API validation.',
                        'default' => 100,
                    ],
                    'aws_bucket' => [
                        'label' => 'AWS Bucket *',
                        'type' => 'text',
                        'sensitive' => false,
                        'helperText' => 'Bucket name (e.g. fls-xxxx for R2).',
                        'visibleWhen' => 's3',
                    ],
                    'aws_default_region' => [
                        'label' => 'AWS Region *',
                        'type' => 'text',
                        'sensitive' => false,
                        'helperText' => 'Region code (e.g. us-east-1). Use "auto" for Cloudflare R2.',
                        'visibleWhen' => 's3',
                    ],
                    'aws_access_key_id' => [
                        'label' => 'AWS Access Key ID *',
                        'type' => 'text',
                        'sensitive' => false,
                        'helperText' => 'Access key for S3/R2.',
                        'visibleWhen' => 's3',
                    ],
                    'aws_secret_access_key' => [
                        'label' => 'AWS Secret Access Key *',
                        'type' => 'password',
                        'sensitive' => true,
                        'helperText' => 'Secret key for S3/R2.',
                        'visibleWhen' => 's3',
                    ],
                    'aws_url' => [
                        'label' => 'Public URL *',
                        'type' => 'text',
                        'sensitive' => false,
                        'helperText' => 'Base URL for public file access (e.g. https://bucket.xxx.laravel.cloud). Must be the public URL, not the API endpoint.',
                        'visibleWhen' => 's3',
                    ],
                    'aws_endpoint' => [
                        'label' => 'Custom Endpoint',
                        'type' => 'text',
                        'sensitive' => false,
                        'helperText' => 'S3 API endpoint. Required for R2, Spaces, MinIO. Leave empty for standard AWS S3.',
                        'visibleWhen' => 's3',
                    ],
                    'aws_use_path_style_endpoint' => [
                        'label' => 'Path-style Endpoint',
                        'type' => 'toggle',
                        'sensitive' => false,
                        'helperText' => 'Use endpoint/bucket instead of bucket.endpoint. Usually false.',
                        'visibleWhen' => 's3',
                    ],
                ],
            ],
            'branding' => [
                'label' => 'Branding',
                'icon' => Heroicon::OutlinedPhoto,
                'description' => 'Brand assets and QR print settings',
                'fields' => [
                    'qr_logo' => ['label' => 'QR Code Logo', 'type' => 'file', 'sensitive' => false, 'helperText' => 'Upload a logo image (PNG recommended, ~200×200px). Used inside all merchant QR codes.'],
                    'qr_background' => ['label' => 'QR Print Background', 'type' => 'file', 'sensitive' => false, 'helperText' => 'Upload a background image for printed QR stickers. Used when printing QR codes. Falls back to default if not set.'],
                    'qr_print_width_in' => ['label' => 'QR Print Page Width (inches)', 'type' => 'number', 'sensitive' => false, 'default' => 4],
                    'qr_print_height_in' => ['label' => 'QR Print Page Height (inches)', 'type' => 'number', 'sensitive' => false, 'default' => 6],
                ],
            ],
        ];
    }

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $data = [];

        foreach ($this->getSettingsSchema() as $group => $groupDef) {
            foreach ($groupDef['fields'] as $key => $fieldDef) {
                // Load from DB first, then fallback via SettingService
                $dbSetting = AdminSetting::find($key);

                if ($dbSetting && !blank($dbSetting->value)) {
                    $value = $dbSetting->is_sensitive ? '' : AdminSetting::castValue($dbSetting->value, $dbSetting->type);
                }
                else {
                    $default = $fieldDef['default'] ?? null;
                    $fallbackValue = SettingService::get($key, $default);
                    // Don't pre-fill sensitive values from env
                    $value = $fieldDef['sensitive'] ? '' : $fallbackValue;
                }

                // Convert booleans for toggle fields
                if ($fieldDef['type'] === 'toggle') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }

                // File uploads need the stored path wrapped in an array for FileUpload component
                if ($fieldDef['type'] === 'file' && $value) {
                    $value = [$value];
                }

                $data[$key] = $value;
            }
        }

        $this->form->fill($data);
    }

    public function form(Schema $form): Schema
    {
        $tabs = [];

        foreach ($this->getSettingsSchema() as $group => $groupDef) {
            $fields = [];

            foreach ($groupDef['fields'] as $key => $fieldDef) {
                $dbSetting = AdminSetting::find($key);
                $source = ($dbSetting && !blank($dbSetting->value)) ? 'Database' : 'Environment (.env)';
                $helperText = $fieldDef['helperText'] ?? "Source: {$source}";

                $field = match ($fieldDef['type']) {
                        'password' => TextInput::make($key)
                        ->label($fieldDef['label'])
                        ->password()
                        ->revealable()
                        ->placeholder($fieldDef['sensitive'] ? '••••••••' : '')
                        ->helperText($helperText),

                        'number' => TextInput::make($key)
                        ->label($fieldDef['label'])
                        ->numeric()
                        ->helperText($helperText),

                        'select' => Select::make($key)
                        ->label($fieldDef['label'])
                        ->options($fieldDef['options'] ?? [])
                        ->helperText($helperText),

                        'toggle' => Toggle::make($key)
                        ->label($fieldDef['label'])
                        ->helperText($helperText),

                        'file' => FileUpload::make($key)
                        ->label($fieldDef['label'])
                        ->image()
                        ->disk(fn () => \App\Services\SettingService::getStorageDisk())
                        ->directory('settings')
                        ->visibility('public')
                        ->imagePreviewHeight('100')
                        ->helperText($fieldDef['helperText'] ?? 'Upload an image.'),

                        default => TextInput::make($key)
                        ->label($fieldDef['label'])
                        ->helperText($helperText),
                    };

                if ($key === 'object_storage_driver') {
                    $field = $field->live();
                }
                if (isset($fieldDef['visibleWhen']) && $fieldDef['visibleWhen'] === 's3') {
                    $field = $field->visible(fn (Get $get): bool => $get('object_storage_driver') === 's3');
                }
                $fields[] = $field;
            }

            $tabs[] = Tab::make($groupDef['label'])
                ->icon($groupDef['icon'])
                ->schema([
                Section::make($groupDef['description'])
                ->schema($fields)
                ->columns(2),
            ]);
        }

        return $form
            ->schema([
            Tabs::make('Settings')
            ->tabs($tabs)
            ->columnSpanFull()
            ->persistTabInQueryString(),
        ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        $updated = 0;

        foreach ($this->getSettingsSchema() as $group => $groupDef) {
            foreach ($groupDef['fields'] as $key => $fieldDef) {
                if (!array_key_exists($key, $formData)) {
                    continue;
                }

                $value = $formData[$key];

                // Skip empty password fields (don't overwrite with blank)
                if ($fieldDef['type'] === 'password' && blank($value)) {
                    continue;
                }

                // Convert toggle booleans to string
                if ($fieldDef['type'] === 'toggle') {
                    $value = $value ? '1' : '0';
                }

                // File uploads return an array of paths — store the first (or empty string)
                if ($fieldDef['type'] === 'file') {
                    $value = is_array($value) ? (collect($value)->first() ?? '') : ($value ?? '');
                }

                // Use updateOrCreate so settings that don't exist in DB yet are created
                AdminSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string)($value ?? ''),
                    'type' => match ($fieldDef['type']) {
                        'number' => 'integer',
                        'toggle' => 'boolean',
                        default => 'string',
                    },
                    'group' => $group,
                    'label' => $fieldDef['label'],
                    'is_sensitive' => $fieldDef['sensitive'] ?? false,
                ]
                );

                // Bust cache
                Cache::forget("admin_setting:{$key}");

                $updated++;
            }
        }

        Notification::make()
            ->title('Settings saved')
            ->body("{$updated} settings updated successfully.")
            ->success()
            ->send();

        // Reload to show fresh "Source:" indicators
        $this->loadSettings();
    }

    public function resetToDefaults(): void
    {
        AdminSetting::query()->delete();
        AdminSetting::clearCache();

        Notification::make()
            ->title('Settings reset')
            ->body('All settings have been cleared from the database. Environment (.env) values will now be used.')
            ->warning()
            ->send();

        $this->loadSettings();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
            SchemaForm::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                ->alignment($this->getFormActionsAlignment())
                ->key('form-actions'),
            ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
            ->label('Save Settings')
            ->submit('save'),

            Action::make('resetToDefaults')
            ->label('Reset to .env Defaults')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset All Settings?')
            ->modalDescription('This will delete all settings from the database and fall back to .env values. This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, reset all')
            ->action(fn() => $this->resetToDefaults()),
        ];
    }
}
