<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Events\EventSchedule;
use App\Events\EventStatus;
use App\Filament\Resources\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = EventResource::applyLocation($data, (string) ($this->data['location'] ?? ''));
        $data = EventSchedule::applyAdmin($data);
        $data = EventResource::applyFlyer($data);
        $data['status'] = EventStatus::Confirmed;
        $data['confirmed_at'] = now();

        return $data;
    }
}
