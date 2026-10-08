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
            $previousFlyer = $event->flyer_path;
            $previousThumb = $event->flyer_thumb_path;
            $stored = app(StoreFlyer::class)->store($flyer);
            $event->flyer_path = $stored->path;
            $event->flyer_thumb_path = $stored->thumb;
            $event->save();
            app(StoreFlyer::class)->delete($previousFlyer, $previousThumb);

            return;
        }

        $event->save();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
