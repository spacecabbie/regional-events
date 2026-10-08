<?php

namespace App\Console\Commands;

use App\Events\Event;
use App\Events\FlyerUnreadable;
use App\Events\StoreFlyer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertFlyers extends Command
{
    protected $signature = 'events:convert-flyers';

    protected $description = 'Convert stored flyers to the WebP renditions';

    public function handle(StoreFlyer $flyers): int
    {
        $disk = Storage::disk('public');
        $failed = 0;

        Event::query()->whereNotNull('flyer_path')->orderBy('id')->each(function (Event $event) use ($flyers, $disk, &$failed): void {
            if (str_ends_with((string) $event->flyer_path, '.webp') && $event->flyer_width) {
                return;
            }

            if (! $disk->exists((string) $event->flyer_path)) {
                $this->warn('Event '.$event->id.' has no flyer file.');
                $failed++;

                return;
            }

            $previous = array_filter([$event->flyer_path, $event->flyer_2x_path, $event->flyer_thumb_path]);

            try {
                $stored = $flyers->storePath($disk->path((string) $event->flyer_path));
            } catch (FlyerUnreadable $exception) {
                $this->warn('Event '.$event->id.' was not converted.');
                $failed++;

                return;
            }

            $event->forceFill($stored->attributes())->save();
            $flyers->delete(...array_values(array_diff($previous, array_filter([$stored->path, $stored->retina, $stored->thumb]))));
            $this->line('Converted event '.$event->id.'.');
        });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
