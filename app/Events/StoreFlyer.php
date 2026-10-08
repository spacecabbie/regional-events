<?php

namespace App\Events;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * Fits a flyer inside ISO 216 A4 at 150 DPI, in either orientation.
 * Smaller images stay as they are. The stored file is JPEG and at most 2 MB,
 * so the original upload and its EXIF are not kept.
 */
class StoreFlyer
{
    public function store(UploadedFile $file): StoredFlyer
    {
        $image = Image::load($file->getPathname())->orientation();
        $portrait = $image->getHeight() >= $image->getWidth();
        $boxWidth = $portrait ? (int) config('events.a4.width') : (int) config('events.a4.height');
        $boxHeight = $portrait ? (int) config('events.a4.height') : (int) config('events.a4.width');
        $image->fit(Fit::Max, $boxWidth, $boxHeight)->background('#ffffff')->format('jpg');

        $directory = 'events/'.Str::uuid();
        Storage::disk('public')->makeDirectory($directory);

        $path = $directory.'/flyer.jpg';
        $absolute = Storage::disk('public')->path($path);
        $this->saveWithinLimit($image, $absolute, $boxWidth, $boxHeight);

        $thumb = $directory.'/thumb.jpg';
        Image::load($absolute)
            ->fit(Fit::Max, (int) config('events.thumb_max'), (int) config('events.thumb_max'))
            ->format('jpg')
            ->quality(80)
            ->save(Storage::disk('public')->path($thumb));

        return new StoredFlyer($path, $thumb);
    }

    public function delete(?string $flyer, ?string $thumb): void
    {
        $disk = Storage::disk('public');

        foreach ([$flyer, $thumb] as $path) {
            if (! is_string($path) || $path === '' || str_contains($path, '..') || ! str_starts_with($path, 'events/')) {
                continue;
            }

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    private function saveWithinLimit(Image $image, string $absolute, int $boxWidth, int $boxHeight): void
    {
        $max = (int) config('events.flyer_max_bytes');
        $quality = 85;

        do {
            $image->quality($quality)->save($absolute);

            if (filesize($absolute) <= $max) {
                return;
            }

            $quality -= 10;
        } while ($quality >= 40);

        $guard = 0;

        while (filesize($absolute) > $max && $guard < 12) {
            $info = getimagesize($absolute);
            $width = max(1, (int) round(($info[0] ?? 1) * 0.7));
            $height = max(1, (int) round(($info[1] ?? 1) * 0.7));

            if ($width < 48 && $height < 48) {
                break;
            }

            Image::load($absolute)
                ->fit(Fit::Max, $width, $height)
                ->format('jpg')
                ->quality(60)
                ->save($absolute);
            $guard++;
        }

        if (filesize($absolute) > $max) {
            @unlink($absolute);

            throw new RuntimeException('The flyer could not be stored within the size limit.');
        }
    }
}
