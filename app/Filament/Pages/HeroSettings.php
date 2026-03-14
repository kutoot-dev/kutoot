<?php

namespace App\Filament\Pages;

use App\Models\HeroSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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

    public ?HeroSetting $heroSetting = null;

    // locale currently being edited/selected
    public ?string $currentLocale = null;

    public function mount(): void
    {
        $locale = request('locale') ?? app()->getLocale();
        $this->currentLocale = $locale;

        $setting = HeroSetting::active($locale) ?? HeroSetting::where('locale', $locale)->first();
        if (! $setting) {
            $setting = HeroSetting::create([
                'title' => '',
                'description' => '',
                'is_active' => true,
                'locale' => $locale,
            ]);
        }
        $this->heroSetting = $setting;

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

                SpatieMediaLibraryFileUpload::make('hero_media')
                    ->label('Hero media (videos and images)')
                    ->collection('hero_media')
                    ->multiple()
                    ->reorderable()
                    ->acceptedFileTypes([
                        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                        'video/mp4', 'video/webm', 'video/quicktime',
                    ])
                    ->maxSize((int) config('upload.max_upload_size_mb', 100) * 1024)
                    ->conversion('thumb')
                    ->helperText('Add multiple images and/or videos for the hero carousel. Images and videos will rotate in the hero section.'),
            ])
            ->statePath('data')
            ->model($this->heroSetting ?? new HeroSetting());
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $locale = $data['locale'] ?? $this->currentLocale;

        HeroSetting::where('locale', $locale)
            ->where('id', '!=', $this->heroSetting?->id)
            ->update(['is_active' => false]);

        $setting = $this->heroSetting ?? HeroSetting::where('locale', $locale)->first() ?? new HeroSetting();
        $setting->fill([
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'locale' => $locale,
        ]);
        $setting->save();

        $this->form->model($setting)->saveRelationships();

        Notification::make()
            ->title('Hero Settings Saved')
            ->body('The hero banner text and media have been updated successfully.')
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
