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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
            EditAction::make(),
        ];
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
