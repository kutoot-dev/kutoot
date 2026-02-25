<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                TextInput::make('country'),
                TextInput::make('state'),
                TextInput::make('city'),
                TextInput::make('pin_code')->label('Pin code'),
                Textarea::make('full_address')->label('Full address'),
                FileUpload::make('profile_picture')
                    ->image()
                    ->maxSize(2048)
                    ->directory('avatars'),
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
