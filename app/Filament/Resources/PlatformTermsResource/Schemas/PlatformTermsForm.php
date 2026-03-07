<?php

namespace App\Filament\Resources\PlatformTermsResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlatformTermsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('version')
                    ->label('Version')
                    ->helperText('Unique version identifier (e.g., 1.0, 2.0, 2.1). This must be unique across all terms.')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('title')
                    ->label('Title')
                    ->helperText('Display title for these terms and conditions.')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('content')
                    ->label('Terms and Conditions Content')
                    ->helperText('Full content of the terms and conditions. Only the active version will be shown to users.')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'strike',
                        'link',
                        'h2',
                        'h3',
                        'bulletList',
                        'orderedList',
                        'redo',
                        'undo',
                    ]),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Only one version can be active at a time. Activating this will deactivate all other versions.')
                    ->default(false),
                DateTimePicker::make('published_at')
                    ->label('Published At')
                    ->helperText('When these terms were published to users.')
                    ->nullable(),
            ]);
    }
}
