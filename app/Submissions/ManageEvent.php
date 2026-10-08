<?php

namespace App\Submissions;

use App\Events\Event;
use App\Events\EventSchedule;
use App\Events\StoreFlyer;
use App\Locations\LocationParser;
use Illuminate\Http\UploadedFile;

class ManageEvent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data, ?UploadedFile $flyer): void
    {
        $coordinate = app(LocationParser::class)->parse((string) $data['location']);

        $schedule = EventSchedule::fromForm($data);

        $event->name = $data['name'];
        $event->starts_at = $schedule['starts_at'];
        $event->ends_at = $schedule['ends_at'];
        $event->all_day = $schedule['all_day'];
        $event->lat = $coordinate->latitude;
        $event->lng = $coordinate->longitude;

        if ($flyer) {
            $previous = array_filter([$event->flyer_path, $event->flyer_2x_path, $event->flyer_thumb_path]);
            $stored = app(StoreFlyer::class)->store($flyer);
            $event->forceFill($stored->attributes());
            $event->save();
            app(StoreFlyer::class)->delete(...array_values(array_diff($previous, array_filter([$stored->path, $stored->retina, $stored->thumb]))));

            return;
        }

        $event->save();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
