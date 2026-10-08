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

    public ?string $previousThumb = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = EventResource::applyLocation($data, (string) ($this->data['location'] ?? ''));
        $data = EventSchedule::applyAdmin($data);
        $storedThumb = $data['flyer_thumb_path'] ?? null;
        $data = EventResource::applyFlyer($data);

        if (($data['flyer_thumb_path'] ?? null) !== $storedThumb) {
            $this->previousFlyer = $this->getRecord()->flyer_path;
            $this->previousThumb = $this->getRecord()->flyer_thumb_path;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->previousFlyer && $this->previousFlyer !== $this->getRecord()->flyer_path) {
            app(StoreFlyer::class)->delete($this->previousFlyer, $this->previousThumb);
        }
    }
}
