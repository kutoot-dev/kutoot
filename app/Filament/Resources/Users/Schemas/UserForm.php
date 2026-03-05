<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rules(['nullable', 'required_without:mobile']),
                TextInput::make('mobile')
                    ->label('Mobile number')
                    ->tel()
                    ->rules(['nullable', 'required_without:email']),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                    ])
                    ->nullable(),
                Select::make('country_id')
                    ->label('Country')
                    ->options(fn () => Country::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('state_id', null) ?? $set('city_id', null)),
                Select::make('state_id')
                    ->label('State')
                    ->options(fn (Get $get) => $get('country_id')
                        ? State::where('country_id', $get('country_id'))->orderBy('name')->pluck('name', 'id')->toArray()
                        : [])
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('city_id', null)),
                Select::make('city_id')
                    ->label('City')
                    ->options(fn (Get $get) => $get('state_id')
                        ? City::where('state_id', $get('state_id'))->orderBy('name')->pluck('name', 'id')->toArray()
                        : [])
                    ->searchable()
                    ->nullable(),
                TextInput::make('pin_code')->label('Pin code'),
                Textarea::make('full_address')->label('Full address'),
                SpatieMediaLibraryFileUpload::make('avatar')
                    ->collection('avatar')
                    ->image()
                    ->conversion('thumb')
                    ->maxSize(config('upload.max_file_size_kb'))
                    ->label('Profile Picture')
                    ->rules(['nullable', 'image', 'mimes:jpeg,png,webp,svg']),
                Select::make('primary_campaign_id')
                    ->relationship('primaryCampaign', 'reward_name')
                    ->label('Primary Campaign'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
