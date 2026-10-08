<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MapSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Map';

    protected static ?string $title = 'Map settings';

    protected static ?string $slug = 'map';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'google_maps_api_key' => Setting::getValue('google_maps_api_key') ?? (string) config('services.google.maps_key'),
            'google_maps_map_id' => Setting::getValue('google_maps_map_id')
                ?: (string) config('services.google.maps_id', 'DEMO_MAP_ID'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('google_maps_api_key')
                ->label('Google Maps API key')
                ->password()
                ->revealable()
                ->helperText('Restrict this key to https://regional-events.hhaufe.eu. Leave it empty to hide Google Maps. The key is visible in the browser, which is how the Maps JavaScript API works.'),
            TextInput::make('google_maps_map_id')
                ->label('Google Map ID')
                ->helperText('Use DEMO_MAP_ID until a Cloud Map ID exists. Required for the advanced markers.')
                ->maxLength(200),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save map settings')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::putValue('google_maps_api_key', trim((string) ($state['google_maps_api_key'] ?? '')));
        $mapId = trim((string) ($state['google_maps_map_id'] ?? ''));
        Setting::putValue('google_maps_map_id', $mapId !== '' ? $mapId : 'DEMO_MAP_ID');

        Notification::make()
            ->title('Map settings saved')
            ->success()
            ->send();
    }
}
