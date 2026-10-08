<?php

namespace App\Submissions;

use App\Events\Event;
use App\Events\EventSchedule;
use App\Events\EventStatus;
use App\Events\StoreFlyer;
use App\Locations\LocationParser;
use App\Mail\ConfirmEventMail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubmitEvent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data, ?UploadedFile $flyer): Event
    {
        $coordinate = app(LocationParser::class)->parse((string) $data['location']);
        $stored = $flyer ? app(StoreFlyer::class)->store($flyer) : null;
        $schedule = EventSchedule::fromForm($data);

        $event = Event::query()->create([
            'name' => $data['name'],
            'starts_at' => $schedule['starts_at'],
            'ends_at' => $schedule['ends_at'],
            'all_day' => $schedule['all_day'],
            'email' => Str::lower((string) $data['email']),
            'status' => EventStatus::Pending,
            'lat' => $coordinate->latitude,
            'lng' => $coordinate->longitude,
            ...($stored?->attributes() ?? [
                'flyer_path' => null,
                'flyer_2x_path' => null,
                'flyer_thumb_path' => null,
                'flyer_width' => null,
                'flyer_height' => null,
                'flyer_2x_width' => null,
            ]),
        ]);

        Mail::to($event->email)->send(new ConfirmEventMail($event));

        return $event;
    }
}
