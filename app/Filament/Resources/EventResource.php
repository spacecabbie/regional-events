<?php

namespace App\Filament\Resources;

use App\Events\Event;
use App\Events\EventRules;
use App\Events\EventStatus;
use App\Events\FlyerBin;
use App\Events\StoreFlyer;
use App\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Filament\Resources\EventResource\Pages\EditEvent;
use App\Filament\Resources\EventResource\Pages\ListEvents;
use App\Locations\LocationParser;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(200)
                ->rules(EventRules::name()),
            Toggle::make('all_day')
                ->label('All day')
                ->live()
                ->helperText('Covers the whole local day. Otherwise enter a 24-hour start and end.'),
            DateTimePicker::make('starts_at')
                ->label(fn (Get $get): string => $get('all_day') ? 'Start date' : 'Starts')
                ->required()
                ->seconds(false)
                ->native(false)
                ->time(fn (Get $get): bool => ! $get('all_day'))
                ->displayFormat(fn (Get $get): string => $get('all_day') ? 'j M Y' : 'j M Y, H:i')
                ->timezone(config('events.timezone'))
                ->helperText('Portugal time (Europe/Lisbon), 24-hour. Stored in UTC.')
                ->rules(EventRules::adminStartsAt()),
            DateTimePicker::make('ends_at')
                ->label(fn (Get $get): string => $get('all_day') ? 'End date' : 'Ends')
                ->required()
                ->seconds(false)
                ->native(false)
                ->time(fn (Get $get): bool => ! $get('all_day'))
                ->displayFormat(fn (Get $get): string => $get('all_day') ? 'j M Y' : 'j M Y, H:i')
                ->timezone(config('events.timezone'))
                ->rules(EventRules::adminEndsAt()),
            TextInput::make('location')
                ->required()
                ->maxLength(2000)
                ->rules(EventRules::location())
                ->dehydrated(false)
                ->helperText('A Google Maps link, an OpenStreetMap link, a geo: link, or coordinates. Place names are not looked up.')
                ->afterStateHydrated(function (TextInput $component): void {
                    $record = $component->getRecord();

                    if ($record instanceof Event && $record->exists) {
                        $component->state($record->lat.', '.$record->lng);
                    }
                }),
            TextInput::make('email')
                ->email()
                ->maxLength(255)
                ->rules(EventRules::adminEmail())
                ->helperText('Optional for an event you add here. Required on the public form.'),
            Select::make('status')
                ->options([
                    EventStatus::Pending->value => 'Pending',
                    EventStatus::Confirmed->value => 'Confirmed',
                ])
                ->default(EventStatus::Confirmed->value)
                ->required()
                ->hidden(fn (string $operation): bool => $operation === 'create'),
            FileUpload::make('flyer_path')
                ->label('Flyer')
                ->disk('public')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize((int) config('events.upload_max_kilobytes'))
                ->helperText('JPEG, PNG, or WebP. Scaled to fit A4. A smaller image is left as it is.')
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                    $stored = app(StoreFlyer::class)->store($file);
                    app(FlyerBin::class)->put($stored);

                    return $stored->path;
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('flyer_thumb_path')
                    ->label('Flyer')
                    ->disk('public')
                    ->height(48),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('j M Y, H:i')
                    ->timezone(config('events.timezone'))
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('j M Y, H:i')
                    ->timezone(config('events.timezone')),
                TextColumn::make('all_day')
                    ->label('All day')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'All day' : 'Timed'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('email')
                    ->searchable(),
            ])
            ->defaultSort('starts_at')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyLocation(array $data, string $location): array
    {
        $coordinate = app(LocationParser::class)->parse($location);
        $data['lat'] = $coordinate->latitude;
        $data['lng'] = $coordinate->longitude;
        unset($data['location']);

        if (array_key_exists('email', $data)) {
            $email = is_string($data['email']) ? trim($data['email']) : '';
            $data['email'] = $email === '' ? null : mb_strtolower($email);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyFlyer(array $data): array
    {
        $stored = app(FlyerBin::class)->pull();

        if ($stored) {
            $data['flyer_path'] = $stored->path;
            $data['flyer_thumb_path'] = $stored->thumb;
        }

        return $data;
    }
}
