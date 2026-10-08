<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Events\EventSchedule;
use App\Events\StoreFlyer;
use App\Filament\Resources\EventResource;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    public ?string $previousFlyer = null;

    public ?string $previousRetina = null;

    public ?string $previousThumb = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = EventResource::applyLocation($data, (string) ($this->data['location'] ?? ''));
        $data = EventSchedule::applyAdmin($data);
        $data = EventResource::applyFlyer($data);
        $record = $this->getRecord();

        if (($data['flyer_path'] ?? null) !== $record->flyer_path) {
            $this->previousFlyer = $record->flyer_path;
            $this->previousRetina = $record->flyer_2x_path;
            $this->previousThumb = $record->flyer_thumb_path;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->previousFlyer && $this->previousFlyer !== $this->getRecord()->flyer_path) {
            app(StoreFlyer::class)->delete($this->previousFlyer, $this->previousRetina, $this->previousThumb);
        }
    }
}
