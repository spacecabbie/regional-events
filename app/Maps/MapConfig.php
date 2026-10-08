<?php

namespace App\Maps;

use App\Models\Setting;

class MapConfig
{
    public function googleKey(): ?string
    {
        if (Setting::query()->where('key', 'google_maps_api_key')->exists()) {
            $stored = Setting::getValue('google_maps_api_key');

            return is_string($stored) && $stored !== '' ? $stored : null;
        }

        $env = config('services.google.maps_key');

        return is_string($env) && $env !== '' ? $env : null;
    }

    public function googleMapId(): string
    {
        $stored = Setting::getValue('google_maps_map_id');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $env = config('services.google.maps_id');

        return is_string($env) && $env !== '' ? $env : 'DEMO_MAP_ID';
    }

    public function googleEnabled(): bool
    {
        return $this->googleKey() !== null;
    }
}
