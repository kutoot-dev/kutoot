<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\CreatorType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('creator_type')
                    ->options(CreatorType::class)
                    ->required(),
                Select::make('creator_id')
                    ->relationship('creator', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('reward_name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(CampaignStatus::class)
                    ->default('active')
                    ->required(),
                DatePicker::make('start_date')
                    ->required(),
                TextInput::make('reward_cost_target')
                    ->required()
                    ->numeric(),
                TextInput::make('stamp_target')
                    ->required()
                    ->numeric(),
                TextInput::make('collected_commission_cache')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('issued_stamps_cache')
                    ->numeric()
                    ->default(0),
                TextInput::make('marketing_bounty_percentage')
                    ->label('Marketing Bounty %')
                    ->helperText('Dummy percentage added to the bounty meter for marketing purposes (0-100).')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100),
                DateTimePicker::make('winner_announcement_date'),
                CheckboxList::make('plans')
                    ->relationship('plans', 'name')
                    ->label('Eligible Subscription Plans')
                    ->helperText('Select which subscription plans can access this campaign.')
                    ->bulkToggleable()
                    ->columns(2),

                Section::make('Media')
                    ->description('Upload images and videos for this campaign.')
                    ->collapsible()
                    ->components([
                        SpatieMediaLibraryFileUpload::make('media')
                            ->collection('media')
                            ->multiple()
                            ->reorderable()
                            ->acceptedFileTypes([
                                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                                'video/mp4', 'video/webm', 'video/quicktime',
                            ])
                            ->maxSize(102400)
                            ->conversion('thumb')
                            ->responsiveImages()
                            ->customHeaders(['CacheControl' => 'max-age=86400']),
                    ]),

                Section::make('Stamp Code Configuration')
                    ->description('Configure the lottery-style stamp code format for this campaign.')
                    ->collapsible()
                    ->components([
                        TextInput::make('code')
                            ->label('Campaign Code')
                            ->helperText('Unique uppercase alphanumeric code used as the stamp prefix (e.g., DIWALI2026). Leave empty to auto-generate.')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper(trim($state)) : strtoupper(Str::random(6)))
                            ->live(onBlur: true),

                        TextInput::make('stamp_slots')
                            ->label('Number of Slots')
                            ->helperText('How many number slots each stamp code will have (e.g., 6 for CAMP-01-02-03-45-46-49).')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(6)
                            ->live(),

                        TextInput::make('stamp_slot_min')
                            ->label('Slot Minimum Value')
                            ->helperText('The smallest number allowed in each slot.')
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->live(),

                        TextInput::make('stamp_slot_max')
                            ->label('Slot Maximum Value')
                            ->helperText('The largest number allowed in each slot.')
                            ->numeric()
                            ->minValue(1)
                            ->default(49)
                            ->live()
                            ->rules([
                                fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get): void {
                                    $min = (int) $get('stamp_slot_min');
                                    $slots = (int) $get('stamp_slots');
                                    if ($value !== null && $value !== '' && (int) $value <= $min) {
                                        $fail('Maximum value must be greater than the minimum value.');
                                    }
                                    if ($value !== null && $slots > 0 && ((int) $value - $min + 1) < $slots) {
                                        $fail("The range must be at least {$slots} to fit all slots in ascending order.");
                                    }
                                },
                            ]),

                        Toggle::make('stamp_editable_on_plan_purchase')
                            ->label('Editable on Plan Purchase')
                            ->helperText('Allow users to pick their own slot numbers when stamps are awarded via plan purchase or upgrade.')
                            ->default(false),

                        Toggle::make('stamp_editable_on_coupon_redemption')
                            ->label('Editable on Coupon Redemption')
                            ->helperText('Allow users to pick their own slot numbers when stamps are awarded via successful coupon redemption.')
                            ->default(false),

                        Placeholder::make('stamp_preview')
                            ->label('Stamp Code Preview')
                            ->content(function (Get $get): string {
                                $code = $get('code');
                                $slots = (int) $get('stamp_slots');
                                $min = (int) $get('stamp_slot_min');
                                $max = (int) $get('stamp_slot_max');

                                if (! $code || $slots <= 0 || $min < 0 || $max <= $min || ($max - $min + 1) < $slots) {
                                    return 'Fill in the fields above to see a preview.';
                                }

                                $digits = strlen((string) $max);
                                $range = range($min, $max);
                                shuffle($range);
                                $selected = array_slice($range, 0, $slots);
                                sort($selected);

                                $paddedSlots = array_map(
                                    fn (int $v): string => str_pad((string) $v, $digits, '0', STR_PAD_LEFT),
                                    $selected,
                                );

                                $sampleCode = strtoupper($code).'-'.implode('-', $paddedSlots);

                                // Calculate combinations C(range_size, slots)
                                $rangeSize = $max - $min + 1;
                                $combinations = self::binomial($rangeSize, $slots);
                                $formattedCombinations = number_format($combinations);

                                return "Sample: {$sampleCode}\nPossible combinations: {$formattedCombinations}";
                            }),
                    ]),
            ]);
    }

    /**
     * Calculate binomial coefficient C(n, k).
     */
    protected static function binomial(int $n, int $k): int
    {
        if ($k > $n) {
            return 0;
        }

        if ($k === 0 || $k === $n) {
            return 1;
        }

        if ($k > $n - $k) {
            $k = $n - $k;
        }

        $result = 1;
        for ($i = 0; $i < $k; $i++) {
            $result = intdiv($result * ($n - $i), $i + 1);
        }

        return $result;
    }
}
