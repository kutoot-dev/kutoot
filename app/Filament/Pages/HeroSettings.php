<?php

namespace App\Filament\Pages;

use App\Models\HeroSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HeroSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;


    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Hero Settings';

    protected static ?string $navigationLabel = 'Hero Banner Text';

    public ?array $data = [];

    // locale currently being edited/selected
    public ?string $currentLocale = null;

    public function mount(): void
    {
        // determine which locale we're working with, either passed via query
        // parameter or fallback to application locale
        $locale = request('locale') ?? app()->getLocale();
        $this->currentLocale = $locale;

        $setting = HeroSetting::active($locale) ?? new HeroSetting();

        $this->form->fill([
            'title' => $setting->title ?? '',
            'description' => $setting->description ?? '',
            'is_active' => $setting->is_active ?? true,
            'locale' => $locale,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('locale')
                    ->label('Locale')
                    ->options(config('locales'))
                    ->required()
                    ->default($this->currentLocale),

                TextInput::make('title')
                    ->label('Hero Title')
                    ->helperText('This title is displayed on all hero banner slides. Required.')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Hero Description (Optional)')
                    ->helperText('Optional subtitle/description shown below the title on all slides.')
                    ->maxLength(500)
                    ->rows(3),

                Toggle::make('is_active')
                    ->label('Show on Homepage')
                    ->helperText('When enabled, this text overlays all hero campaign slides.')
                    ->default(true),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $locale = $data['locale'] ?? $this->currentLocale;

        // Deactivate all existing settings for this locale first
        HeroSetting::where('locale', $locale)
            ->update(['is_active' => false]);

        // Upsert the active setting for the current locale
        $setting = HeroSetting::where('locale', $locale)->first() ?? new HeroSetting();
        $setting->fill($data);
        $setting->locale = $locale;
        $setting->save();

        Notification::make()
            ->title('Hero Settings Saved')
            ->body('The hero banner text has been updated successfully.')
            ->success()
            ->send();
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
                ->label('Save changes')
                ->submit('save'),
        ];
    }
}
