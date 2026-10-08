<?php

namespace App\Submissions;

use App\Events\Event;
use App\Events\EventStatus;
use App\Events\StoreFlyer;
use App\Locations\LocationParser;
use App\Mail\ConfirmEventMail;
use Carbon\Carbon;
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

        $event = Event::query()->create([
            'name' => $data['name'],
            'starts_at' => Carbon::parse((string) $data['starts_at'], config('events.timezone'))->utc(),
            'email' => Str::lower((string) $data['email']),
            'status' => EventStatus::Pending,
            'lat' => $coordinate->latitude,
            'lng' => $coordinate->longitude,
            'flyer_path' => $stored?->path,
            'flyer_thumb_path' => $stored?->thumb,
        ]);

        Mail::to($event->email)->send(new ConfirmEventMail($event));

        return $event;
    }
}
