<?php

namespace App\Filament\Resources\MerchantApplications\Pages;

use App\Contracts\SmsContract;
use App\Enums\MerchantApplicationStatus;
use App\Filament\Resources\MerchantApplications\MerchantApplicationResource;
use App\Mail\MerchantCredentialsMail;
use App\Models\Merchant;
use App\Models\MerchantCategory;
use App\Models\MerchantLocation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ViewMerchantApplication extends ViewRecord
{
    protected static string $resource = MerchantApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getApproveAction(),
            $this->getRejectAction(),
            $this->getCompleteMerchantProfileAction(),
            $this->getViewCredentialsAction(),
            $this->getGeneratePasswordAction(),
            $this->getResendToEmailAction(),
            $this->getResendToMobileAction(),
            EditAction::make(),
        ];
    }

    protected function getOwnerUser(): ?User
    {
        $locationId = $this->record->merchant_location_id;
        if (! $locationId) {
            return null;
        }

        $location = MerchantLocation::find($locationId);

        return $location?->users()->wherePivot('role', 'owner')->first();
    }

    protected function canAccessProfileWizard(): bool
    {
        $user = auth()->user();

        return $this->record->isApproved()
            && (bool) $this->record->merchant_location_id
            && $user?->hasAnyRole(['Executive', 'Admin', 'Merchant Admin', 'Super Admin']);
    }

    protected function getProfileDefaultCategoryId(?MerchantLocation $location): ?int
    {
        if ($location?->merchant_category_id) {
            return (int) $location->merchant_category_id;
        }

        if (! $this->record->store_type) {
            return null;
        }

        return MerchantCategory::query()
            ->where('name', $this->record->store_type)
            ->value('id');
    }

    protected function getProfileWizardDefaults(MerchantLocation $location): array
    {
        $owner = $this->getOwnerUser();
        $record = $this->record;

        return [
            'data_consent_accepted' => true,
            'store_email' => $location->store_email ?: $record->owner_email,
            'branch_name' => $location->branch_name ?: $record->store_name,
            'merchant_category_id' => $this->getProfileDefaultCategoryId($location),
            'year_of_establishment' => $location->year_of_establishment,
            'business_ownership_type' => $location->business_ownership_type,
            'address' => $location->address ?: $record->address,
            'area_locality' => $location->area_locality,
            'city_name' => $location->city_name,
            'state_name' => $location->state_name,
            'pin_code' => $location->pin_code,
            'google_maps_link' => $location->google_maps_link,
            'owner_name' => $location->owner_name ?: $owner?->name,
            'owner_mobile_whatsapp' => $location->owner_mobile_whatsapp ?: $record->owner_mobile,
            'owner_email' => $location->owner_email ?: $record->owner_email,
            'has_business_partner' => (bool) $location->has_business_partner,
            'partner_name' => $location->partner_name,
            'partner_mobile' => $location->partner_mobile,
            'partner_role' => $location->partner_role,
            'average_monthly_sales_range' => $location->average_monthly_sales_range,
            'average_profit_margin_range' => $location->average_profit_margin_range,
            'kutoot_customer_discount_offer' => $location->kutoot_customer_discount_offer,
            'exclusive_discount_for_kutoot' => $location->exclusive_discount_for_kutoot,
            'max_discount_policy' => $location->max_discount_policy,
            'minimum_bill_amount_for_discount' => $location->minimum_bill_amount_for_discount,
            'creative_consent' => $location->creative_consent,
            'requested_creatives' => $location->requested_creatives ?? [],
            'gst_registration_status' => $location->gst_registration_status ?: ($location->gst_number ? 'gst_registered' : null),
            'preferred_settlement_method' => $location->preferred_settlement_method,
            'settlement_details' => $location->settlement_details ?: $location->upi_id,
            'declaration_accepted' => (bool) $location->declaration_accepted,
            'communication_consent' => (bool) $location->communication_consent,
            'additional_comments' => $location->additional_comments,
        ];
    }

    protected function syncSingleMediaFromUpload(MerchantLocation $location, array $data, string $field, string $collection): void
    {
        $uploaded = $data[$field] ?? null;

        if (! $uploaded) {
            return;
        }

        $path = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        if (! $path) {
            return;
        }

        $location->clearMediaCollection($collection);
        $location->addMediaFromDisk($path, 'public')->toMediaCollection($collection);
    }

    protected function syncMultipleMediaFromUpload(MerchantLocation $location, array $data, string $field, string $collection): void
    {
        $paths = array_values(array_filter((array) ($data[$field] ?? [])));
        if ($paths === []) {
            return;
        }

        $location->clearMediaCollection($collection);
        foreach ($paths as $path) {
            $location->addMediaFromDisk($path, 'public')->toMediaCollection($collection);
        }
    }

    protected function saveMerchantProfile(array $data): void
    {
        $location = MerchantLocation::findOrFail($this->record->merchant_location_id);

        DB::transaction(function () use ($location, $data) {
            $location->update([
                'branch_name' => $data['branch_name'],
                'store_email' => $data['store_email'],
                'merchant_category_id' => $data['merchant_category_id'],
                'year_of_establishment' => $data['year_of_establishment'] ?? null,
                'business_ownership_type' => $data['business_ownership_type'],
                'address' => $data['address'],
                'area_locality' => $data['area_locality'],
                'city_name' => $data['city_name'],
                'state_name' => $data['state_name'],
                'pin_code' => $data['pin_code'],
                'google_maps_link' => $data['google_maps_link'] ?? null,
                'owner_name' => $data['owner_name'],
                'owner_mobile_whatsapp' => $data['owner_mobile_whatsapp'],
                'owner_email' => $data['owner_email'] ?? null,
                'has_business_partner' => (bool) ($data['has_business_partner'] ?? false),
                'partner_name' => ($data['has_business_partner'] ?? false) ? ($data['partner_name'] ?? null) : null,
                'partner_mobile' => ($data['has_business_partner'] ?? false) ? ($data['partner_mobile'] ?? null) : null,
                'partner_role' => ($data['has_business_partner'] ?? false) ? ($data['partner_role'] ?? null) : null,
                'average_monthly_sales_range' => $data['average_monthly_sales_range'],
                'average_profit_margin_range' => $data['average_profit_margin_range'] ?? null,
                'kutoot_customer_discount_offer' => $data['kutoot_customer_discount_offer'],
                'exclusive_discount_for_kutoot' => (bool) $data['exclusive_discount_for_kutoot'],
                'max_discount_policy' => $data['max_discount_policy'],
                'minimum_bill_amount_for_discount' => $data['minimum_bill_amount_for_discount'],
                'creative_consent' => $data['creative_consent'],
                'requested_creatives' => $data['requested_creatives'] ?? [],
                'gst_registration_status' => $data['gst_registration_status'],
                'preferred_settlement_method' => $data['preferred_settlement_method'],
                'settlement_details' => $data['settlement_details'] ?? null,
                'declaration_accepted' => (bool) ($data['declaration_accepted'] ?? false),
                'communication_consent' => (bool) ($data['communication_consent'] ?? false),
                'additional_comments' => $data['additional_comments'] ?? null,
                'profile_completed_at' => now(),
                'profile_completed_by' => auth()->id(),
            ]);

            $this->syncSingleMediaFromUpload($location, $data, 'store_logo_upload', 'store_logo');
            $this->syncMultipleMediaFromUpload($location, $data, 'store_photos_upload', 'store_photos');
            $this->syncSingleMediaFromUpload($location, $data, 'gst_certificate_upload', 'gst_certificate');
            $this->syncSingleMediaFromUpload($location, $data, 'pan_card_upload', 'pan_card');
            $this->syncSingleMediaFromUpload($location, $data, 'aadhaar_card_upload', 'aadhaar_card');
        });
    }

    protected function getCompleteMerchantProfileAction(): Action
    {
        $location = $this->record->merchant_location_id
            ? MerchantLocation::find($this->record->merchant_location_id)
            : null;

        return Action::make('completeMerchantProfile')
            ->label('Complete Merchant Profile')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('primary')
            ->visible(fn () => $this->canAccessProfileWizard())
            ->modalHeading('Complete Merchant Profile')
            ->modalDescription('Fill all mandatory details to complete onboarding for this approved merchant location.')
            ->fillForm(fn (): array => $location ? $this->getProfileWizardDefaults($location) : [])
            ->form([
                Wizard::make([
                    Step::make('Consent & Store')
                        ->schema([
                            Placeholder::make('consent_notice')
                                ->label('Data Consent Notice')
                                ->content('By submitting, you consent to Kutoot collecting and processing business/KYC/payment information for onboarding, verification, settlement, and compliance.'),
                            Checkbox::make('data_consent_accepted')
                                ->label('I understand and accept this data consent notice.')
                                ->required()
                                ->accepted(),
                            TextInput::make('store_email')
                                ->label('Email')
                                ->email()
                                ->required(),
                            TextInput::make('branch_name')
                                ->label('Store Name')
                                ->required(),
                            Select::make('merchant_category_id')
                                ->label('Store Category')
                                ->options(MerchantCategory::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('year_of_establishment')
                                ->label('Year of Establishment')
                                ->maxLength(4),
                            Radio::make('business_ownership_type')
                                ->label('Business Ownership Type')
                                ->options([
                                    'proprietorship' => 'Proprietorship',
                                    'partnership' => 'Partnership',
                                    'private_limited' => 'Private Limited',
                                    'llp' => 'LLP',
                                    'unregistered' => 'Unregistered',
                                ])
                                ->columns(2)
                                ->required(),
                        ]),
                    Step::make('Store Location')
                        ->schema([
                            Textarea::make('address')
                                ->label('Full Store Address')
                                ->required(),
                            TextInput::make('area_locality')
                                ->label('Area / Locality')
                                ->required(),
                            TextInput::make('city_name')
                                ->label('City')
                                ->required(),
                            TextInput::make('state_name')
                                ->label('State')
                                ->required(),
                            TextInput::make('pin_code')
                                ->label('PIN Code')
                                ->required(),
                            TextInput::make('google_maps_link')
                                ->label('Google Maps Location Link')
                                ->url(),
                        ]),
                    Step::make('Primary Contact')
                        ->schema([
                            TextInput::make('owner_name')
                                ->label('Owner / Authorized Signatory Name')
                                ->required(),
                            TextInput::make('owner_mobile_whatsapp')
                                ->label('Mobile Number (WhatsApp Enabled)')
                                ->required(),
                            TextInput::make('owner_email')
                                ->label('Email Address')
                                ->email(),
                        ]),
                    Step::make('Partner Details')
                        ->schema([
                            Radio::make('has_business_partner')
                                ->label('Do you have a business partner / co-owner?')
                                ->boolean()
                                ->options([
                                    true => 'Yes',
                                    false => 'No',
                                ])
                                ->inline()
                                ->default(false)
                                ->required()
                                ->live(),
                            TextInput::make('partner_name')
                                ->label('Partner Name')
                                ->visible(fn ($get): bool => (bool) $get('has_business_partner'))
                                ->required(fn ($get): bool => (bool) $get('has_business_partner')),
                            TextInput::make('partner_mobile')
                                ->label('Partner Mobile Number')
                                ->visible(fn ($get): bool => (bool) $get('has_business_partner'))
                                ->required(fn ($get): bool => (bool) $get('has_business_partner')),
                            Select::make('partner_role')
                                ->label('Partner Role')
                                ->options([
                                    'co_owner' => 'Co-Owner',
                                    'managing_partner' => 'Managing Partner',
                                    'silent_partner' => 'Silent Partner',
                                    'operations_partner' => 'Operations Partner',
                                ])
                                ->visible(fn ($get): bool => (bool) $get('has_business_partner'))
                                ->required(fn ($get): bool => (bool) $get('has_business_partner')),
                        ]),
                    Step::make('Performance & Discount')
                        ->schema([
                            Select::make('average_monthly_sales_range')
                                ->label('Approximate Average Monthly Sales')
                                ->options([
                                    'below_100k' => 'Below ₹1,00,000',
                                    '100k_300k' => '₹1,00,000 – ₹3,00,000',
                                    '300k_500k' => '₹3,00,000 – ₹5,00,000',
                                    '500k_1m' => '₹5,00,000 – ₹10,00,000',
                                    '1m_2_5m' => '₹10,00,000 – ₹25,00,000',
                                    'above_2_5m' => '₹25,00,000+',
                                ])
                                ->required(),
                            Select::make('average_profit_margin_range')
                                ->label('Average Profit Margin Range (%)')
                                ->options([
                                    'below_5' => 'Below 5%',
                                    '5_8' => '5% – 8%',
                                    '8_12' => '8% – 12%',
                                    '12_18' => '12% – 18%',
                                    '18_25' => '18% – 25%',
                                    'above_25' => 'Above 25%',
                                ]),
                            Select::make('kutoot_customer_discount_offer')
                                ->label('How much discount will you offer for Kutoot customers?')
                                ->options([
                                    '5_percent' => '5%',
                                    '10_percent' => '10%',
                                    '15_percent' => '15%',
                                    '20_percent' => '20%',
                                    'need_guidance' => 'Need guidance from Kutoot',
                                ])
                                ->required(),
                            Radio::make('exclusive_discount_for_kutoot')
                                ->label('Do you agree to offer exclusive discounts for Kutoot customers at your store?')
                                ->boolean()
                                ->options([
                                    true => 'Yes',
                                    false => 'No',
                                ])
                                ->inline()
                                ->required(),
                            Select::make('max_discount_policy')
                                ->label('Maximum discount allowed per bill for Kutoot customers')
                                ->options([
                                    '5_percent_bill' => '5% of bill value',
                                    '10_percent_bill' => '10% of bill value',
                                    'fixed_amount' => 'Fixed amount (store decides)',
                                    'need_recommendation' => 'Need Kutoot recommendation',
                                ])
                                ->required(),
                            TextInput::make('minimum_bill_amount_for_discount')
                                ->label('Minimum Bill Amount to Avail Kutoot Customer Discount (₹)')
                                ->numeric()
                                ->required(),
                        ]),
                    Step::make('Marketing & Media')
                        ->schema([
                            Radio::make('creative_consent')
                                ->label('Do you allow Kutoot to create promotional creatives using your store name & logo?')
                                ->options([
                                    'yes' => 'Yes',
                                    'yes_with_approval' => 'Yes, with approval',
                                    'no' => 'No',
                                ])
                                ->required(),
                            CheckboxList::make('requested_creatives')
                                ->label('What creatives would you like from Kutoot?')
                                ->options([
                                    'posters' => 'Posters',
                                    'banners' => 'Banners',
                                    'reels_videos' => 'Reels / Videos',
                                    'whatsapp_creatives' => 'WhatsApp creatives',
                                ])
                                ->columns(2),
                            FileUpload::make('store_logo_upload')
                                ->label('Upload Store Logo')
                                ->image()
                                ->disk('public')
                                ->directory('merchant-profile-temp')
                                ->maxSize(10240)
                                ->required(! $location?->hasMedia('store_logo')),
                            FileUpload::make('store_photos_upload')
                                ->label('Upload Store Photos')
                                ->disk('public')
                                ->directory('merchant-profile-temp')
                                ->acceptedFileTypes([
                                    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                                    'video/mp4', 'video/webm', 'video/quicktime',
                                ])
                                ->multiple()
                                ->maxFiles(5)
                                ->maxSize(10240),
                        ]),
                    Step::make('KYC Documents')
                        ->schema([
                            Radio::make('gst_registration_status')
                                ->label('GST Registration Status')
                                ->options([
                                    'gst_registered' => 'GST Registered',
                                    'gst_not_available' => 'GST Not Available',
                                ])
                                ->required()
                                ->live(),
                            FileUpload::make('gst_certificate_upload')
                                ->label('Upload GST Certificate')
                                ->disk('public')
                                ->directory('merchant-profile-temp')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                                ])
                                ->maxSize(10240)
                                ->visible(fn ($get): bool => $get('gst_registration_status') === 'gst_registered')
                                ->required(fn ($get): bool => $get('gst_registration_status') === 'gst_registered' && ! $location?->hasMedia('gst_certificate')),
                            FileUpload::make('pan_card_upload')
                                ->label('Upload PAN Card (Owner / Business)')
                                ->disk('public')
                                ->directory('merchant-profile-temp')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                                ])
                                ->maxSize(10240)
                                ->required(! $location?->hasMedia('pan_card')),
                            FileUpload::make('aadhaar_card_upload')
                                ->label('Upload Aadhaar Card (Owner / Authorized Signatory)')
                                ->disk('public')
                                ->directory('merchant-profile-temp')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                                ])
                                ->maxSize(10240)
                                ->required(! $location?->hasMedia('aadhaar_card')),
                        ]),
                    Step::make('Settlement & Declaration')
                        ->schema([
                            Radio::make('preferred_settlement_method')
                                ->label('Preferred Settlement Method')
                                ->options([
                                    'upi' => 'UPI',
                                    'bank_transfer' => 'Bank Transfer',
                                    'weekly_manual' => 'Weekly Manual Settlement',
                                ])
                                ->required(),
                            Textarea::make('settlement_details')
                                ->label('UPI ID / Bank Details')
                                ->rows(3)
                                ->required(),
                            Checkbox::make('declaration_accepted')
                                ->label('I confirm that the information provided is true and I agree to participate as a Kutoot Store Partner under the Kutoot Partner MOU.')
                                ->accepted()
                                ->required(),
                            Checkbox::make('communication_consent')
                                ->label('I agree to receive WhatsApp, SMS, or email communication from Kutoot.'),
                            Textarea::make('additional_comments')
                                ->label('Additional Comments')
                                ->rows(3),
                        ]),
                ])->columnSpanFull(),
            ])
            ->modalWidth('7xl')
            ->action(function (array $data) {
                $this->saveMerchantProfile($data);

                Notification::make()
                    ->title('Merchant profile completed')
                    ->body('Approved merchant location details have been saved successfully.')
                    ->success()
                    ->send();
            });
    }

    protected function generateAndUpdateCredentials(): array
    {
        $user = $this->getOwnerUser();
        if (! $user) {
            return [null, null];
        }

        $plainPassword = Str::random(10);
        $user->update(['password' => Hash::make($plainPassword)]);

        return [$user->username, $plainPassword];
    }

    protected function getViewCredentialsAction(): Action
    {
        return Action::make('viewCredentials')
            ->label('View credentials')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->visible(fn () => $this->record->isApproved() && $this->record->merchant_location_id)
            ->modalHeading('Store credentials')
            ->modalDescription('Username is shown below. Password cannot be retrieved—use "Resend to email" or "Resend to mobile" to send credentials, or use the Generate password action.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->form([
                \Filament\Forms\Components\Placeholder::make('username')
                    ->label('Username')
                    ->content(fn () => $this->getOwnerUser()?->username ?? '—'),
            ])
            ->action(fn () => null);
    }

    protected function getGeneratePasswordAction(): Action
    {
        return Action::make('generatePassword')
            ->label('Generate password')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->visible(fn () => $this->record->isApproved() && $this->record->merchant_location_id)
            ->requiresConfirmation()
            ->modalHeading('Generate new password')
            ->modalDescription('A new temporary password will be created. It will be shown in a notification—copy and share with the store owner. They should change it on first login.')
            ->action(function () {
                [$username, $password] = $this->generateAndUpdateCredentials();
                if ($username) {
                    Notification::make()
                        ->title('New password generated')
                        ->body("Username: {$username}\nTemporary password: {$password}\n\nCopy and share with the store owner.")
                        ->success()
                        ->persistent()
                        ->send();
                } else {
                    Notification::make()->title('Error')->body('Could not find store owner.')->danger()->send();
                }
            });
    }

    protected function getResendToEmailAction(): Action
    {
        return Action::make('resendToEmail')
            ->label('Resend to email')
            ->icon('heroicon-o-envelope')
            ->color('gray')
            ->visible(fn () => $this->record->isApproved() && $this->record->merchant_location_id && $this->record->owner_email)
            ->requiresConfirmation()
            ->modalHeading('Resend credentials to email')
            ->modalDescription("A new temporary password will be generated and sent to {$this->record->owner_email}.")
            ->action(function () {
                [$username, $plainPassword] = $this->generateAndUpdateCredentials();
                if (! $username) {
                    Notification::make()->title('Error')->body('Could not find store owner.')->danger()->send();
                    return;
                }

                try {
                    Mail::to($this->record->owner_email)->send(
                        new MerchantCredentialsMail($this->record->store_name, $username, $plainPassword)
                    );
                    Notification::make()
                        ->title('Credentials sent')
                        ->body("Login details sent to {$this->record->owner_email}.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Log::error("Failed to resend credentials email: {$e->getMessage()}");
                    Notification::make()
                        ->title('Failed to send')
                        ->body('Could not send email. Please try again.')
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getResendToMobileAction(): Action
    {
        return Action::make('resendToMobile')
            ->label('Resend to mobile')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('gray')
            ->visible(fn () => $this->record->isApproved() && $this->record->merchant_location_id && $this->record->owner_mobile)
            ->requiresConfirmation()
            ->modalHeading('Resend credentials to mobile')
            ->modalDescription("A new temporary password will be generated and sent via SMS to {$this->record->owner_mobile}.")
            ->action(function () {
                [$username, $plainPassword] = $this->generateAndUpdateCredentials();
                if (! $username) {
                    Notification::make()->title('Error')->body('Could not find store owner.')->danger()->send();
                    return;
                }

                try {
                    $sms = app(SmsContract::class);
                    $message = "Kutoot store credentials: Store: {$this->record->store_name}. Username: {$username}, Password: {$plainPassword}. Change password after first login. -Team Kutoot";
                    $sms->send($this->record->owner_mobile, $message);
                    Notification::make()
                        ->title('Credentials sent')
                        ->body("Login details sent via SMS to {$this->record->owner_mobile}.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Log::error("Failed to resend credentials SMS: {$e->getMessage()}");
                    Notification::make()
                        ->title('Failed to send')
                        ->body('Could not send SMS. Please try again.')
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve & Create Store')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn () => $this->record->isPending())
            ->requiresConfirmation()
            ->modalHeading('Approve Store Application')
            ->modalDescription('This will create a Merchant, Store Location, and User account with auto-generated credentials.')
            ->form([
                Select::make('merchant_id')
                    ->label('Attach to Existing Merchant (optional)')
                    ->options(Merchant::pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Create new merchant automatically')
                    ->helperText('Leave empty to create a new merchant from the store name.'),
                TextInput::make('commission_percentage')
                    ->label('Commission %')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
                Textarea::make('admin_notes')
                    ->label('Admin Notes (optional)')
                    ->maxLength(65535),
            ])
            ->action(function (array $data) {
                $this->approveApplication($data);
            });
    }

    protected function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => $this->record->isPending())
            ->requiresConfirmation()
            ->modalHeading('Reject Store Application')
            ->form([
                Textarea::make('admin_notes')
                    ->label('Reason for Rejection')
                    ->required()
                    ->maxLength(65535),
            ])
            ->action(function (array $data) {
                $this->record->update([
                    'status' => MerchantApplicationStatus::Rejected,
                    'admin_notes' => $data['admin_notes'],
                    'processed_by' => auth()->id(),
                    'processed_at' => now(),
                ]);

                Notification::make()
                    ->title('Application Rejected')
                    ->body("Application for \"{$this->record->store_name}\" has been rejected.")
                    ->danger()
                    ->send();
            });
    }

    protected function approveApplication(array $data): void
    {
        DB::transaction(function () use ($data) {
            $app = $this->record;

            // 1. Create or find merchant
            if (! empty($data['merchant_id'])) {
                $merchant = Merchant::findOrFail($data['merchant_id']);
            } else {
                $merchant = Merchant::create([
                    'name' => $app->store_name,
                    'slug' => Str::slug($app->store_name) . '-' . Str::random(4),
                    'is_active' => true,
                ]);
            }

            // 2. Resolve merchant category by store_type name
            $category = MerchantCategory::where('name', $app->store_type)->first();
            if (! $category) {
                $category = MerchantCategory::create([
                    'name' => $app->store_type,
                    'is_active' => true,
                    'serial' => MerchantCategory::max('serial') + 1,
                ]);
            }

            // 3. Create merchant location
            $location = MerchantLocation::create([
                'merchant_id' => $merchant->id,
                'merchant_category_id' => $category->id,
                'branch_name' => $app->store_name,
                'commission_percentage' => $data['commission_percentage'] ?? 0,
                'is_active' => true,
                'address' => $app->address,
                'gst_number' => $app->gst_number,
                'pan_number' => $app->pan_number,
                'bank_name' => $app->bank_name,
                'sub_bank_name' => $app->sub_bank_name,
                'account_number' => $app->account_number,
                'ifsc_code' => $app->ifsc_code,
                'upi_id' => $app->upi_id,
            ]);

            // 4. Create user with auto-generated credentials
            $username = 'store-' . $app->owner_mobile;
            $plainPassword = Str::random(10);

            // Check if a user already exists with this email or mobile
            $user = User::where('email', $app->owner_email)
                ->orWhere('mobile', $app->owner_mobile)
                ->first();

            if ($user) {
                // Update existing user with username if not set
                if (! $user->username) {
                    $user->update(['username' => $username]);
                    Log::info("Assigned username {$username} to existing user {$user->id}");
                } else {
                    $username = $user->username;
                }
                // Update password
                $user->update(['password' => Hash::make($plainPassword)]);
            } else {
                $user = User::create([
                    'name' => $app->store_name,
                    'email' => $app->owner_email,
                    'mobile' => $app->owner_mobile,
                    'username' => $username,
                    'password' => Hash::make($plainPassword),
                ]);
                Log::info("Created new user {$user->id} with username {$username} for store {$app->store_name}");
            }

            // 5. Attach user to merchant location with 'owner' role
            if (! $user->merchantLocations()->where('merchant_location_id', $location->id)->exists()) {
                try {
                    $user->merchantLocations()->attach($location->id, ['role' => 'owner']);
                    Log::info("Successfully attached user {$user->id} (username: {$username}) to merchant location {$location->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to attach user {$user->id} to merchant location {$location->id}: " . $e->getMessage());
                    throw new \Exception("Failed to associate store with user account: " . $e->getMessage());
                }
            }

            // Verify the attachment was successful
            if (! $user->merchantLocations()->where('merchant_location_id', $location->id)->exists()) {
                throw new \Exception("Store association verification failed. User was not properly linked to the merchant location.");
            }

            // 6. Update application status
            $app->update([
                'status' => MerchantApplicationStatus::Approved,
                'admin_notes' => $data['admin_notes'] ?? $app->admin_notes,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'merchant_location_id' => $location->id,
            ]);

            // 7. Send credentials via email
            $emailSent = false;
            if ($app->owner_email) {
                try {
                    Mail::to($app->owner_email)->send(
                        new MerchantCredentialsMail($app->store_name, $username, $plainPassword)
                    );
                    Log::info("Credentials email sent successfully to {$app->owner_email}");
                    $emailSent = true;
                } catch (\Exception $e) {
                    Log::error("Failed to send credentials email to {$app->owner_email}: " . $e->getMessage());
                }
            }

            // 8. Send credentials via SMS
            $smsSent = false;
            if ($app->owner_mobile) {
                try {
                    $sms = app(SmsContract::class);
                    $message = "Welcome to Kutoot! Your store \"{$app->store_name}\" is approved. Login: Username: {$username}, Password: {$plainPassword}. Change your password after first login. -Team Kutoot";
                    $sms->send($app->owner_mobile, $message);
                    Log::info("Credentials SMS sent successfully to {$app->owner_mobile}");
                    $smsSent = true;
                } catch (\Exception $e) {
                    Log::error("Failed to send credentials SMS to {$app->owner_mobile}: " . $e->getMessage());
                }
            }

            // Notify admin about delivery status
            if (! $emailSent && ! $smsSent) {
                Notification::make()
                    ->title('Application Approved — Credentials NOT Delivered')
                    ->body("Store \"{$app->store_name}\" created (Username: {$username}), but BOTH email and SMS failed. Please share credentials manually.")
                    ->warning()
                    ->persistent()
                    ->send();
            } elseif (! $emailSent || ! $smsSent) {
                $failedChannel = ! $emailSent ? 'email' : 'SMS';
                Notification::make()
                    ->title('Application Approved — Partial Delivery')
                    ->body("Store \"{$app->store_name}\" created (Username: {$username}). Credentials sent via " . ($emailSent ? 'email' : 'SMS') . " but {$failedChannel} delivery failed.")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Application Approved!')
                    ->body("Store \"{$app->store_name}\" created successfully. Username: {$username}. Credentials sent to {$app->owner_email} & {$app->owner_mobile}.")
                    ->success()
                    ->send();
            }
        });
    }
}
